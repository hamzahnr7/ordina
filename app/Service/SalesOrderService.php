<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Transaction\TransactionManagerInterface;
use App\Domain\SalesOrderStatus;
use App\Entity\SalesOrder;
use App\Repository\Contracts\CustomerRepositoryInterface;
use App\Repository\Contracts\ProductRepositoryInterface;
use App\Repository\Contracts\ProductStockRepositoryInterface;
use App\Repository\Contracts\SalesOrderItemRepositoryInterface;
use App\Repository\Contracts\SalesOrderRepositoryInterface;
use App\Repository\Contracts\StockLedgerRepositoryInterface;
use App\Repository\Contracts\WarehouseRepositoryInterface;
use App\Service\Exception\ForbiddenOperationException;
use App\Service\Exception\ValidationException;

/**
 * SO-01. approve() enforces the brief's segregation-of-duties rule
 * server-side (never trust the UI alone - ARCH-01): the approver can never
 * be the same user who created the order, mirroring the
 * chk_so_approver_not_creator CHECK constraint, but checked here first so
 * the rejection is a clean message instead of a raw SQL error.
 *
 * processGoodsIssue() is ARCH-02's actual load-bearing path - see
 * docs/architecture/adr-0002-concurrency-safe-stock.md.
 */
final class SalesOrderService
{
    private const PER_PAGE = 10;

    public function __construct(
        private readonly SalesOrderRepositoryInterface $salesOrders,
        private readonly SalesOrderItemRepositoryInterface $items,
        private readonly ProductStockRepositoryInterface $stocks,
        private readonly StockLedgerRepositoryInterface $ledger,
        private readonly CustomerRepositoryInterface $customers,
        private readonly WarehouseRepositoryInterface $warehouses,
        private readonly ProductRepositoryInterface $products,
        private readonly TransactionManagerInterface $transactions,
    ) {
    }

    /**
     * @param array{search?:string, status?:string} $filters
     * @return array{items: list<array<string, mixed>>, total: int, page: int, perPage: int, totalPages: int, sortDir: string}
     */
    public function paginate(array $filters, ?int $ownerId, string $sortDir, int $page): array
    {
        $page = max(1, $page);
        $result = $this->salesOrders->paginateForListing($filters, $ownerId, $sortDir, $page, self::PER_PAGE);
        $totalPages = max(1, (int) ceil($result['total'] / self::PER_PAGE));

        return [
            'items' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => self::PER_PAGE,
            'totalPages' => $totalPages,
            'sortDir' => strtoupper($sortDir) === 'ASC' ? 'asc' : 'desc',
        ];
    }

    public function find(int $id): ?SalesOrder
    {
        return $this->salesOrders->findById($id);
    }

    /**
     * @return array{salesOrder: SalesOrder, customerName: string, warehouseName: string, items: list<array{item: \App\Entity\SalesOrderItem, productName: string, productSku: string}>, ledger: list<array<string, mixed>>}|null
     */
    public function detail(int $id): ?array
    {
        $so = $this->salesOrders->findById($id);

        if ($so === null) {
            return null;
        }

        $items = array_map(function ($item) {
            $product = $this->products->findById($item->productId);

            return ['item' => $item, 'productName' => $product?->name ?? '-', 'productSku' => $product?->sku ?? '-'];
        }, $this->items->findBySalesOrderId($id));

        return [
            'salesOrder' => $so,
            'customerName' => $this->customers->findById($so->customerId)?->name ?? '-',
            'warehouseName' => $this->warehouses->findById($so->warehouseId)?->name ?? '-',
            'items' => $items,
            'ledger' => $this->ledger->findByReference('SO', $id),
        ];
    }

    /**
     * @param array{customer_id?:string|int, warehouse_id?:string|int} $input
     * @param list<array{product_id?:string|int, qty?:string|int, sell_price?:string|float}> $itemsInput
     */
    public function create(array $input, array $itemsInput, int $createdBy): SalesOrder
    {
        $errors = [...$this->validateHeader($input), ...$this->validateItems($itemsInput)];

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $this->transactions->transactional(function () use ($input, $itemsInput, $createdBy) {
            $so = $this->salesOrders->save(new SalesOrder(
                id: null,
                customerId: (int) $input['customer_id'],
                warehouseId: (int) $input['warehouse_id'],
                status: SalesOrderStatus::Draft,
                createdBy: $createdBy,
                approvedBy: null,
            ));

            $this->items->insertMany((int) $so->id, array_map(static fn (array $item): array => [
                'product_id' => (int) $item['product_id'],
                'qty' => (int) $item['qty'],
                'sell_price' => (float) $item['sell_price'],
            ], $itemsInput));

            return $so;
        });
    }

    public function submit(int $id): void
    {
        $so = $this->salesOrders->findById($id);

        if ($so === null || !$so->status->canBeSubmitted()) {
            throw new ForbiddenOperationException('Hanya Sales Order berstatus Draft yang bisa diajukan.');
        }

        $this->salesOrders->updateStatus($id, SalesOrderStatus::PendingApproval);
    }

    public function approve(int $id, int $approverId): void
    {
        $so = $this->salesOrders->findById($id);

        if ($so === null || !$so->status->canBeDecided()) {
            throw new ForbiddenOperationException('Hanya Sales Order berstatus Pending Approval yang bisa disetujui.');
        }

        if ($so->createdBy === $approverId) {
            throw new ForbiddenOperationException('Anda tidak dapat menyetujui Sales Order milik Anda sendiri.');
        }

        $this->salesOrders->approve($id, $approverId);
    }

    public function reject(int $id): void
    {
        $so = $this->salesOrders->findById($id);

        if ($so === null || !$so->status->canBeDecided()) {
            throw new ForbiddenOperationException('Hanya Sales Order berstatus Pending Approval yang bisa ditolak.');
        }

        $this->salesOrders->updateStatus($id, SalesOrderStatus::Cancelled);
    }

    public function cancel(int $id): void
    {
        $so = $this->salesOrders->findById($id);

        if ($so === null || !$so->status->canBeCancelled()) {
            throw new ForbiddenOperationException('Sales Order ini tidak bisa dibatalkan pada status saat ini.');
        }

        $this->salesOrders->updateStatus($id, SalesOrderStatus::Cancelled);
    }

    /** ARCH-02: rejects (rolling back any partial decrements already made in this call) the moment any item's stock is insufficient. */
    public function processGoodsIssue(int $salesOrderId, int $performedBy): void
    {
        $so = $this->salesOrders->findById($salesOrderId);

        if ($so === null || !$so->status->canIssueGoods()) {
            throw new ForbiddenOperationException('Goods issue hanya bisa diproses untuk Sales Order berstatus Approved.');
        }

        $items = $this->items->findBySalesOrderId($salesOrderId);

        if ($items === []) {
            throw new ForbiddenOperationException('Sales Order ini tidak memiliki item.');
        }

        $this->transactions->transactional(function () use ($so, $items, $salesOrderId, $performedBy) {
            foreach ($items as $item) {
                if (!$this->stocks->decrementIfAvailable($item->productId, $so->warehouseId, $item->qty)) {
                    $productName = $this->products->findById($item->productId)?->name ?? "produk #{$item->productId}";

                    throw new ValidationException(['stock' => "Stok tidak mencukupi untuk {$productName}."]);
                }

                $this->ledger->record($item->productId, $so->warehouseId, 'Issue', $item->qty, 'SO', $salesOrderId, $performedBy);
            }

            $this->salesOrders->updateStatus($salesOrderId, SalesOrderStatus::Fulfilled);
        });
    }

    /** @param array<string, mixed> $input @return array<string, string> */
    private function validateHeader(array $input): array
    {
        $errors = [];

        $customerId = (int) ($input['customer_id'] ?? 0);

        if ($customerId <= 0 || $this->customers->findById($customerId) === null) {
            $errors['customer_id'] = 'Customer tidak valid.';
        }

        $warehouseId = (int) ($input['warehouse_id'] ?? 0);

        if ($warehouseId <= 0 || $this->warehouses->findById($warehouseId) === null) {
            $errors['warehouse_id'] = 'Gudang tidak valid.';
        }

        return $errors;
    }

    /**
     * @param list<array{product_id?:string|int, qty?:string|int, sell_price?:string|float}> $itemsInput
     * @return array<string, string>
     */
    private function validateItems(array $itemsInput): array
    {
        if ($itemsInput === []) {
            return ['items' => 'Minimal satu item produk wajib diisi.'];
        }

        $errors = [];

        foreach ($itemsInput as $index => $item) {
            $line = $index + 1;
            $productId = (int) ($item['product_id'] ?? 0);

            if ($productId <= 0 || $this->products->findById($productId) === null) {
                $errors["item_{$index}_product"] = "Produk tidak valid pada baris {$line}.";
            }

            if (!is_numeric($item['qty'] ?? null) || (int) $item['qty'] <= 0) {
                $errors["item_{$index}_qty"] = "Qty pada baris {$line} harus lebih dari 0.";
            }

            if (!is_numeric($item['sell_price'] ?? null) || (float) $item['sell_price'] < 0) {
                $errors["item_{$index}_price"] = "Harga jual pada baris {$line} tidak valid.";
            }
        }

        return $errors;
    }
}
