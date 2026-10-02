<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Domain\SalesOrderStatus;
use App\Service\Exception\ForbiddenOperationException;
use PDOException;

/**
 * SO approval segregation of duties, enforced twice: SalesOrderService gives
 * the clean message, and chk_so_approver_not_creator is the database-level
 * backstop even if some future code path skips the service.
 */
final class SegregationOfDutiesIntegrationTest extends DatabaseTestCase
{
    public function test_service_refuses_creator_approving_own_sales_order(): void
    {
        $service = $this->salesOrderService();
        $so = $service->create(
            ['customer_id' => 1, 'warehouse_id' => self::WAREHOUSE_ID],
            [['product_id' => self::PRODUCT_ID, 'qty' => 1, 'sell_price' => 15000]],
            self::SALES_ID
        );
        $service->submit((int) $so->id);

        try {
            $service->approve((int) $so->id, self::SALES_ID);
            self::fail('Creator must not be able to approve their own Sales Order.');
        } catch (ForbiddenOperationException) {
        }

        $stored = $service->find((int) $so->id);
        self::assertNotNull($stored);
        self::assertSame(SalesOrderStatus::PendingApproval, $stored->status);
        self::assertNull($stored->approvedBy);
    }

    public function test_database_check_constraint_rejects_self_approval_written_directly(): void
    {
        $this->pdo->exec(
            "INSERT INTO sales_orders (customer_id, warehouse_id, status, created_by) VALUES (1, 1, 'PendingApproval', " . self::SALES_ID . ')'
        );
        $soId = (int) $this->pdo->lastInsertId();

        $this->expectException(PDOException::class);

        $this->pdo->prepare("UPDATE sales_orders SET status = 'Approved', approved_by = :approver WHERE id = :id")
            ->execute(['approver' => self::SALES_ID, 'id' => $soId]);
    }
}
