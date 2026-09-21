<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\OnboardingProgress;

class OnboardingEngine
{
    /**
     * Define the onboarding steps
     */
    public const STEPS = [
        'agency_created' => [
            'label' => 'Create Agency',
            'description' => 'Set up your agency workspace',
            'icon' => 'fa-building',
        ],
        'social_connected' => [
            'label' => 'Connect Social Account',
            'description' => 'Link your first social media account',
            'icon' => 'fa-share-alt',
        ],
        'team_invited' => [
            'label' => 'Invite Team',
            'description' => 'Add team members to collaborate',
            'icon' => 'fa-users',
        ],
        'campaign_created' => [
            'label' => 'Create Campaign',
            'description' => 'Set up your first marketing campaign',
            'icon' => 'fa-bullhorn',
        ],
        'post_published' => [
            'label' => 'Publish First Post',
            'description' => 'Schedule or publish your first post',
            'icon' => 'fa-paper-plane',
        ],
        'ai_activated' => [
            'label' => 'Activate AI',
            'description' => 'Try AI content generation',
            'icon' => 'fa-robot',
        ],
    ];

    /**
     * Get or create progress for an agency
     */
    public function getOrCreate(Agency $agency, string $step): OnboardingProgress
    {
        return OnboardingProgress::firstOrCreate(
            ['agency_id' => $agency->id, 'step' => $step]
        );
    }

    /**
     * Mark a step as completed
     */
    public function completeStep(Agency $agency, string $step, array $data = []): OnboardingProgress
    {
        $progress = $this->getOrCreate($agency, $step);
        $progress->complete($data);
        return $progress;
    }

    /**
     * Check if a step is completed
     */
    public function isStepCompleted(Agency $agency, string $step): bool
    {
        return OnboardingProgress::where('agency_id', $agency->id)
            ->where('step', $step)
            ->exists();
    }

    /**
     * Get all progress for an agency
     */
    public function getAllProgress(Agency $agency): array
    {
        $completedSteps = OnboardingProgress::where('agency_id', $agency->id)
            ->pluck('step')
            ->toArray();

        $progress = [];
        foreach (self::STEPS as $key => $step) {
            $progress[$key] = [
                ...$step,
                'key' => $key,
                'completed' => in_array($key, $completedSteps),
            ];
        }

        return $progress;
    }

    /**
     * Calculate completion percentage
     */
    public function getCompletionPercentage(Agency $agency): int
    {
        $totalSteps = count(self::STEPS);
        $completedSteps = OnboardingProgress::where('agency_id', $agency->id)->count();

        return (int) round(($completedSteps / $totalSteps) * 100);
    }

    /**
     * Get the next incomplete step
     */
    public function getNextStep(Agency $agency): ?string
    {
        $completedSteps = OnboardingProgress::where('agency_id', $agency->id)
            ->pluck('step')
            ->toArray();

        foreach (array_keys(self::STEPS) as $step) {
            if (!in_array($step, $completedSteps)) {
                return $step;
            }
        }

        return null;
    }

    /**
     * Auto-detect completed steps based on agency state
     */
    public function autoDetectProgress(Agency $agency): void
    {
        // Agency created
        if ($agency->created_at) {
            $this->completeStep($agency, 'agency_created');
        }

        // Social account connected
        if ($agency->socialAccounts()->exists()) {
            $this->completeStep($agency, 'social_connected');
        }

        // Team invited (owner + at least 1 more)
        if ($agency->users()->count() > 1) {
            $this->completeStep($agency, 'team_invited');
        }

        // Campaign created
        if ($agency->campaigns()->exists()) {
            $this->completeStep($agency, 'campaign_created');
        }

        // Post published
        if ($agency->socialPosts()->where('status', 'published')->exists()) {
            $this->completeStep($agency, 'post_published');
        }

        // AI activated (check AI content logs)
        if (\App\Models\AiContentLog::where('agency_id', $agency->id)->exists()) {
            $this->completeStep($agency, 'ai_activated');
        }
    }
}
