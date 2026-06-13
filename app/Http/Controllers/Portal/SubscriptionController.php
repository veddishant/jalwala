<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\PauseSubscriptionRequest;
use App\Models\Customer;
use App\Models\Subscription;
use App\Models\SubscriptionSchedule;
use App\Services\SubscriptionPauseService;
use App\Services\SubscriptionService;
use App\SubscriptionStatus;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService,
        private SubscriptionPauseService $pauseService,
    ) {}

    public function show(Request $request): Response
    {
        $customer = $this->resolveCustomer($request);

        $subscription = Subscription::query()
            ->where('customer_id', $customer->id)
            ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Paused])
            ->with(['address', 'items.product', 'schedules', 'pauses'])
            ->orderByDesc('id')
            ->first();

        if ($subscription !== null) {
            $this->authorize('view', $subscription);
        }

        return Inertia::render('portal/subscription', [
            'subscription' => $subscription ? [
                'id' => $subscription->id,
                'status' => $subscription->status->value,
                'status_label' => $subscription->status->label(),
                'start_date' => $subscription->start_date->toDateString(),
                'paused_until' => $subscription->paused_until?->toDateString(),
                'notes' => $subscription->notes,
                'address' => [
                    'label' => $subscription->address?->label,
                    'address_line_1' => $subscription->address?->address_line_1,
                    'city' => $subscription->address?->city,
                ],
                'items' => $subscription->items->map(fn ($item): array => [
                    'product_name' => $item->product?->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                ])->values()->all(),
                'schedule_days' => $subscription->schedules
                    ->map(fn ($schedule) => SubscriptionSchedule::dayLabel($schedule->day_of_week))
                    ->values()
                    ->all(),
                'pauses' => $subscription->pauses->map(fn ($pause): array => [
                    'start_date' => $pause->start_date->toDateString(),
                    'end_date' => $pause->end_date->toDateString(),
                    'reason' => $pause->reason,
                ])->values()->all(),
            ] : null,
            'upcomingDeliveries' => $subscription
                ? $this->subscriptionService->upcomingDeliveryDates($subscription)
                : [],
            'can' => [
                'pause' => $subscription
                    ? ($request->user()?->can('pause', $subscription) ?? false)
                    : false,
                'resume' => $subscription
                    ? ($request->user()?->can('resume', $subscription) ?? false)
                    : false,
            ],
        ]);
    }

    public function pause(PauseSubscriptionRequest $request): RedirectResponse
    {
        $customer = $this->resolveCustomer($request);

        $subscription = Subscription::query()
            ->where('customer_id', $customer->id)
            ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Paused])
            ->orderByDesc('id')
            ->firstOrFail();

        $this->authorize('pause', $subscription);

        $this->pauseService->pause(
            subscription: $subscription,
            startDate: $request->validated('start_date'),
            endDate: $request->validated('end_date'),
            createdBy: (int) $request->user()->id,
            reason: $request->validated('reason'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Subscription paused for your vacation.')]);

        return back();
    }

    public function resume(Request $request): RedirectResponse
    {
        $customer = $this->resolveCustomer($request);

        $subscription = Subscription::query()
            ->where('customer_id', $customer->id)
            ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Paused])
            ->orderByDesc('id')
            ->firstOrFail();

        $this->authorize('resume', $subscription);

        $this->pauseService->resume($subscription);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Subscription resumed.')]);

        return back();
    }

    private function resolveCustomer(Request $request): Customer
    {
        TenantContext::resolveFromAuthenticatedUser($request->user());

        $customer = $request->user()?->customer;

        if ($customer === null) {
            abort(404);
        }

        return $customer;
    }
}
