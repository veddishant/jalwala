<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustDepositRequest;
use App\Http\Requests\Admin\CollectDepositRequest;
use App\Http\Requests\Admin\RefundDepositRequest;
use App\Models\Customer;
use App\Models\CustomerDeposit;
use App\Models\DepositTransaction;
use App\Models\Product;
use App\Models\Tenant;
use App\Services\DepositService;
use App\Support\TenantContext;
use App\TenantStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerDepositController extends Controller
{
    public function __construct(
        private DepositService $depositService,
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

        $deposit = $this->depositService->ensureForCustomer($customer);

        $this->authorize('view', $deposit);

        $transactions = $deposit->transactions()
            ->with(['createdBy:id,name', 'product:id,name'])
            ->paginate(15)
            ->withQueryString()
            ->through(fn (DepositTransaction $transaction): array => $this->transformTransaction($transaction));

        return Inertia::render('admin/customers/deposits', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'code' => $customer->code,
            ],
            'deposit' => $this->transformDeposit($deposit),
            'jarSummary' => $this->depositService->jarSummaryByProduct($deposit),
            'products' => $this->depositService->returnableProductsForTenant($this->currentTenantId($request)),
            'transactions' => $transactions,
            'can' => [
                'collect' => $request->user()?->can('collect', $deposit) ?? false,
                'refund' => $request->user()?->can('refund', $deposit) ?? false,
                'adjust' => $request->user()?->can('adjust', $deposit) ?? false,
                'viewLedger' => $request->user()?->can('viewLedger', $deposit) ?? false,
            ],
        ]);
    }

    public function collect(CollectDepositRequest $request, int $managedCustomer): RedirectResponse
    {
        $customer = $this->tenantCustomersQuery($request)->findOrFail($managedCustomer);
        $deposit = $this->depositService->ensureForCustomer($customer);

        $this->authorize('collect', $deposit);

        $product = Product::query()->findOrFail($request->validated('product_id'));

        $amount = $request->validated('amount');

        $this->depositService->collect(
            deposit: $deposit,
            product: $product,
            jarCount: (int) $request->validated('jar_count'),
            createdBy: (int) $request->user()->id,
            amount: $amount !== null
                ? number_format((float) $amount, 2, '.', '')
                : null,
            description: $request->validated('description'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Deposit collected successfully.')]);

        return back();
    }

    public function refund(RefundDepositRequest $request, int $managedCustomer): RedirectResponse
    {
        $customer = $this->tenantCustomersQuery($request)->findOrFail($managedCustomer);
        $deposit = $this->depositService->ensureForCustomer($customer);

        $this->authorize('refund', $deposit);

        $amount = $request->validated('amount');

        $this->depositService->refund(
            deposit: $deposit,
            jarCount: (int) $request->validated('jar_count'),
            createdBy: (int) $request->user()->id,
            amount: $amount !== null
                ? number_format((float) $amount, 2, '.', '')
                : null,
            description: $request->validated('description'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Deposit refunded successfully.')]);

        return back();
    }

    public function adjust(AdjustDepositRequest $request, int $managedCustomer): RedirectResponse
    {
        $customer = $this->tenantCustomersQuery($request)->findOrFail($managedCustomer);
        $deposit = $this->depositService->ensureForCustomer($customer);

        $this->authorize('adjust', $deposit);

        $this->depositService->adjust(
            deposit: $deposit,
            amount: number_format((float) $request->validated('amount'), 2, '.', ''),
            direction: $request->validated('direction'),
            reason: $request->validated('reason'),
            createdBy: (int) $request->user()->id,
            jarCount: (int) ($request->validated('jar_count') ?? 0),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Deposit adjusted successfully.')]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function transformDeposit(CustomerDeposit $deposit): array
    {
        return [
            'id' => $deposit->id,
            'balance' => $deposit->balance,
            'held_jar_count' => $this->depositService->heldJarCount($deposit),
            'closure_refund_amount' => $deposit->balance,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transformTransaction(DepositTransaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'type' => $transaction->type->value,
            'amount' => $transaction->amount,
            'balance_after' => $transaction->balance_after,
            'jar_count' => $transaction->jar_count,
            'product_name' => $transaction->product?->name,
            'description' => $transaction->description,
            'created_by' => $transaction->createdBy?->name,
            'created_at' => $transaction->created_at?->toISOString(),
        ];
    }
}
