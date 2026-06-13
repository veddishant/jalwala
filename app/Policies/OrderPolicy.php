<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use App\OrderStatus;
use App\Support\TenantContext;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('orders.view');
    }

    public function view(User $user, Order $order): bool
    {
        if (! $user->can('orders.view')) {
            return false;
        }

        if ($user->hasRole('customer')) {
            return $order->customer?->user_id === $user->id;
        }

        return $this->sameTenant($user, $order);
    }

    public function create(User $user): bool
    {
        return $user->can('orders.create');
    }

    public function confirm(User $user, Order $order): bool
    {
        return $user->can('orders.confirm')
            && $this->sameTenant($user, $order)
            && $order->status === OrderStatus::Draft;
    }

    public function cancel(User $user, Order $order): bool
    {
        if (! $user->can('orders.cancel') || ! $order->status->isCancellable()) {
            return false;
        }

        if ($user->hasRole('customer')) {
            return $order->customer?->user_id === $user->id
                && in_array($order->status, [OrderStatus::Draft, OrderStatus::Pending], true);
        }

        return $this->sameTenant($user, $order);
    }

    public function transition(User $user, Order $order): bool
    {
        return $user->can('orders.update')
            && $this->sameTenant($user, $order)
            && ! $order->status->isTerminal();
    }

    private function sameTenant(User $user, Order $order): bool
    {
        if ($user->isSuperAdmin() && TenantContext::getId() !== null) {
            return $order->tenant_id === TenantContext::getId();
        }

        if ($user->tenant_id === null) {
            return false;
        }

        return $order->tenant_id === $user->tenant_id;
    }
}
