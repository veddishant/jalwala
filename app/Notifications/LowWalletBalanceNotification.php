<?php

namespace App\Notifications;

use App\Models\Wallet;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LowWalletBalanceNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Wallet $wallet,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'wallet_id' => $this->wallet->id,
            'balance' => $this->wallet->balance,
            'threshold' => $this->wallet->low_balance_threshold,
            'message' => 'Your wallet balance is below the alert threshold.',
        ];
    }
}
