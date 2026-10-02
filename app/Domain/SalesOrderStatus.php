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

    /** CSS badge class for list/detail pages - one place instead of a match() per view. */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'badge-muted',
            self::Cancelled => 'badge-danger',
            self::PendingApproval, self::Approved, self::Fulfilled => 'badge-success',
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
