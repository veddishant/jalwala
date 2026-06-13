<?php

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;
use App\SubscriptionStatus;
use App\Support\TenantContext;

class SubscriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('subscriptions.view');
    }

    public function view(User $user, Subscription $subscription): bool
    {
        if (! $user->can('subscriptions.view')) {
            return false;
        }

        if ($user->hasRole('customer')) {
            return $subscription->customer?->user_id === $user->id;
        }

        return $this->sameTenant($user, $subscription);
    }

    public function create(User $user): bool
    {
        return $user->can('subscriptions.create') && $this->hasTenantContext($user);
    }

    public function update(User $user, Subscription $subscription): bool
    {
        return $user->can('subscriptions.update')
            && $this->sameTenant($user, $subscription)
            && $subscription->status->isManageable();
    }

    public function pause(User $user, Subscription $subscription): bool
    {
        if (! $user->can('subscriptions.pause') || ! $subscription->isPausable()) {
            return false;
        }

        if ($user->hasRole('customer')) {
            return $subscription->customer?->user_id === $user->id;
        }

        return $this->sameTenant($user, $subscription);
    }

    public function resume(User $user, Subscription $subscription): bool
    {
        if (! $user->can('subscriptions.resume') || ! $subscription->isResumable()) {
            return false;
        }

        if ($user->hasRole('customer')) {
            return $subscription->customer?->user_id === $user->id;
        }

        return $this->sameTenant($user, $subscription);
    }

    public function cancel(User $user, Subscription $subscription): bool
    {
        return $user->can('subscriptions.cancel')
            && $this->sameTenant($user, $subscription)
            && $subscription->status !== SubscriptionStatus::Cancelled;
    }

    private function sameTenant(User $user, Subscription $subscription): bool
    {
        if ($user->isSuperAdmin() && TenantContext::getId() !== null) {
            return $subscription->tenant_id === TenantContext::getId();
        }

        if ($user->tenant_id === null) {
            return false;
        }

        return $subscription->tenant_id === $user->tenant_id;
    }

    private function hasTenantContext(User $user): bool
    {
        if ($user->tenant_id !== null) {
            return true;
        }

        return $user->isSuperAdmin() && TenantContext::getId() !== null;
    }
}
