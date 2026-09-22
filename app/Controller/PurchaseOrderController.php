<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Authorization\Gate;
use App\Core\Authorization\Permission;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Http\Exceptions\NotFoundException;
use App\Core\Session;
use App\Core\Transaction\PdoTransactionManager;
use App\Domain\PurchaseOrderStatus;
use App\Repository\Mysql\MysqlCategoryRepository;
use App\Repository\Mysql\MysqlProductRepository;
use App\Repository\Mysql\MysqlProductStockRepository;
use App\Repository\Mysql\MysqlPurchaseOrderItemRepository;
use App\Repository\Mysql\MysqlPurchaseOrderRepository;
use App\Repository\Mysql\MysqlStockLedgerRepository;
use App\Repository\Mysql\MysqlSupplierRepository;
use App\Repository\Mysql\MysqlWarehouseRepository;
use App\Service\Exception\ForbiddenOperationException;
use App\Service\Exception\ValidationException;
use App\Service\ProductImageUploader;
use App\Service\ProductService;
use App\Service\PurchaseOrderService;
use App\Service\SupplierService;
use App\Service\WarehouseService;

/** PO-01: create/list/detail open to Admin+Warehouse Staff (Sales has no PO permission at all); goods receipt gated separately. */
final class PurchaseOrderController extends Controller
{
    public function index(): void
    {
        $this->authorizeAny(Permission::CreatePurchaseOrder, Permission::ProposePurchaseOrder, Permission::ProcessGoodsReceipt);

        $validStatuses = array_map(static fn (PurchaseOrderStatus $s) => $s->value, PurchaseOrderStatus::cases());

        $filters = array_filter([
            'search' => trim((string) ($_GET['search'] ?? '')),
            'status' => in_array($_GET['status'] ?? '', $validStatuses, true) ? $_GET['status'] : null,
        ]);
        $sortDir = ($_GET['sort'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $requestedPerPage = (int) ($_GET['per_page'] ?? 10);
        $perPage = in_array($requestedPerPage, [10, 25, 50], true) ? $requestedPerPage : 10;

        $this->view('purchase-orders/index', [
            'title' => 'Purchase Order',
            'result' => $this->service()->paginate($filters, $sortDir, $page, $perPage),
            'filters' => $filters,
            'sortDir' => $sortDir,
            'perPage' => $perPage,
            'statuses' => PurchaseOrderStatus::cases(),
            'success' => Session::pullFlash('success'),
        ]);
    }

    public function create(): void
    {
        $this->authorizeAny(Permission::CreatePurchaseOrder, Permission::ProposePurchaseOrder);

        $this->view('purchase-orders/create', [
            'title' => 'Buat Purchase Order',
            'suppliers' => $this->supplierService()->listActive(),
            'warehouses' => $this->warehouseService()->listActive(),
            'products' => $this->productService()->listActive(),
            'errors' => Session::pullFlash('errors', []),
            'old' => Session::pullFlash('old', []),
        ]);
    }

    public function store(): void
    {
        $this->authorizeAny(Permission::CreatePurchaseOrder, Permission::ProposePurchaseOrder);

        try {
            $po = $this->service()->create($_POST, $this->parseItemsFromPost(), (int) $this->currentUser()['id']);
        } catch (ValidationException $e) {
            Session::flash('errors', $e->errors());
            Session::flash('old', $_POST);
            $this->redirect('/purchase-orders/create');
        }

        Session::flash('success', 'Purchase Order berhasil dibuat sebagai Draft.');
        $this->redirect("/purchase-orders/{$po->id}");
    }

    /** @param array<string, string> $params */
    public function show(array $params): void
    {
        $this->authorizeAny(Permission::CreatePurchaseOrder, Permission::ProposePurchaseOrder, Permission::ProcessGoodsReceipt);

        $detail = $this->service()->detail((int) $params['id']);

        if ($detail === null) {
            throw new NotFoundException();
        }

        $role = $this->currentRole();

        $this->view('purchase-orders/show', [
            'title' => 'Detail Purchase Order',
            ...$detail,
            'success' => Session::pullFlash('success'),
            'errors' => Session::pullFlash('errors', []),
            'canManage' => $role !== null && (Gate::allows($role, Permission::CreatePurchaseOrder) || Gate::allows($role, Permission::ProposePurchaseOrder)),
            'canReceive' => $role !== null && Gate::allows($role, Permission::ProcessGoodsReceipt),
        ]);
    }

    /** @param array<string, string> $params */
    public function markOrdered(array $params): void
    {
        $this->authorizeAny(Permission::CreatePurchaseOrder, Permission::ProposePurchaseOrder);

        $id = (int) $params['id'];

        try {
            $this->service()->markOrdered($id);
            Session::flash('success', 'Purchase Order dikirim ke supplier (status: Ordered).');
        } catch (ForbiddenOperationException $e) {
            Session::flash('errors', [$e->getMessage()]);
        }

        $this->redirect("/purchase-orders/{$id}");
    }

    /** @param array<string, string> $params */
    public function cancel(array $params): void
    {
        $this->authorizeAny(Permission::CreatePurchaseOrder, Permission::ProposePurchaseOrder);

        $id = (int) $params['id'];

        try {
            $this->service()->cancel($id);
            Session::flash('success', 'Purchase Order dibatalkan.');
        } catch (ForbiddenOperationException $e) {
            Session::flash('errors', [$e->getMessage()]);
        }

        $this->redirect("/purchase-orders/{$id}");
    }

    /** @param array<string, string> $params */
    public function receive(array $params): void
    {
        $this->authorize(Permission::ProcessGoodsReceipt);

        $id = (int) $params['id'];
        $receiptQuantities = $_POST['receive'] ?? [];

        try {
            $this->service()->receiveGoods($id, is_array($receiptQuantities) ? $receiptQuantities : [], (int) $this->currentUser()['id']);
            Session::flash('success', 'Goods receipt berhasil diproses.');
        } catch (ValidationException $e) {
            Session::flash('errors', array_values($e->errors()));
        } catch (ForbiddenOperationException $e) {
            Session::flash('errors', [$e->getMessage()]);
        }

        $this->redirect("/purchase-orders/{$id}");
    }

    /** @return list<array{product_id: mixed, qty_ordered: mixed, buy_price: mixed}> */
    private function parseItemsFromPost(): array
    {
        $productIds = $_POST['items']['product_id'] ?? [];
        $qtys = $_POST['items']['qty_ordered'] ?? [];
        $prices = $_POST['items']['buy_price'] ?? [];

        $items = [];

        foreach ($productIds as $index => $productId) {
            if ((string) $productId === '') {
                continue; // blank template row the user didn't fill in
            }

            $items[] = [
                'product_id' => $productId,
                'qty_ordered' => $qtys[$index] ?? null,
                'buy_price' => $prices[$index] ?? null,
            ];
        }

        return $items;
    }

    private function service(): PurchaseOrderService
    {
        $pdo = Database::connection();

        return new PurchaseOrderService(
            new MysqlPurchaseOrderRepository($pdo),
            new MysqlPurchaseOrderItemRepository($pdo),
            new MysqlProductStockRepository($pdo),
            new MysqlStockLedgerRepository($pdo),
            new MysqlSupplierRepository($pdo),
            new MysqlWarehouseRepository($pdo),
            new MysqlProductRepository($pdo),
            new PdoTransactionManager($pdo),
        );
    }

    private function supplierService(): SupplierService
    {
        return new SupplierService(new MysqlSupplierRepository(Database::connection()));
    }

    private function warehouseService(): WarehouseService
    {
        return new WarehouseService(new MysqlWarehouseRepository(Database::connection()));
    }

    private function productService(): ProductService
    {
        $pdo = Database::connection();

        return new ProductService(
            new MysqlProductRepository($pdo),
            new MysqlCategoryRepository($pdo),
            new MysqlProductStockRepository($pdo),
            new ProductImageUploader(uploadDir: __DIR__ . '/../../public/uploads/products')
        );
    }
}
