<?php

declare(strict_types=1);

namespace App\Domain;

/** Mirrors purchase_orders.status ENUM (database/schema-and-seed.sql) - brief §1.3/PO-01. */
enum PurchaseOrderStatus: string
{
    case Draft = 'Draft';
    case Ordered = 'Ordered';
    case PartiallyReceived = 'PartiallyReceived';
    case Received = 'Received';
    case Cancelled = 'Cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Ordered => 'Ordered',
            self::PartiallyReceived => 'Partially Received',
            self::Received => 'Received',
            self::Cancelled => 'Cancelled',
        };
    }

    /** CSS badge class for list/detail pages - one place instead of a match() per view. */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'badge-muted',
            self::Cancelled => 'badge-danger',
            self::Ordered, self::PartiallyReceived, self::Received => 'badge-success',
        };
    }

    public function canBeCancelled(): bool
    {
        return $this !== self::Received && $this !== self::Cancelled;
    }

    public function canReceiveGoods(): bool
    {
        return $this === self::Ordered || $this === self::PartiallyReceived;
    }
}
