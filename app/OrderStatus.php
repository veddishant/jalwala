<?php

namespace App;

enum OrderStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Assigned = 'assigned';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Pending => 'Pending',
            self::Assigned => 'Assigned',
            self::OutForDelivery => 'Out for delivery',
            self::Delivered => 'Delivered',
            self::Failed => 'Failed',
            self::Cancelled => 'Cancelled',
            self::Completed => 'Completed',
        };
    }

    public function isCancellable(): bool
    {
        return in_array($this, [
            self::Draft,
            self::Pending,
            self::Assigned,
            self::OutForDelivery,
        ], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::Cancelled,
            self::Completed,
        ], true);
    }
}
