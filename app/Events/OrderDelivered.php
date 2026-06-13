<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderDelivered
{
    use Dispatchable, SerializesModels;

    /**
     * @param  list<array{product_id: int, quantity: int}>  $emptiesCollected
     */
    public function __construct(
        public Order $order,
        public array $emptiesCollected = [],
        public ?int $changedBy = null,
    ) {}
}
