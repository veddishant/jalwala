<?php

namespace App;

enum WalletTransactionCategory: string
{
    case OpeningBalance = 'opening_balance';
    case TopUp = 'top_up';
    case OrderPayment = 'order_payment';
    case Refund = 'refund';
    case Adjustment = 'adjustment';
}
