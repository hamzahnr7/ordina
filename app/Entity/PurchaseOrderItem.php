<?php

declare(strict_types=1);

namespace App\Entity;

final class PurchaseOrderItem
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $purchaseOrderId,
        public readonly int $productId,
        public readonly int $qtyOrdered,
        public readonly int $qtyReceived,
        public readonly float $buyPrice,
    ) {
    }

    public function remaining(): int
    {
        return $this->qtyOrdered - $this->qtyReceived;
    }

    public function isFullyReceived(): bool
    {
        return $this->qtyReceived >= $this->qtyOrdered;
    }

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            purchaseOrderId: (int) $row['purchase_order_id'],
            productId: (int) $row['product_id'],
            qtyOrdered: (int) $row['qty_ordered'],
            qtyReceived: (int) $row['qty_received'],
            buyPrice: (float) $row['buy_price'],
        );
    }
}
