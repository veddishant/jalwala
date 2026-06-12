<?php

namespace App;

enum WalletTransactionType: string
{
    case Credit = 'credit';
    case Debit = 'debit';
}
