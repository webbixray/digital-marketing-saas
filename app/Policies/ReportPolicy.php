<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    public function view(User $user, Report $report): bool
    {
        return $user->agency_id === $report->agency_id;
    }

    public function create(User $user): bool
    {
        return $user->isEditor();
    }

    public function update(User $user, Report $report): bool
    {
        return $user->agency_id === $report->agency_id && $user->isEditor();
    }

    public function delete(User $user, Report $report): bool
    {
        return $user->agency_id === $report->agency_id && $user->isOwner();
    }

    public function generate(User $user, Report $report): bool
    {
        return $user->agency_id === $report->agency_id;
    }

    public function download(User $user, Report $report): bool
    {
        return $user->agency_id === $report->agency_id;
    }
}
