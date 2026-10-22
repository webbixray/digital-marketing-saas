<?php

namespace App\Jobs\Scheduler;

use App\Enums\WorkflowStatus;
use App\Models\Workflow;
use App\Services\Workflow\WorkflowEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunWorkflows implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 600;

    public function handle(): void
    {
        $workflows = Workflow::where('status', WorkflowStatus::ACTIVE->value)
            ->where('trigger_type', 'cron')
            ->get();

        foreach ($workflows as $workflow) {
            try {
                $engine = app(WorkflowEngine::class);
                $engine->execute($workflow, ['triggered_at' => now()->toISOString()]);
                Log::info("Workflow #{$workflow->id} executed successfully");
            } catch (\Exception $e) {
                Log::error("Workflow #{$workflow->id} execution failed: ".$e->getMessage());
            }
        }
    }
}
