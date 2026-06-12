<?php

use App\Models\Tenant;
use App\Models\TenantScopedRecord;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::create('tenant_scoped_records', function (Blueprint $table) {
        $table->id();
        $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
        $table->string('label');
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('tenant_scoped_records');
});

class TenantScopedTestRecord extends TenantScopedRecord
{
    protected $table = 'tenant_scoped_records';

    protected $fillable = ['label'];
}

test('tenant context can be set and cleared', function () {
    $tenant = Tenant::factory()->create();

    TenantContext::set($tenant);

    expect(TenantContext::getId())->toBe($tenant->id)
        ->and(TenantContext::get()?->is($tenant))->toBeTrue();

    TenantContext::clear();

    expect(TenantContext::getId())->toBeNull()
        ->and(TenantContext::isBypassed())->toBeFalse();
});

test('tenant scope filters records to the active tenant', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    TenantContext::set($tenantA);
    TenantScopedTestRecord::query()->create(['label' => 'A']);

    TenantContext::set($tenantB);
    TenantScopedTestRecord::query()->create(['label' => 'B']);

    TenantContext::set($tenantA);

    expect(TenantScopedTestRecord::query()->pluck('label')->all())->toBe(['A']);
});

test('tenant scope is bypassed for super admin reporting', function () {
    $tenant = Tenant::factory()->create();

    TenantContext::set($tenant);
    TenantScopedTestRecord::query()->create(['label' => 'Scoped']);

    TenantContext::bypass();

    expect(TenantScopedTestRecord::query()->count())->toBe(1);
});

test('belongs to tenant auto fills tenant id on create', function () {
    $tenant = Tenant::factory()->create();

    TenantContext::set($tenant);

    $record = TenantScopedTestRecord::query()->create(['label' => 'Auto']);

    expect($record->tenant_id)->toBe($tenant->id);
});

test('users can be scoped to current tenant explicitly', function () {
    TenantContext::clear();

    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $userA = User::factory()->forTenant($tenantA)->create();
    $userB = User::factory()->forTenant($tenantB)->create();

    expect($userA->tenant_id)->toBe($tenantA->id)
        ->and($userB->tenant_id)->toBe($tenantB->id);

    TenantContext::clear();
    TenantContext::set($tenantA);

    expect(TenantContext::getId())->toBe($tenantA->id)
        ->and(
            User::query()->where('tenant_id', TenantContext::getId())->pluck('id')->all()
        )->toBe([$userA->id]);
});
