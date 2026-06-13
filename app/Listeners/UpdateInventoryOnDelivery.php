<?php

namespace App\Listeners;

use App\Events\OrderDelivered;
use App\Services\InventoryService;

class UpdateInventoryOnDelivery
{
    public function __construct(
        private InventoryService $inventoryService,
    ) {}

    public function handle(OrderDelivered $event): void
    {
        $this->inventoryService->transferForDelivery(
            order: $event->order,
            emptiesCollected: $event->emptiesCollected,
            createdBy: $event->changedBy,
        );
    }
}
