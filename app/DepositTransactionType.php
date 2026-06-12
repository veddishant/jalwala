<?php

namespace App;

enum DepositTransactionType: string
{
    case Collect = 'collect';
    case Refund = 'refund';
    case Adjustment = 'adjustment';
}
