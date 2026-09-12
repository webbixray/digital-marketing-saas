<?php

namespace App\Providers;

use App\Services\AI\Agent\AgentInterface;
use App\Services\AI\Agent\AgentMemory;
use App\Services\AI\Agent\AgentOrchestrator;
use App\Services\AI\Agent\Workflows\CampaignOptimizationWorkflow;
use App\Services\AI\Agent\Workflows\ClientOnboardingWorkflow;
use App\Services\AI\Agent\Workflows\ContentCalendarWorkflow;
use App\Services\AI\Agent\Workflows\EmailMarketingWorkflow;
use App\Services\AI\Agent\Workflows\LeadGenerationWorkflow;
use App\Services\AI\Agent\Workflows\SocialMediaStrategyWorkflow;
use App\Services\AI\Agent\Workflows\WeeklyReportWorkflow;
use App\Services\AI\Agent\Workflows\WorkflowRunner;
use App\Services\AI\Gateway\AiGateway;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AgentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register AgentMemory as singleton
        $this->app->singleton(AgentMemory::class, function ($app) {
            return new AgentMemory;
        });

        // Register AgentOrchestrator as singleton
        $this->app->singleton(AgentOrchestrator::class, function ($app) {
            $orchestrator = new AgentOrchestrator(
                aiGateway: $app->make(AiGateway::class),
                memory: $app->make(AgentMemory::class),
            );

            // Auto-discover and register agents
            $this->autoDiscoverAgents($orchestrator);

            return $orchestrator;
        });

        // Register WorkflowRunner as singleton
        $this->app->singleton(WorkflowRunner::class, function ($app) {
            $runner = new WorkflowRunner(
                orchestrator: $app->make(AgentOrchestrator::class),
            );

            // Register workflow templates
            $runner->registerTemplate(new ContentCalendarWorkflow);
            $runner->registerTemplate(new ClientOnboardingWorkflow);
            $runner->registerTemplate(new CampaignOptimizationWorkflow);
            $runner->registerTemplate(new EmailMarketingWorkflow);
            $runner->registerTemplate(new SocialMediaStrategyWorkflow);
            $runner->registerTemplate(new LeadGenerationWorkflow);
            $runner->registerTemplate(new WeeklyReportWorkflow);

            return $runner;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Auto-discover agent classes and register them with the orchestrator.
     */
    private function autoDiscoverAgents(AgentOrchestrator $orchestrator): void
    {
        $agentDir = app_path('Services/AI/Agent/Agents');

        if (! is_dir($agentDir)) {
            Log::info('AgentServiceProvider: no Agents directory found, skipping auto-discovery');

            return;
        }

        $files = File::files($agentDir);

        foreach ($files as $file) {
            $className = 'App\\Services\\AI\\Agent\\Agents\\'.$file->getFilenameWithoutExtension();

            if (! class_exists($className)) {
                continue;
            }

            $interfaces = class_implements($className);

            if ($interfaces === false || ! in_array(AgentInterface::class, $interfaces)) {
                continue;
            }

            try {
                $agent = $this->app->make($className);
                $orchestrator->registerAgent($agent->getName(), $agent);
            } catch (\Exception $e) {
                Log::warning("AgentServiceProvider: failed to register agent [{$className}]: {$e->getMessage()}");
            }
        }

        Log::info('AgentServiceProvider: auto-discovery complete, registered agents: '.implode(', ', $orchestrator->getRegisteredAgents()));
    }
}
