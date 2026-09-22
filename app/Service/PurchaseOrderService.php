<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Transaction\TransactionManagerInterface;
use App\Domain\PurchaseOrderStatus;
use App\Entity\PurchaseOrder;
use App\Repository\Contracts\ProductRepositoryInterface;
use App\Repository\Contracts\ProductStockRepositoryInterface;
use App\Repository\Contracts\PurchaseOrderItemRepositoryInterface;
use App\Repository\Contracts\PurchaseOrderRepositoryInterface;
use App\Repository\Contracts\StockLedgerRepositoryInterface;
use App\Repository\Contracts\SupplierRepositoryInterface;
use App\Repository\Contracts\WarehouseRepositoryInterface;
use App\Service\Exception\ForbiddenOperationException;
use App\Service\Exception\ValidationException;

/**
 * PO-01. Goods receipt (receiveGoods()) is the ARCH-02-relevant path: it
 * writes ProductStock + StockLedger (+ recomputes PurchaseOrder status)
 * inside one TransactionManagerInterface::transactional() call, matching
 * "Perubahan ProductStock dan penulisan StockLedger terjadi dalam satu
 * transaksi" - see docs/architecture/adr-0002-concurrency-safe-stock.md.
 */
final class PurchaseOrderService
{
    private const DEFAULT_PER_PAGE = 10;

    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $purchaseOrders,
        private readonly PurchaseOrderItemRepositoryInterface $items,
        private readonly ProductStockRepositoryInterface $stocks,
        private readonly StockLedgerRepositoryInterface $ledger,
        private readonly SupplierRepositoryInterface $suppliers,
        private readonly WarehouseRepositoryInterface $warehouses,
        private readonly ProductRepositoryInterface $products,
        private readonly TransactionManagerInterface $transactions,
    ) {
    }

    /**
     * @param array{search?:string, status?:string} $filters
     * @return array{items: list<array<string, mixed>>, total: int, page: int, perPage: int, totalPages: int, sortDir: string}
     */
    public function paginate(array $filters, string $sortDir, int $page, int $perPage = self::DEFAULT_PER_PAGE): array
    {
        $page = max(1, $page);
        $result = $this->purchaseOrders->paginateForListing($filters, $sortDir, $page, $perPage);
        $totalPages = max(1, (int) ceil($result['total'] / $perPage));

        return [
            'items' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
            'sortDir' => strtoupper($sortDir) === 'ASC' ? 'asc' : 'desc',
        ];
    }

    public function find(int $id): ?PurchaseOrder
    {
        return $this->purchaseOrders->findById($id);
    }

    /**
     * @return array{purchaseOrder: PurchaseOrder, supplierName: string, warehouseName: string, items: list<array{item: \App\Entity\PurchaseOrderItem, productName: string, productSku: string}>, ledger: list<array<string, mixed>>}|null
     */
    public function detail(int $id): ?array
    {
        $po = $this->purchaseOrders->findById($id);

        if ($po === null) {
            return null;
        }

        $items = array_map(function ($item) {
            $product = $this->products->findById($item->productId);

            return ['item' => $item, 'productName' => $product?->name ?? '-', 'productSku' => $product?->sku ?? '-'];
        }, $this->items->findByPurchaseOrderId($id));

        return [
            'purchaseOrder' => $po,
            'supplierName' => $this->suppliers->findById($po->supplierId)?->name ?? '-',
            'warehouseName' => $this->warehouses->findById($po->warehouseId)?->name ?? '-',
            'items' => $items,
            'ledger' => $this->ledger->findByReference('PO', $id),
        ];
    }

    /**
     * @param array{supplier_id?:string|int, warehouse_id?:string|int, order_date?:string} $input
     * @param list<array{product_id?:string|int, qty_ordered?:string|int, buy_price?:string|float}> $itemsInput
     */
    public function create(array $input, array $itemsInput, int $createdBy): PurchaseOrder
    {
        $errors = [...$this->validateHeader($input), ...$this->validateItems($itemsInput)];

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $this->transactions->transactional(function () use ($input, $itemsInput, $createdBy) {
            $po = $this->purchaseOrders->save(new PurchaseOrder(
                id: null,
                supplierId: (int) $input['supplier_id'],
                warehouseId: (int) $input['warehouse_id'],
                status: PurchaseOrderStatus::Draft,
                orderDate: (string) $input['order_date'],
                createdBy: $createdBy,
            ));

            $this->items->insertMany((int) $po->id, array_map(static fn (array $item): array => [
                'product_id' => (int) $item['product_id'],
                'qty_ordered' => (int) $item['qty_ordered'],
                'buy_price' => (float) $item['buy_price'],
            ], $itemsInput));

            return $po;
        });
    }

    public function markOrdered(int $id): void
    {
        $po = $this->purchaseOrders->findById($id);

        if ($po === null || $po->status !== PurchaseOrderStatus::Draft) {
            throw new ForbiddenOperationException('Hanya PO berstatus Draft yang bisa dikirim ke supplier.');
        }

        $this->purchaseOrders->updateStatus($id, PurchaseOrderStatus::Ordered);
    }

    public function cancel(int $id): void
    {
        $po = $this->purchaseOrders->findById($id);

        if ($po === null || !$po->status->canBeCancelled()) {
            throw new ForbiddenOperationException('PO ini tidak bisa dibatalkan pada status saat ini.');
        }

        $this->purchaseOrders->updateStatus($id, PurchaseOrderStatus::Cancelled);
    }

    /**
     * @param array<int|string, int|string> $receiptQuantities keyed by purchase_order_item.id
     */
    public function receiveGoods(int $purchaseOrderId, array $receiptQuantities, int $performedBy): void
    {
        $po = $this->purchaseOrders->findById($purchaseOrderId);

        if ($po === null || !$po->status->canReceiveGoods()) {
            throw new ForbiddenOperationException('Goods receipt hanya bisa diproses untuk PO berstatus Ordered atau Partially Received.');
        }

        $toReceive = [];

        foreach ($this->items->findByPurchaseOrderId($purchaseOrderId) as $item) {
            $qtyNow = (int) ($receiptQuantities[$item->id] ?? 0);

            if ($qtyNow <= 0) {
                continue;
            }

            if ($qtyNow > $item->remaining()) {
                throw new ValidationException(["item_{$item->id}" => "Qty diterima melebihi sisa pesanan ({$item->remaining()})."]);
            }

            $toReceive[] = [$item, $qtyNow];
        }

        if ($toReceive === []) {
            throw new ValidationException(['receipt' => 'Isi minimal satu qty penerimaan yang valid.']);
        }

        $this->transactions->transactional(function () use ($purchaseOrderId, $po, $toReceive, $performedBy) {
            foreach ($toReceive as [$item, $qtyNow]) {
                $this->items->incrementReceivedQty($item->id, $qtyNow);
                $this->stocks->incrementQuantity($item->productId, $po->warehouseId, $qtyNow);
                $this->ledger->record($item->productId, $po->warehouseId, 'Receipt', $qtyNow, 'PO', $purchaseOrderId, $performedBy);
            }

            $refreshed = $this->items->findByPurchaseOrderId($purchaseOrderId);
            $allReceived = true;
            $noneReceived = true;

            foreach ($refreshed as $item) {
                if ($item->qtyReceived > 0) {
                    $noneReceived = false;
                }

                if (!$item->isFullyReceived()) {
                    $allReceived = false;
                }
            }

            $newStatus = match (true) {
                $allReceived => PurchaseOrderStatus::Received,
                $noneReceived => PurchaseOrderStatus::Ordered,
                default => PurchaseOrderStatus::PartiallyReceived,
            };

            $this->purchaseOrders->updateStatus($purchaseOrderId, $newStatus);
        });
    }

    /** @param array<string, mixed> $input @return array<string, string> */
    private function validateHeader(array $input): array
    {
        $errors = [];

        $supplierId = (int) ($input['supplier_id'] ?? 0);

        if ($supplierId <= 0 || $this->suppliers->findById($supplierId) === null) {
            $errors['supplier_id'] = 'Supplier tidak valid.';
        }

        $warehouseId = (int) ($input['warehouse_id'] ?? 0);

        if ($warehouseId <= 0 || $this->warehouses->findById($warehouseId) === null) {
            $errors['warehouse_id'] = 'Gudang tidak valid.';
        }

        $orderDate = (string) ($input['order_date'] ?? '');
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $orderDate);

        if ($orderDate === '' || $parsed === false || $parsed->format('Y-m-d') !== $orderDate) {
            $errors['order_date'] = 'Tanggal order tidak valid (format YYYY-MM-DD).';
        }

        return $errors;
    }

    /**
     * @param list<array{product_id?:string|int, qty_ordered?:string|int, buy_price?:string|float}> $itemsInput
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

            if (!is_numeric($item['qty_ordered'] ?? null) || (int) $item['qty_ordered'] <= 0) {
                $errors["item_{$index}_qty"] = "Qty pada baris {$line} harus lebih dari 0.";
            }

            if (!is_numeric($item['buy_price'] ?? null) || (float) $item['buy_price'] < 0) {
                $errors["item_{$index}_price"] = "Harga beli pada baris {$line} tidak valid.";
            }
        }

        return $errors;
    }
}
