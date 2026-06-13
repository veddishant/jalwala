<?php

namespace App;

enum InventoryMovementType: string
{
    case FilledIn = 'filled_in';
    case FilledOut = 'filled_out';
    case EmptyIn = 'empty_in';
    case EmptyOut = 'empty_out';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::FilledIn => 'Filled in',
            self::FilledOut => 'Filled out',
            self::EmptyIn => 'Empty in',
            self::EmptyOut => 'Empty out',
            self::Adjustment => 'Adjustment',
        };
    }
}
