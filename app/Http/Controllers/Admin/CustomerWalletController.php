<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustWalletRequest;
use App\Http\Requests\Admin\TopUpWalletRequest;
use App\Http\Requests\Admin\UpdateWalletThresholdRequest;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use App\Support\TenantContext;
use App\TenantStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerWalletController extends Controller
{
    public function __construct(
        private WalletService $walletService,
    ) {}

    private function ensureTenantContext(Request $request): void
    {
        TenantContext::resolveFromAuthenticatedUser($request->user());

        if (TenantContext::getId() !== null || TenantContext::isBypassed()) {
            return;
        }

        $user = $request->user();

        if ($user?->hasRole('super-admin') && $request->hasSession()) {
            try {
                $activeTenantId = $request->session()->get('active_tenant_id');

                if ($activeTenantId !== null) {
                    TenantContext::setId((int) $activeTenantId);

                    return;
                }
            } catch (\RuntimeException) {
                //
            }
        }

        if ($user?->hasRole('super-admin') && TenantContext::getId() === null) {
            $activeTenantCount = Tenant::query()
                ->where('status', TenantStatus::Active)
                ->count();

            if ($activeTenantCount === 1) {
                $tenantId = Tenant::query()
                    ->where('status', TenantStatus::Active)
                    ->orderBy('id')
                    ->value('id');

                if ($tenantId !== null) {
                    TenantContext::setId((int) $tenantId);
                }
            }
        }
    }

    private function currentTenantId(Request $request): int
    {
        $this->ensureTenantContext($request);

        $tenantId = TenantContext::getId() ?? $request->user()?->tenant_id;

        if ($tenantId === null) {
            abort(403, 'Tenant context is required.');
        }

        return (int) $tenantId;
    }

    /**
     * @return Builder<Customer>
     */
    private function tenantCustomersQuery(Request $request): Builder
    {
        return Customer::query()->where('tenant_id', $this->currentTenantId($request));
    }

    public function show(Request $request, int $managedCustomer): Response
    {
        $customer = $this->tenantCustomersQuery($request)->findOrFail($managedCustomer);

        $wallet = $this->walletService->ensureForCustomer($customer);

        $this->authorize('view', $wallet);

        $transactions = $wallet->transactions()
            ->with('createdBy:id,name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (WalletTransaction $transaction): array => $this->transformTransaction($transaction));

        return Inertia::render('admin/customers/wallet', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'code' => $customer->code,
            ],
            'wallet' => $this->transformWallet($wallet),
            'transactions' => $transactions,
            'can' => [
                'topUp' => $request->user()?->can('topUp', $wallet) ?? false,
                'adjust' => $request->user()?->can('adjust', $wallet) ?? false,
                'viewLedger' => $request->user()?->can('viewLedger', $wallet) ?? false,
            ],
        ]);
    }

    public function topUp(TopUpWalletRequest $request, int $managedCustomer): RedirectResponse
    {
        $customer = $this->tenantCustomersQuery($request)->findOrFail($managedCustomer);
        $wallet = $this->walletService->ensureForCustomer($customer);

        $this->authorize('topUp', $wallet);

        $this->walletService->topUp(
            wallet: $wallet,
            amount: number_format((float) $request->validated('amount'), 2, '.', ''),
            description: $request->validated('description'),
            createdBy: (int) $request->user()->id,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Wallet topped up successfully.')]);

        return back();
    }

    public function adjust(AdjustWalletRequest $request, int $managedCustomer): RedirectResponse
    {
        $customer = $this->tenantCustomersQuery($request)->findOrFail($managedCustomer);
        $wallet = $this->walletService->ensureForCustomer($customer);

        $this->authorize('adjust', $wallet);

        $this->walletService->adjust(
            wallet: $wallet,
            amount: number_format((float) $request->validated('amount'), 2, '.', ''),
            direction: $request->validated('direction'),
            reason: $request->validated('reason'),
            createdBy: (int) $request->user()->id,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Wallet adjusted successfully.')]);

        return back();
    }

    public function updateThreshold(UpdateWalletThresholdRequest $request, int $managedCustomer): RedirectResponse
    {
        $customer = $this->tenantCustomersQuery($request)->findOrFail($managedCustomer);
        $wallet = $this->walletService->ensureForCustomer($customer);

        $this->authorize('updateThreshold', $wallet);

        $threshold = $request->validated('low_balance_threshold');

        $wallet->update([
            'low_balance_threshold' => $threshold !== null
                ? number_format((float) $threshold, 2, '.', '')
                : null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Low balance threshold updated.')]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function transformWallet(Wallet $wallet): array
    {
        return [
            'id' => $wallet->id,
            'balance' => $wallet->balance,
            'low_balance_threshold' => $wallet->low_balance_threshold,
            'is_below_threshold' => $wallet->isBelowThreshold(),
            'is_negative' => bccomp($wallet->balance, '0', 2) < 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transformTransaction(WalletTransaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'type' => $transaction->type->value,
            'category' => $transaction->category->value,
            'amount' => $transaction->amount,
            'balance_after' => $transaction->balance_after,
            'description' => $transaction->description,
            'created_by' => $transaction->createdBy?->name,
            'created_at' => $transaction->created_at?->toISOString(),
        ];
    }
}
