<?php

namespace App\Policies;

use App\Models\User;
use App\ReportType;

class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return collect(ReportType::all())
            ->contains(fn (ReportType $type): bool => $user->can($type->permission()));
    }

    public function view(User $user, ReportType $reportType): bool
    {
        return $user->can($reportType->permission());
    }

    public function export(User $user, ReportType $reportType): bool
    {
        return $user->can($reportType->permission());
    }
}
