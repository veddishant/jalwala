<?php

namespace App;

enum InquiryType: string
{
    case Supplier = 'supplier';
    case Tenant = 'tenant';
    case Bug = 'bug';
    case Suggestion = 'suggestion';
    case General = 'general';

    public function label(): string
    {
        return match ($this) {
            self::Supplier => 'Become a supplier',
            self::Tenant => 'New tenant / partnership',
            self::Bug => 'Report a bug',
            self::Suggestion => 'Feature suggestion',
            self::General => 'General inquiry',
        };
    }
}
