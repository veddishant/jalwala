<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\InquiryStatus;
use App\InquiryType;
use App\Models\Inquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InquiryController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Inquiry::class);

        $search = $request->string('search')->trim()->toString();
        $type = $request->string('type')->trim()->toString();
        $status = $request->string('status')->trim()->toString();

        $inquiries = Inquiry::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%");
                });
            })
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Inquiry $inquiry): array => $this->listItem($inquiry));

        return Inertia::render('platform/inquiries/index', [
            'inquiries' => $inquiries,
            'filters' => [
                'search' => $search,
                'type' => $type,
                'status' => $status,
            ],
            'types' => collect(InquiryType::cases())->map(fn (InquiryType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
            ])->values()->all(),
            'statuses' => collect(InquiryStatus::cases())->map(fn (InquiryStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->values()->all(),
            'stats' => [
                'new' => Inquiry::query()->where('status', InquiryStatus::New)->count(),
                'total' => Inquiry::query()->count(),
            ],
        ]);
    }

    public function show(Request $request, Inquiry $inquiry): Response
    {
        $this->authorize('view', $inquiry);

        if ($request->user()?->can('update', $inquiry)) {
            $inquiry->markAsRead();
            $inquiry->refresh();
        }

        return Inertia::render('platform/inquiries/show', [
            'inquiry' => $this->detail($inquiry),
            'can' => [
                'update' => $request->user()?->can('update', $inquiry) ?? false,
            ],
        ]);
    }

    public function archive(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $this->authorize('update', $inquiry);

        $inquiry->update(['status' => InquiryStatus::Archived]);

        return to_route('platform.inquiries.index')
            ->with('status', 'Inquiry archived.');
    }

    /**
     * @return array<string, mixed>
     */
    private function listItem(Inquiry $inquiry): array
    {
        return [
            'id' => $inquiry->id,
            'name' => $inquiry->name,
            'email' => $inquiry->email,
            'type' => $inquiry->type->value,
            'type_label' => $inquiry->type->label(),
            'subject' => $inquiry->subject,
            'message' => str($inquiry->message)->limit(120)->toString(),
            'status' => $inquiry->status->value,
            'status_label' => $inquiry->status->label(),
            'created_at' => $inquiry->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(Inquiry $inquiry): array
    {
        return [
            'id' => $inquiry->id,
            'name' => $inquiry->name,
            'email' => $inquiry->email,
            'phone' => $inquiry->phone,
            'type' => $inquiry->type->value,
            'type_label' => $inquiry->type->label(),
            'subject' => $inquiry->subject,
            'message' => $inquiry->message,
            'status' => $inquiry->status->value,
            'status_label' => $inquiry->status->label(),
            'ip_address' => $inquiry->ip_address,
            'user_agent' => $inquiry->user_agent,
            'read_at' => $inquiry->read_at?->toIso8601String(),
            'created_at' => $inquiry->created_at?->toIso8601String(),
        ];
    }
}
