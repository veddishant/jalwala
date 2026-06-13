<?php

namespace App;

enum SubscriptionStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Paused => 'Paused',
            self::Cancelled => 'Cancelled',
        };
    }

    public function isManageable(): bool
    {
        return in_array($this, [self::Active, self::Paused], true);
    }
}
