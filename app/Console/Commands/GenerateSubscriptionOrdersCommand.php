<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\SubscriptionOrderGeneratorService;
use App\Support\TenantContext;
use App\TenantStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateSubscriptionOrdersCommand extends Command
{
    protected $signature = 'subscriptions:generate-orders
                            {--date= : Target delivery date (Y-m-d). Defaults to tomorrow.}
                            {--tenant= : Limit generation to a specific tenant ID}';

    protected $description = 'Generate subscription orders for the target delivery date';

    public function handle(SubscriptionOrderGeneratorService $generator): int
    {
        $targetDate = $this->option('date')
            ? Carbon::parse($this->option('date'))->toDateString()
            : today()->addDay()->toDateString();

        $tenantId = $this->option('tenant');

        if ($tenantId !== null) {
            $tenant = Tenant::query()
                ->where('id', $tenantId)
                ->where('status', TenantStatus::Active)
                ->first();

            if ($tenant === null) {
                $this->error('Active tenant not found.');

                return self::FAILURE;
            }

            $count = $generator->generateForTenant($tenant, $targetDate);
        } else {
            $count = $generator->generateForDate($targetDate);
        }

        TenantContext::clear();

        $this->info("Generated {$count} subscription order(s) for {$targetDate}.");

        return self::SUCCESS;
    }
}
