<?php

declare(strict_types=1);

namespace App\Entity;

final class SalesOrderItem
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $salesOrderId,
        public readonly int $productId,
        public readonly int $qty,
        public readonly float $sellPrice,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            salesOrderId: (int) $row['sales_order_id'],
            productId: (int) $row['product_id'],
            qty: (int) $row['qty'],
            sellPrice: (float) $row['sell_price'],
        );
    }
}
