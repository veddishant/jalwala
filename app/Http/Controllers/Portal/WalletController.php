<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WalletController extends Controller
{
    public function __construct(
        private WalletService $walletService,
    ) {}

    public function index(Request $request): Response
    {
        $customer = $this->resolveCustomer($request);
        $wallet = $this->walletService->ensureForCustomer($customer);

        $this->authorize('view', $wallet);
        $this->authorize('viewLedger', $wallet);

        $transactions = $wallet->transactions()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (WalletTransaction $transaction): array => [
                'id' => $transaction->id,
                'type' => $transaction->type->value,
                'category' => $transaction->category->value,
                'amount' => $transaction->amount,
                'balance_after' => $transaction->balance_after,
                'description' => $transaction->description,
                'created_at' => $transaction->created_at?->toISOString(),
            ]);

        return Inertia::render('portal/wallet', [
            'wallet' => [
                'balance' => $wallet->balance,
                'low_balance_threshold' => $wallet->low_balance_threshold,
                'is_below_threshold' => $wallet->isBelowThreshold(),
                'is_negative' => bccomp($wallet->balance, '0', 2) < 0,
            ],
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
