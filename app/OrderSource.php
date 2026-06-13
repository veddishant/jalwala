<?php

namespace App;

enum OrderSource: string
{
    case Manual = 'manual';
    case Subscription = 'subscription';
    case CustomerPortal = 'customer_portal';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Subscription => 'Subscription',
            self::CustomerPortal => 'Customer portal',
        };
    }
}
