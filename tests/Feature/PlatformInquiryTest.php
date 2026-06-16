<?php

use App\InquiryStatus;
use App\Models\Inquiry;
use App\Models\User;

test('super admin can list inquiries', function () {
    seedRolesAndPermissions();

    Inquiry::factory()->count(2)->create();

    $superAdmin = User::factory()->superAdmin()->create();
    $superAdmin->assignRole('super-admin');

    $this->actingAs($superAdmin)
        ->get(route('platform.inquiries.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/inquiries/index')
            ->has('inquiries.data', 2)
            ->has('stats'));
});

test('super admin can view inquiry and it is marked read', function () {
    seedRolesAndPermissions();

    $inquiry = Inquiry::factory()->create([
        'status' => InquiryStatus::New,
    ]);

    $superAdmin = User::factory()->superAdmin()->create();
    $superAdmin->assignRole('super-admin');

    $this->actingAs($superAdmin)
        ->get(route('platform.inquiries.show', $inquiry))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/inquiries/show')
            ->where('inquiry.id', $inquiry->id));

    expect($inquiry->refresh()->status)->toBe(InquiryStatus::Read)
        ->and($inquiry->read_at)->not->toBeNull();
});

test('super admin can archive inquiry', function () {
    seedRolesAndPermissions();

    $inquiry = Inquiry::factory()->read()->create();

    $superAdmin = User::factory()->superAdmin()->create();
    $superAdmin->assignRole('super-admin');

    $this->actingAs($superAdmin)
        ->post(route('platform.inquiries.archive', $inquiry))
        ->assertRedirect(route('platform.inquiries.index'))
        ->assertSessionHas('status');

    expect($inquiry->refresh()->status)->toBe(InquiryStatus::Archived);
});

test('supplier admin cannot access platform inquiries', function () {
    seedRolesAndPermissions();

    $inquiry = Inquiry::factory()->create();
    ['admin' => $admin] = createSupplierAdmin();

    $this->actingAs($admin)
        ->get(route('platform.inquiries.index'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('platform.inquiries.show', $inquiry))
        ->assertForbidden();

    $this->actingAs($admin)
        ->post(route('platform.inquiries.archive', $inquiry))
        ->assertForbidden();
});
