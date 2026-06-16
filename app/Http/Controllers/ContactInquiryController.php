<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInquiryRequest;
use App\InquiryStatus;
use App\Models\Inquiry;
use Illuminate\Http\RedirectResponse;

class ContactInquiryController extends Controller
{
    public function store(StoreInquiryRequest $request): RedirectResponse
    {
        Inquiry::query()->create([
            ...$request->validated(),
            'status' => InquiryStatus::New,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('status', 'Thank you! Your message has been sent. We will get back to you soon.');
    }
}
