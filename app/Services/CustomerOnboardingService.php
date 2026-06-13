<?php

namespace App\Services;

use App\CustomerStatus;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\User;
use App\Support\TenantContext;
use App\UserStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CustomerOnboardingService
{
    public function __construct(
        private WalletService $walletService,
        private InventoryService $inventoryService,
    ) {}

    /**
     * @param  array{
     *     name: string,
     *     phone: string,
     *     email?: string|null,
     *     status?: string,
     *     notes?: string|null,
     *     address: array{
     *         label: string,
     *         address_line_1: string,
     *         address_line_2?: string|null,
     *         city: string,
     *         state: string,
     *         postal_code: string,
     *         delivery_instructions?: string|null
     *     },
     *     portal?: array{
     *         create?: bool,
     *         password?: string
     *     },
     *     wallet?: array{
     *         opening_balance?: string|null,
     *         low_balance_threshold?: string|null
     *     }
     * }  $data
     */
    public function onboard(array $data, int $tenantId, ?int $createdBy = null): Customer
    {
        return DB::transaction(function () use ($data, $tenantId, $createdBy): Customer {
            TenantContext::setId($tenantId);

            $customer = Customer::query()->create([
                'code' => $this->generateCustomerCode($tenantId),
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'status' => CustomerStatus::from($data['status'] ?? CustomerStatus::Active->value),
                'notes' => $data['notes'] ?? null,
            ]);

            CustomerAddress::query()->create([
                ...$data['address'],
                'customer_id' => $customer->id,
                'is_default' => true,
            ]);

            if (($data['portal']['create'] ?? false) && filled($data['email'])) {
                $user = User::query()->create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'password' => Hash::make($data['portal']['password'] ?? ''),
                    'status' => UserStatus::Active,
                ]);

                $user->forceFill(['tenant_id' => $tenantId])->save();
                $user->assignRole('customer');

                $customer->update(['user_id' => $user->id]);
            }

            $openingBalance = isset($data['wallet']['opening_balance'])
                ? number_format((float) $data['wallet']['opening_balance'], 2, '.', '')
                : null;
            $lowBalanceThreshold = isset($data['wallet']['low_balance_threshold'])
                ? number_format((float) $data['wallet']['low_balance_threshold'], 2, '.', '')
                : null;

            $this->walletService->ensureForCustomer(
                customer: $customer,
                openingBalance: $openingBalance,
                lowBalanceThreshold: $lowBalanceThreshold,
                createdBy: $createdBy,
            );

            $this->inventoryService->ensureCustomerLocation($customer);

            return $customer->load(['addresses', 'user', 'wallet']);
        });
    }

    private function generateCustomerCode(int $tenantId): string
    {
        $count = Customer::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->count();

        return 'CUST-'.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }
}
