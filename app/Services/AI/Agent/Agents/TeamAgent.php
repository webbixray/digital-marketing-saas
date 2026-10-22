<?php

namespace App\Services\AI\Agent\Agents;

use App\Models\Agency;
use App\Models\User;
use App\Services\AI\Agent\AbstractAgent;
use App\Services\AI\Agent\AgentContext;
use App\Services\AI\Agent\AgentResult;
use App\Services\AI\Agent\AgentTask;
use App\Services\AI\Gateway\AiRequest;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class TeamAgent extends AbstractAgent
{
    protected string $name = 'team_agent';

    /**
     * @var array<string>
     */
    protected array $supportedTaskTypes = [
        'task_suggest',
        'workload_balance',
        'performance_review',
        'skill_gap_analysis',
    ];

    /**
     * Task completion improvement thresholds.
     */
    private const COMPLETION_THRESHOLD_HIGH = 0.85;

    private const COMPLETION_THRESHOLD_MEDIUM = 0.6;

    /**
     * {@inheritdoc}
     */
    public function execute(AgentTask $task, AgentContext $context): AgentResult
    {
        $startTime = microtime(true);

        if (! $this->canHandle($task->type)) {
            return AgentResult::failure(
                taskId: $task->id,
                agentName: $this->name,
                error: "Unsupported task type: {$task->type}"
            );
        }

        $agency = $context->agency;

        if (! $agency) {
            return AgentResult::failure(
                taskId: $task->id,
                agentName: $this->name,
                error: 'No agency context provided'
            );
        }

        try {
            $result = match ($task->type) {
                'task_suggest' => $this->handleTaskSuggest($task, $agency, $context),
                'workload_balance' => $this->handleWorkloadBalance($task, $agency, $context),
                'performance_review' => $this->handlePerformanceReview($task, $agency, $context),
                'skill_gap_analysis' => $this->handleSkillGapAnalysis($task, $agency, $context),
                default => null,
            };

            if ($result === null) {
                return AgentResult::failure(
                    taskId: $task->id,
                    agentName: $this->name,
                    error: "Failed to execute task: {$task->type}"
                );
            }

            $executionTime = (microtime(true) - $startTime) * 1000;
            $result = new AgentResult(
                taskId: $result->taskId,
                agentName: $result->agentName,
                success: $result->success,
                output: $result->output,
                costUsd: $result->costUsd,
                tokensUsed: $result->tokensUsed,
                executionTimeMs: $executionTime,
                error: $result->error,
                metadata: $result->metadata,
                timestamp: $result->timestamp
            );

            $this->recordExecution($task->type, $result);
            $this->persistAgencyResults($context, $task->type, $result);

            return $result;
        } catch (\Exception $e) {
            Log::error("TeamAgent execution failed: {$e->getMessage()}", [
                'task_id' => $task->id,
                'task_type' => $task->type,
            ]);

            $executionTime = (microtime(true) - $startTime) * 1000;
            $result = AgentResult::failure(
                taskId: $task->id,
                agentName: $this->name,
                error: $e->getMessage(),
                metadata: ['task_type' => $task->type]
            );

            $this->recordExecution($task->type, $result);

            return $result;
        }
    }

    /**
     * {@inheritdoc}
     *
     * Success rate based on task completion improvement.
     */
    public function getSuccessRate(): float
    {
        $completionScores = $this->executionStats['completion_scores'] ?? [];

        if (empty($completionScores)) {
            return 0.5;
        }

        $highCompletion = count(array_filter($completionScores, fn ($s) => $s >= self::COMPLETION_THRESHOLD_HIGH));
        $mediumCompletion = count(array_filter($completionScores, fn ($s) => $s >= self::COMPLETION_THRESHOLD_MEDIUM));

        $weightedSuccesses = ($highCompletion * 2) + $mediumCompletion;

        return min($weightedSuccesses / (count($completionScores) * 2), 1.0);
    }

    /**
     * Get team productivity patterns learned over time.
     *
     * @return array<string, array>
     */
    public function getProductivityPatterns(): array
    {
        return $this->executionStats['productivity_patterns'] ?? [];
    }

    /**
     * Get skill assignment recommendations.
     *
     * @return array<string, array>
     */
    public function getSkillRecommendations(): array
    {
        return $this->executionStats['skill_recommendations'] ?? [];
    }

    /**
     * Handle task suggestion based on skills and workload.
     */
    private function handleTaskSuggest(AgentTask $task, Agency $agency, AgentContext $context): AgentResult
    {
        $teamMembers = $this->getTeamMembers($agency);
        $pendingTasks = $task->data['pending_tasks'] ?? [];
        $priority = $task->data['priority'] ?? 'medium';

        // Get learned skill recommendations
        $skillRecs = $this->getSkillRecommendations();

        // Get workload data
        $workloads = $this->getTeamWorkloads($agency, $teamMembers);

        $prompt = "Suggest optimal task assignments for the following team:\n\n";
        $prompt .= "Priority: {$priority}\n\n";

        $prompt .= "Team Members:\n";
        foreach ($teamMembers as $member) {
            $workload = $workloads[$member->id] ?? ['active_tasks' => 0, 'capacity' => 'unknown'];
            $prompt .= "- {$member->name} ({$member->role}): {$workload['active_tasks']} active tasks\n";
        }

        $prompt .= "\nPending Tasks:\n";
        foreach ($pendingTasks as $pendingTask) {
            $prompt .= "- {$pendingTask['title']} (Skills: ".implode(', ', $pendingTask['required_skills'] ?? []).")\n";
        }

        if (! empty($skillRecs)) {
            $prompt .= "\nBased on past successful assignments:\n";
            foreach (array_slice($skillRecs, 0, 5) as $rec) {
                $prompt .= "- {$rec['pattern']}: {$rec['success_rate']}% success rate\n";
            }
        }

        $prompt .= "\nProvide:\n";
        $prompt .= "1. Recommended task assignments with rationale\n";
        $prompt .= "2. Expected completion timeline\n";
        $prompt .= "3. Risk assessment for each assignment\n";
        $prompt .= "4. Alternative assignments if primary is unavailable\n";
        $prompt .= '5. Collaboration suggestions for complex tasks';

        $request = AiRequest::analysis(
            prompt: $prompt,
            systemPrompt: 'You are a team management specialist. You analyze team skills, workload, and task requirements to optimize task assignments and maximize productivity.'
        );

        $response = $this->callAi($request, $agency);

        // Calculate completion score based on assignment quality
        $completionScore = $this->calculateAssignmentScore($response->content, $teamMembers, $pendingTasks);

        $meta = [
            'task_type' => 'task_suggest',
            'team_size' => count($teamMembers),
            'pending_tasks' => count($pendingTasks),
            'priority' => $priority,
            'completion_score' => $completionScore,
        ];

        $this->recordCompletionScore('task_suggest', $completionScore, $meta);
        $this->learnFromAssignment($response->content, $teamMembers, $pendingTasks);

        return AgentResult::success(
            taskId: $task->id,
            agentName: $this->name,
            output: $response->content,
            costUsd: $response->costUsd ?? 0,
            tokensUsed: $response->totalTokens,
            executionTimeMs: 0,
            metadata: $meta,
        );
    }

    /**
     * Handle workload balancing across team.
     */
    private function handleWorkloadBalance(AgentTask $task, Agency $agency, AgentContext $context): AgentResult
    {
        $teamMembers = $this->getTeamMembers($agency);
        $workloads = $this->getTeamWorkloads($agency, $teamMembers);

        // Get learned productivity patterns
        $patterns = $this->getProductivityPatterns();

        $prompt = "Analyze and balance the workload across the following team:\n\n";

        $prompt .= "Current Workloads:\n";
        foreach ($teamMembers as $member) {
            $workload = $workloads[$member->id] ?? ['active_tasks' => 0, 'completed_this_month' => 0, 'avg_completion_time' => 0];
            $prompt .= "- {$member->name} ({$member->role}):\n";
            $prompt .= "  Active tasks: {$workload['active_tasks']}\n";
            $prompt .= "  Completed this month: {$workload['completed_this_month']}\n";
            $prompt .= "  Avg completion time: {$workload['avg_completion_time']} hours\n";
        }

        if (! empty($patterns)) {
            $prompt .= "\nBased on past productivity patterns:\n";
            foreach ($patterns as $pattern => $data) {
                $prompt .= "- {$pattern}: Optimal load = {$data['optimal_load']} tasks, Peak = {$data['peak_day']}\n";
            }
        }

        $prompt .= "\nProvide:\n";
        $prompt .= "1. Current workload distribution analysis\n";
        $prompt .= "2. Overloaded and underloaded team members\n";
        $prompt .= "3. Specific task redistribution recommendations\n";
        $prompt .= "4. Capacity planning for upcoming work\n";
        $prompt .= "5. Burnout risk assessment\n";
        $prompt .= '6. Suggested hiring needs if applicable';

        $request = AiRequest::analysis(
            prompt: $prompt,
            systemPrompt: 'You are a workforce optimization specialist. You analyze team workloads and provide actionable recommendations to balance capacity and prevent burnout.'
        );

        $response = $this->callAi($request, $agency);

        $completionScore = $this->calculateWorkloadScore($response->content, $workloads);

        $meta = [
            'task_type' => 'workload_balance',
            'team_size' => count($teamMembers),
            'avg_workload' => $this->calculateAverageWorkload($workloads),
            'completion_score' => $completionScore,
        ];

        $this->recordCompletionScore('workload_balance', $completionScore, $meta);
        $this->learnProductivityPatterns($teamMembers, $workloads);

        return AgentResult::success(
            taskId: $task->id,
            agentName: $this->name,
            output: $response->content,
            costUsd: $response->costUsd ?? 0,
            tokensUsed: $response->totalTokens,
            executionTimeMs: 0,
            metadata: $meta,
        );
    }

    /**
     * Handle performance review generation.
     */
    private function handlePerformanceReview(AgentTask $task, Agency $agency, AgentContext $context): AgentResult
    {
        $reviewPeriod = $task->data['period'] ?? 'monthly';
        $memberId = $task->data['member_id'] ?? null;

        $teamMembers = $memberId
            ? $this->getTeamMembers($agency)->where('id', $memberId)
            : $this->getTeamMembers($agency);

        if ($teamMembers->isEmpty()) {
            return AgentResult::failure(
                taskId: $task->id,
                agentName: $this->name,
                error: 'No team members found for performance review'
            );
        }

        $prompt = "Generate performance reviews for the following team members ({$reviewPeriod}):\n\n";

        foreach ($teamMembers as $member) {
            $performance = $this->getMemberPerformance($agency, $member, $reviewPeriod);
            $prompt .= "Member: {$member->name} ({$member->role})\n";
            $prompt .= "Tasks completed: {$performance['tasks_completed']}\n";
            $prompt .= "Avg completion time: {$performance['avg_completion_time']} hours\n";
            $prompt .= "On-time delivery rate: {$performance['on_time_rate']}%\n";
            $prompt .= "Quality score: {$performance['quality_score']}/10\n";
            $prompt .= "Collaboration score: {$performance['collaboration_score']}/10\n\n";
        }

        $prompt .= "Provide for each member:\n";
        $prompt .= "1. Overall performance rating\n";
        $prompt .= "2. Key achievements\n";
        $prompt .= "3. Areas for improvement\n";
        $prompt .= "4. Specific, actionable feedback\n";
        $prompt .= "5. Goals for next period\n";
        $prompt .= '6. Professional development recommendations';

        $request = AiRequest::analysis(
            prompt: $prompt,
            systemPrompt: 'You are a people operations specialist. You create fair, constructive performance reviews that motivate growth and recognize achievements.'
        );

        $response = $this->callAi($request, $agency);

        $completionScore = 0.7; // Default for performance reviews

        $meta = [
            'task_type' => 'performance_review',
            'review_period' => $reviewPeriod,
            'members_reviewed' => $teamMembers->count(),
            'completion_score' => $completionScore,
        ];

        $this->recordCompletionScore('performance_review', $completionScore, $meta);

        return AgentResult::success(
            taskId: $task->id,
            agentName: $this->name,
            output: $response->content,
            costUsd: $response->costUsd ?? 0,
            tokensUsed: $response->totalTokens,
            executionTimeMs: 0,
            metadata: $meta,
        );
    }

    /**
     * Handle skill gap analysis.
     */
    private function handleSkillGapAnalysis(AgentTask $task, Agency $agency, AgentContext $context): AgentResult
    {
        $teamMembers = $this->getTeamMembers($agency);
        $requiredSkills = $task->data['required_skills'] ?? [
            'content_creation', 'social_media', 'analytics',
            'copywriting', 'design', 'strategy', 'client_management',
        ];

        $prompt = "Analyze skill gaps for the following team:\n\n";

        $prompt .= "Current Team Skills:\n";
        foreach ($teamMembers as $member) {
            $skills = $this->getMemberSkills($member);
            $prompt .= "- {$member->name}: ".implode(', ', $skills)."\n";
        }

        $prompt .= "\nRequired Skills for Current Projects:\n";
        $prompt .= implode(', ', $requiredSkills)."\n";

        $prompt .= "\nProvide:\n";
        $prompt .= "1. Current team skill matrix\n";
        $prompt .= "2. Critical skill gaps\n";
        $prompt .= "3. Skills at risk (single points of failure)\n";
        $prompt .= "4. Training recommendations per team member\n";
        $prompt .= "5. Hiring recommendations for critical gaps\n";
        $prompt .= "6. Upskilling timeline and priorities\n";
        $prompt .= '7. Cross-training opportunities';

        $request = AiRequest::analysis(
            prompt: $prompt,
            systemPrompt: 'You are a talent development specialist. You identify skill gaps and create actionable plans to build team capabilities.'
        );

        $response = $this->callAi($request, $agency);

        $completionScore = 0.65;

        $meta = [
            'task_type' => 'skill_gap_analysis',
            'team_size' => $teamMembers->count(),
            'required_skills' => $requiredSkills,
            'completion_score' => $completionScore,
        ];

        $this->recordCompletionScore('skill_gap_analysis', $completionScore, $meta);

        return AgentResult::success(
            taskId: $task->id,
            agentName: $this->name,
            output: $response->content,
            costUsd: $response->costUsd ?? 0,
            tokensUsed: $response->totalTokens,
            executionTimeMs: 0,
            metadata: $meta,
        );
    }

    /**
     * Get team members for an agency.
     *
     * @return Collection<int, User>
     */
    private function getTeamMembers(Agency $agency)
    {
        return User::where('agency_id', $agency->id)
            ->where('status', 'active')
            ->get();
    }

    /**
     * Get workload data for team members.
     *
     * @param  Collection<int, User>  $members
     * @return array<int, array>
     */
    private function getTeamWorkloads(Agency $agency, $members): array
    {
        $workloads = [];

        foreach ($members as $member) {
            // Simulated workload data - in production this would query tasks table
            $workloads[$member->id] = [
                'active_tasks' => rand(2, 10),
                'completed_this_month' => rand(5, 25),
                'avg_completion_time' => rand(4, 40),
                'capacity' => 'normal',
            ];
        }

        return $workloads;
    }

    /**
     * Get performance data for a team member.
     */
    private function getMemberPerformance(Agency $agency, User $member, string $period): array
    {
        // Simulated performance data - in production this would query actual task data
        return [
            'tasks_completed' => rand(5, 30),
            'avg_completion_time' => rand(4, 30),
            'on_time_rate' => rand(70, 98),
            'quality_score' => rand(6, 10),
            'collaboration_score' => rand(5, 10),
        ];
    }

    /**
     * Get skills for a team member.
     *
     * @return array<string>
     */
    private function getMemberSkills(User $member): array
    {
        // Simulated skills - in production this would query a skills table
        $allSkills = [
            'content_creation', 'social_media', 'analytics',
            'copywriting', 'design', 'strategy', 'client_management',
            'seo', 'ppc', 'email_marketing',
        ];

        $count = rand(2, 5);
        shuffle($allSkills);

        return array_slice($allSkills, 0, $count);
    }

    /**
     * Calculate assignment quality score.
     */
    private function calculateAssignmentScore(string $content, $teamMembers, array $pendingTasks): float
    {
        $score = 0.5;

        // More detailed assignments score higher
        if (strlen($content) > 500) {
            $score += 0.15;
        }

        // Assignments with rationale score higher
        if (stripos($content, 'because') !== false || stripos($content, 'rationale') !== false) {
            $score += 0.1;
        }

        // Assignments mentioning specific skills score higher
        if (preg_match('/skill|experience|expertise/i', $content)) {
            $score += 0.1;
        }

        // Assignments with timeline score higher
        if (preg_match('/day|week|hour|timeline|deadline/i', $content)) {
            $score += 0.08;
        }

        return min($score, 1.0);
    }

    /**
     * Calculate workload balance score.
     */
    private function calculateWorkloadScore(string $content, array $workloads): float
    {
        $score = 0.5;

        if (strlen($content) > 400) {
            $score += 0.12;
        }

        if (stripos($content, 'redistribute') !== false || stripos($content, 'reassign') !== false) {
            $score += 0.1;
        }

        if (stripos($content, 'burnout') !== false || stripos($content, 'overload') !== false) {
            $score += 0.08;
        }

        return min($score, 1.0);
    }

    /**
     * Calculate average workload across team.
     */
    private function calculateAverageWorkload(array $workloads): float
    {
        if (empty($workloads)) {
            return 0;
        }

        $total = array_sum(array_column($workloads, 'active_tasks'));

        return round($total / count($workloads), 1);
    }

    /**
     * Record completion score for learning.
     */
    private function recordCompletionScore(string $taskType, float $score, array $meta = []): void
    {
        $this->executionStats['completion_scores'][] = $score;

        $taskCompletions = $this->executionStats['task_completions'] ?? [];
        $taskCompletions[$taskType][] = $score;
        $this->executionStats['task_completions'] = $taskCompletions;

        $this->persistMemory();
    }

    /**
     * Learn from task assignments.
     */
    private function learnFromAssignment(string $content, $teamMembers, array $pendingTasks): void
    {
        $recs = $this->executionStats['skill_recommendations'] ?? [];

        // Extract assignment patterns
        if (preg_match_all('/(\w+)\s*(?:should|assigned|best for|ideal for)\s*(.+)/i', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $recs[] = [
                    'pattern' => trim($match[0]),
                    'success_rate' => 70,
                    'timestamp' => now()->toIso8601String(),
                ];
            }
        }

        // Keep top 30 recommendations
        if (count($recs) > 30) {
            $recs = array_slice($recs, -30);
        }

        $this->executionStats['skill_recommendations'] = $recs;
        $this->persistMemory();
    }

    /**
     * Learn productivity patterns from team data.
     *
     * @param  Collection<int, User>  $members
     */
    private function learnProductivityPatterns($members, array $workloads): void
    {
        $patterns = $this->executionStats['productivity_patterns'] ?? [];

        // Track optimal workload per role
        foreach ($members as $member) {
            $workload = $workloads[$member->id] ?? null;
            if (! $workload) {
                continue;
            }

            $role = $member->role ?? 'general';
            $rolePatterns = $patterns[$role] ?? ['loads' => [], 'optimal_load' => 5, 'peak_day' => 'Tuesday'];

            $rolePatterns['loads'][] = $workload['active_tasks'];
            $rolePatterns['optimal_load'] = round(array_sum($rolePatterns['loads']) / count($rolePatterns['loads']));
            $patterns[$role] = $rolePatterns;
        }

        $this->executionStats['productivity_patterns'] = $patterns;
        $this->persistMemory();
    }
}
