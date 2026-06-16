<?php

use App\InquiryStatus;
use App\InquiryType;
use App\Models\Inquiry;

test('guest can submit contact inquiry from landing page', function () {
    $response = $this->post(route('contact.store'), [
        'name' => 'Ravi Kumar',
        'email' => 'ravi@example.com',
        'phone' => '9876543210',
        'type' => InquiryType::Supplier->value,
        'subject' => 'Interested in supplier plan',
        'message' => 'We run a water delivery business in Pune and want to try Jalwala.',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status');

    $this->assertDatabaseHas('inquiries', [
        'name' => 'Ravi Kumar',
        'email' => 'ravi@example.com',
        'type' => InquiryType::Supplier->value,
        'status' => InquiryStatus::New->value,
    ]);
});

test('contact inquiry requires valid fields', function () {
    $this->post(route('contact.store'), [
        'name' => '',
        'email' => 'not-an-email',
        'type' => 'invalid',
        'message' => 'short',
    ])->assertSessionHasErrors(['name', 'email', 'type', 'message']);

    expect(Inquiry::query()->count())->toBe(0);
});

test('contact inquiry accepts bug and suggestion types', function () {
    foreach ([InquiryType::Bug, InquiryType::Suggestion, InquiryType::Tenant] as $type) {
        $this->post(route('contact.store'), [
            'name' => 'Test User',
            'email' => "test-{$type->value}@example.com",
            'type' => $type->value,
            'message' => 'This is a detailed message for testing purposes.',
        ])->assertRedirect();
    }

    expect(Inquiry::query()->count())->toBe(3);
});
