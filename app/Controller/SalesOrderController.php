<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Authorization\Gate;
use App\Core\Authorization\Permission;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Http\Exceptions\AuthorizationException;
use App\Core\Http\Exceptions\NotFoundException;
use App\Core\Session;
use App\Core\Transaction\PdoTransactionManager;
use App\Domain\Role;
use App\Domain\SalesOrderStatus;
use App\Entity\SalesOrder;
use App\Repository\Mysql\MysqlCategoryRepository;
use App\Repository\Mysql\MysqlCustomerRepository;
use App\Repository\Mysql\MysqlProductRepository;
use App\Repository\Mysql\MysqlProductStockRepository;
use App\Repository\Mysql\MysqlSalesOrderItemRepository;
use App\Repository\Mysql\MysqlSalesOrderRepository;
use App\Repository\Mysql\MysqlStockLedgerRepository;
use App\Repository\Mysql\MysqlWarehouseRepository;
use App\Service\CustomerService;
use App\Service\Exception\ForbiddenOperationException;
use App\Service\Exception\ValidationException;
use App\Service\ProductImageUploader;
use App\Service\ProductService;
use App\Service\SalesOrderService;
use App\Service\WarehouseService;

/**
 * SO-01. Sales only ever touches their own orders (§1.2 "milik sendiri") -
 * enforced by assertOwnsOrAdmin() below, separate from the Permission-level
 * authorize() calls which only establish *what kind* of action a role may
 * attempt at all.
 */
final class SalesOrderController extends Controller
{
    public function index(): void
    {
        $this->authorizeAny(Permission::CreateSalesOrder, Permission::ApproveSalesOrder, Permission::ProcessGoodsIssue);

        $validStatuses = array_map(static fn (SalesOrderStatus $s) => $s->value, SalesOrderStatus::cases());

        $filters = array_filter([
            'search' => trim((string) ($_GET['search'] ?? '')),
            'status' => in_array($_GET['status'] ?? '', $validStatuses, true) ? $_GET['status'] : null,
        ]);
        $sortDir = ($_GET['sort'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $role = $this->currentRole();
        $ownerId = $role === Role::Sales ? (int) $this->currentUser()['id'] : null;

        $this->view('sales-orders/index', [
            'title' => 'Sales Order',
            'result' => $this->service()->paginate($filters, $ownerId, $sortDir, $page),
            'filters' => $filters,
            'sortDir' => $sortDir,
            'statuses' => SalesOrderStatus::cases(),
            'success' => Session::pullFlash('success'),
            'canCreate' => $role !== null && Gate::allows($role, Permission::CreateSalesOrder),
        ]);
    }

    public function create(): void
    {
        $this->authorize(Permission::CreateSalesOrder);

        $this->view('sales-orders/create', [
            'title' => 'Buat Sales Order',
            'customers' => $this->customerService()->listActive(),
            'warehouses' => $this->warehouseService()->listActive(),
            'products' => $this->productService()->listActive(),
            'errors' => Session::pullFlash('errors', []),
            'old' => Session::pullFlash('old', []),
        ]);
    }

    public function store(): void
    {
        $this->authorize(Permission::CreateSalesOrder);

        try {
            $so = $this->service()->create($_POST, $this->parseItemsFromPost(), (int) $this->currentUser()['id']);
        } catch (ValidationException $e) {
            Session::flash('errors', $e->errors());
            Session::flash('old', $_POST);
            $this->redirect('/sales-orders/create');
        }

        Session::flash('success', 'Sales Order berhasil dibuat sebagai Draft.');
        $this->redirect("/sales-orders/{$so->id}");
    }

    /** @param array<string, string> $params */
    public function show(array $params): void
    {
        $this->authorizeAny(Permission::CreateSalesOrder, Permission::ApproveSalesOrder, Permission::ProcessGoodsIssue);

        $detail = $this->service()->detail((int) $params['id']);

        if ($detail === null) {
            throw new NotFoundException();
        }

        $this->assertOwnsOrAdmin($detail['salesOrder']);

        /** @var SalesOrder $so */
        $so = $detail['salesOrder'];
        $role = $this->currentRole();
        $isAdmin = $role === Role::Admin;
        $isOwner = $so->createdBy === (int) $this->currentUser()['id'];

        $this->view('sales-orders/show', [
            'title' => 'Detail Sales Order',
            ...$detail,
            'success' => Session::pullFlash('success'),
            'errors' => Session::pullFlash('errors', []),
            // One boolean per button - keeps role/ownership logic out of the view.
            'canSubmit' => $so->status->canBeSubmitted() && ($isOwner || $isAdmin),
            'canApproveOrReject' => $so->status->canBeDecided() && $isAdmin && !$isOwner,
            'canCancel' => $so->status->canBeCancelled() && ($isAdmin || ($isOwner && $so->status !== SalesOrderStatus::Approved)),
            'canIssue' => $so->status->canIssueGoods() && $role !== null && Gate::allows($role, Permission::ProcessGoodsIssue),
        ]);
    }

    /** @param array<string, string> $params */
    public function submit(array $params): void
    {
        $this->authorize(Permission::CreateSalesOrder);

        $id = (int) $params['id'];
        $so = $this->service()->find($id);

        if ($so === null) {
            throw new NotFoundException();
        }

        $this->assertOwnsOrAdmin($so);

        try {
            $this->service()->submit($id);
            Session::flash('success', 'Sales Order diajukan untuk approval.');
        } catch (ForbiddenOperationException $e) {
            Session::flash('errors', [$e->getMessage()]);
        }

        $this->redirect("/sales-orders/{$id}");
    }

    /** @param array<string, string> $params */
    public function approve(array $params): void
    {
        $this->authorize(Permission::ApproveSalesOrder);

        $id = (int) $params['id'];

        try {
            $this->service()->approve($id, (int) $this->currentUser()['id']);
            Session::flash('success', 'Sales Order disetujui.');
        } catch (ForbiddenOperationException $e) {
            Session::flash('errors', [$e->getMessage()]);
        }

        $this->redirect("/sales-orders/{$id}");
    }

    /** @param array<string, string> $params */
    public function reject(array $params): void
    {
        $this->authorize(Permission::ApproveSalesOrder);

        $id = (int) $params['id'];

        try {
            $this->service()->reject($id);
            Session::flash('success', 'Sales Order ditolak.');
        } catch (ForbiddenOperationException $e) {
            Session::flash('errors', [$e->getMessage()]);
        }

        $this->redirect("/sales-orders/{$id}");
    }

    /** @param array<string, string> $params */
    public function cancel(array $params): void
    {
        $this->authorizeAny(Permission::CreateSalesOrder, Permission::ApproveSalesOrder);

        $id = (int) $params['id'];
        $so = $this->service()->find($id);

        if ($so === null) {
            throw new NotFoundException();
        }

        $this->assertOwnsOrAdmin($so);

        if ($this->currentRole() === Role::Sales && $so->status === SalesOrderStatus::Approved) {
            // Once approved, cancelling is an Admin-level decision.
            throw new AuthorizationException();
        }

        try {
            $this->service()->cancel($id);
            Session::flash('success', 'Sales Order dibatalkan.');
        } catch (ForbiddenOperationException $e) {
            Session::flash('errors', [$e->getMessage()]);
        }

        $this->redirect("/sales-orders/{$id}");
    }

    /** @param array<string, string> $params */
    public function issue(array $params): void
    {
        $this->authorize(Permission::ProcessGoodsIssue);

        $id = (int) $params['id'];

        try {
            $this->service()->processGoodsIssue($id, (int) $this->currentUser()['id']);
            Session::flash('success', 'Goods issue berhasil diproses. Sales Order Fulfilled.');
        } catch (ValidationException $e) {
            Session::flash('errors', array_values($e->errors()));
        } catch (ForbiddenOperationException $e) {
            Session::flash('errors', [$e->getMessage()]);
        }

        $this->redirect("/sales-orders/{$id}");
    }

    private function assertOwnsOrAdmin(SalesOrder $so): void
    {
        if ($this->currentRole() === Role::Sales && $so->createdBy !== (int) $this->currentUser()['id']) {
            throw new AuthorizationException();
        }
    }

    /** @return list<array{product_id: mixed, qty: mixed, sell_price: mixed}> */
    private function parseItemsFromPost(): array
    {
        $productIds = $_POST['items']['product_id'] ?? [];
        $qtys = $_POST['items']['qty'] ?? [];
        $prices = $_POST['items']['sell_price'] ?? [];

        $items = [];

        foreach ($productIds as $index => $productId) {
            if ((string) $productId === '') {
                continue;
            }

            $items[] = [
                'product_id' => $productId,
                'qty' => $qtys[$index] ?? null,
                'sell_price' => $prices[$index] ?? null,
            ];
        }

        return $items;
    }

    private function service(): SalesOrderService
    {
        $pdo = Database::connection();

        return new SalesOrderService(
            new MysqlSalesOrderRepository($pdo),
            new MysqlSalesOrderItemRepository($pdo),
            new MysqlProductStockRepository($pdo),
            new MysqlStockLedgerRepository($pdo),
            new MysqlCustomerRepository($pdo),
            new MysqlWarehouseRepository($pdo),
            new MysqlProductRepository($pdo),
            new PdoTransactionManager($pdo),
        );
    }

    private function customerService(): CustomerService
    {
        return new CustomerService(new MysqlCustomerRepository(Database::connection()));
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
