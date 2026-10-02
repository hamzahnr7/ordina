<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\PurchaseOrderStatus;
use App\Domain\Role;
use App\Domain\SalesOrderStatus;
use App\Entity\Category;
use App\Entity\Customer;
use App\Entity\Product;
use App\Entity\PurchaseOrder;
use App\Entity\PurchaseOrderItem;
use App\Entity\SalesOrder;
use App\Entity\SalesOrderItem;
use App\Entity\Supplier;
use App\Entity\User;
use App\Entity\Warehouse;
use PHPUnit\Framework\TestCase;

/**
 * fromArray() is how every Mysql repository turns a PDO row (all values come
 * back as strings) into a typed entity - these pin the casts and the
 * NULL-column handling without needing a database.
 */
final class EntityHydrationTest extends TestCase
{
    public function test_category_from_row_with_and_without_description(): void
    {
        $category = Category::fromArray(['id' => '3', 'name' => 'Stationery', 'description' => 'Alat tulis']);
        self::assertSame(3, $category->id);
        self::assertSame('Stationery', $category->name);
        self::assertSame('Alat tulis', $category->description);

        self::assertNull(Category::fromArray(['id' => '4', 'name' => 'X', 'description' => null])->description);
    }

    public function test_customer_from_row(): void
    {
        $customer = Customer::fromArray(['id' => '1', 'name' => 'Toko Maju', 'contact' => 'a@b.test', 'address' => 'Jakarta', 'is_active' => '1']);
        self::assertSame(1, $customer->id);
        self::assertSame('a@b.test', $customer->contact);
        self::assertSame('Jakarta', $customer->address);
        self::assertTrue($customer->isActive);

        $bare = Customer::fromArray(['id' => '2', 'name' => 'Tanpa Kontak', 'contact' => null, 'address' => null, 'is_active' => '0']);
        self::assertNull($bare->contact);
        self::assertNull($bare->address);
        self::assertFalse($bare->isActive);
    }

    public function test_supplier_from_row(): void
    {
        $supplier = Supplier::fromArray(['id' => '5', 'name' => 'PT Kertas', 'contact' => '0812', 'address' => 'Bandung', 'is_active' => 1]);
        self::assertSame(5, $supplier->id);
        self::assertSame('0812', $supplier->contact);
        self::assertSame('Bandung', $supplier->address);
        self::assertTrue($supplier->isActive);

        $bare = Supplier::fromArray(['id' => '6', 'name' => 'X', 'contact' => null, 'address' => null, 'is_active' => 0]);
        self::assertNull($bare->contact);
        self::assertNull($bare->address);
        self::assertFalse($bare->isActive);
    }

    public function test_warehouse_from_row(): void
    {
        $warehouse = Warehouse::fromArray(['id' => '2', 'name' => 'Surabaya', 'location' => 'Jl. Raya', 'is_active' => '1']);
        self::assertSame(2, $warehouse->id);
        self::assertSame('Surabaya', $warehouse->name);
        self::assertSame('Jl. Raya', $warehouse->location);
        self::assertTrue($warehouse->isActive);
    }

    public function test_product_from_row_casts_numeric_strings(): void
    {
        $product = Product::fromArray([
            'id' => '7', 'sku' => 'SKU-0007', 'name' => 'Laptop Stand', 'category_id' => '1', 'unit' => 'pcs',
            'buy_price' => '95000.00', 'sell_price' => '159000.00', 'reorder_point' => '12',
            'image_path' => 'uploads/products/a.png', 'is_active' => '1',
        ]);

        self::assertSame(7, $product->id);
        self::assertSame(1, $product->categoryId);
        self::assertSame(95000.0, $product->buyPrice);
        self::assertSame(159000.0, $product->sellPrice);
        self::assertSame(12, $product->reorderPoint);
        self::assertSame('uploads/products/a.png', $product->imagePath);
        self::assertTrue($product->isActive);

        $noImage = Product::fromArray([
            'id' => '8', 'sku' => 'S', 'name' => 'N', 'category_id' => '1', 'unit' => 'pcs',
            'buy_price' => '0', 'sell_price' => '0', 'reorder_point' => '0', 'image_path' => null, 'is_active' => '0',
        ]);
        self::assertNull($noImage->imagePath);
        self::assertFalse($noImage->isActive);
    }

    public function test_purchase_order_from_row(): void
    {
        $po = PurchaseOrder::fromArray([
            'id' => '11', 'supplier_id' => '2', 'warehouse_id' => '1',
            'status' => 'PartiallyReceived', 'order_date' => '2026-09-01', 'created_by' => '4',
        ]);

        self::assertSame(11, $po->id);
        self::assertSame(2, $po->supplierId);
        self::assertSame(1, $po->warehouseId);
        self::assertSame(PurchaseOrderStatus::PartiallyReceived, $po->status);
        self::assertSame('2026-09-01', $po->orderDate);
        self::assertSame(4, $po->createdBy);
    }

    public function test_purchase_order_item_from_row_and_remaining_quantity(): void
    {
        $item = PurchaseOrderItem::fromArray([
            'id' => '1', 'purchase_order_id' => '5', 'product_id' => '3',
            'qty_ordered' => '25', 'qty_received' => '10', 'buy_price' => '45000.00',
        ]);

        self::assertSame(5, $item->purchaseOrderId);
        self::assertSame(3, $item->productId);
        self::assertSame(45000.0, $item->buyPrice);
        self::assertSame(15, $item->remaining());
        self::assertFalse($item->isFullyReceived());

        $full = new PurchaseOrderItem(2, 5, 3, qtyOrdered: 10, qtyReceived: 10, buyPrice: 1);
        self::assertSame(0, $full->remaining());
        self::assertTrue($full->isFullyReceived());
    }

    public function test_sales_order_from_row_with_and_without_approver(): void
    {
        $approved = SalesOrder::fromArray([
            'id' => '3', 'customer_id' => '1', 'warehouse_id' => '1',
            'status' => 'Fulfilled', 'created_by' => '2', 'approved_by' => '1',
        ]);
        self::assertSame(3, $approved->id);
        self::assertSame(1, $approved->customerId);
        self::assertSame(SalesOrderStatus::Fulfilled, $approved->status);
        self::assertSame(2, $approved->createdBy);
        self::assertSame(1, $approved->approvedBy);

        $draft = SalesOrder::fromArray([
            'id' => '4', 'customer_id' => '1', 'warehouse_id' => '2',
            'status' => 'Draft', 'created_by' => '3', 'approved_by' => null,
        ]);
        self::assertNull($draft->approvedBy);
    }

    public function test_sales_order_item_from_row(): void
    {
        $item = SalesOrderItem::fromArray(['id' => '9', 'sales_order_id' => '3', 'product_id' => '13', 'qty' => '5', 'sell_price' => '75000.00']);

        self::assertSame(9, $item->id);
        self::assertSame(3, $item->salesOrderId);
        self::assertSame(13, $item->productId);
        self::assertSame(5, $item->qty);
        self::assertSame(75000.0, $item->sellPrice);
    }

    public function test_user_from_row_and_session_array_never_contains_password_hash(): void
    {
        $user = User::fromArray([
            'id' => '4', 'name' => 'Gudang Satu', 'email' => 'wh1@ordina.test',
            'password_hash' => '$2y$10$secret', 'role' => 'WarehouseStaff', 'is_active' => '1',
        ]);

        self::assertSame(4, $user->id);
        self::assertSame(Role::WarehouseStaff, $user->role);
        self::assertTrue($user->isActive);
        self::assertSame(
            ['id' => 4, 'name' => 'Gudang Satu', 'email' => 'wh1@ordina.test', 'role' => 'WarehouseStaff'],
            $user->toSessionArray()
        );
        self::assertNotContains('$2y$10$secret', $user->toSessionArray());
    }
}
