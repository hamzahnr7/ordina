<?php

declare(strict_types=1);

namespace App\Domain;

/** Mirrors sales_orders.status ENUM (database/schema-and-seed.sql) - brief §1.3/SO-01. */
enum SalesOrderStatus: string
{
    case Draft = 'Draft';
    case PendingApproval = 'PendingApproval';
    case Approved = 'Approved';
    case Fulfilled = 'Fulfilled';
    case Cancelled = 'Cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::PendingApproval => 'Pending Approval',
            self::Approved => 'Approved',
            self::Fulfilled => 'Fulfilled',
            self::Cancelled => 'Cancelled',
        };
    }

    public function canBeSubmitted(): bool
    {
        return $this === self::Draft;
    }

    public function canBeDecided(): bool
    {
        return $this === self::PendingApproval;
    }

    public function canBeCancelled(): bool
    {
        return $this !== self::Fulfilled && $this !== self::Cancelled;
    }

    public function canIssueGoods(): bool
    {
        return $this === self::Approved;
    }
}
