<?php

namespace App\Policies;

use App\Models\Inquiry;
use App\Models\User;

class InquiryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() && $user->can('platform.inquiries.view');
    }

    public function view(User $user, Inquiry $inquiry): bool
    {
        return $user->isSuperAdmin() && $user->can('platform.inquiries.view');
    }

    public function update(User $user, Inquiry $inquiry): bool
    {
        return $user->isSuperAdmin() && $user->can('platform.inquiries.update');
    }
}
