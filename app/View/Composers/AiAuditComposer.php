<?php

namespace App\View\Composers;

use App\Services\AI\Audit\AiAuditService;
use Illuminate\View\View;

class AiAuditComposer
{
    public function __construct(
        private readonly AiAuditService $auditService,
    ) {
    }

    public function compose(View $view): void
    {
        $user = auth()->user();

        if (! $user) {
            $view->with('aiAuditStats', [
                'flagged_today' => 0,
                'total_today' => 0,
                'has_flagged' => false,
            ]);

            return;
        }

        $stats = $this->auditService->getStatsForNav($user->agency_id);
        $view->with('aiAuditStats', $stats);
    }
}
