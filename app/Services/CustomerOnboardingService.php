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
     *     }
     * }  $data
     */
    public function onboard(array $data, int $tenantId): Customer
    {
        return DB::transaction(function () use ($data, $tenantId): Customer {
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

            return $customer->load(['addresses', 'user']);
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
