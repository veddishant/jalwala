<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DepositTransaction;
use App\Services\DepositService;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DepositController extends Controller
{
    public function __construct(
        private DepositService $depositService,
    ) {}

    public function index(Request $request): Response
    {
        $customer = $this->resolveCustomer($request);
        $deposit = $this->depositService->ensureForCustomer($customer);

        $this->authorize('view', $deposit);
        $this->authorize('viewLedger', $deposit);

        $transactions = $deposit->transactions()
            ->with('product:id,name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (DepositTransaction $transaction): array => [
                'id' => $transaction->id,
                'type' => $transaction->type->value,
                'amount' => $transaction->amount,
                'balance_after' => $transaction->balance_after,
                'jar_count' => $transaction->jar_count,
                'product_name' => $transaction->product?->name,
                'description' => $transaction->description,
                'created_at' => $transaction->created_at?->toISOString(),
            ]);

        return Inertia::render('portal/deposits', [
            'deposit' => [
                'balance' => $deposit->balance,
                'held_jar_count' => $this->depositService->heldJarCount($deposit),
            ],
            'jarSummary' => $this->depositService->jarSummaryByProduct($deposit),
            'transactions' => $transactions,
        ]);
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
