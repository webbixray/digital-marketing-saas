<?php

namespace App\Services\AI\Agent;

use App\Models\Agency;
use App\Models\Campaign;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\User;

class IntelligentAgentContext
{
    private User $user;
    private Agency $agency;
    private array $userPreferences = [];
    private array $recentActions = [];
    private array $brandVoice = [];
    private array $performanceMetrics = [];
    
    public function __construct(
        private readonly AgentMemory $memory,
        private readonly BrandVoiceService $brandVoiceService,
    ) {}

    /**
     * Build comprehensive context for agent execution
     */
    public function build(User $user, array $additionalContext = []): self
    {
        $this->user = $user;
        $agency = $user->agency;

        // User context
        $this->userPreferences = $this->memory->getUserPreferences($user->id);
        $this->recentActions = $this->memory->getRecentActions($user->id, 20);

        // Agency context
        $this->brandVoice = $this->brandVoiceService->getProfile($agency);
        $this->performanceMetrics = $this->getPerformanceMetrics($agency);

        return $this;
    }

    /**
     * Generate rich context prompt for AI
     */
    public function toPrompt(): string
    {
        $context = [];

        // User role and preferences
        $context[] = "User Role: {$this->user->role}";
        $context[] = "User Language: {$this->userPreferences['language'] ?? 'en'}";
        $context[] = "Preferred Tone: {$this->userPreferences['tone'] ?? 'professional'}";

        // Agency context
        $context[] = "Agency: {$this->user->agency->name}";
        $context[] = "Industry: {$this->user->agency->industry ?? 'general'}";
        $context[] = "Subscription Plan: {$this->user->agency->subscription_plan}";

        // Brand voice
        if (!empty($this->brandVoice)) {
            $context[] = "Brand Voice: " . json_encode($this->brandVoice);
        }

        // Recent actions (for continuity)
        if (!empty($this->recentActions)) {
            $context[] = "Recent Actions: " . json_encode(array_slice($this->recentActions, 0, 5));
        }

        // Performance insights
        if (!empty($this->performanceMetrics)) {
            $context[] = "Top Performing Content: " . json_encode($this->performanceMetrics['top_posts'] ?? []);
            $context[] = "Best Posting Times: " . json_encode($this->performanceMetrics['best_times'] ?? []);
        }

        // Platform context
        $platform = $this->additionalContext['platform'] ?? null;
        if ($platform) {
            $context[] = "Target Platform: {$platform}";
            $context[] = "Platform Best Practices: " . json_encode($this->getPlatformBestPractices($platform));
        }

        // Campaign context
        $campaign = $this->additionalContext['campaign'] ?? null;
        if ($campaign) {
            $context[] = "Campaign: {$campaign->name}";
            $context[] = "Campaign Objective: {$campaign->objective}";
            $context[] = "Campaign History: " . json_encode($this->getCampaignHistory($campaign));
        }

        return implode("\n", $context);
    }

    /**
     * Get platform-specific best practices
     */
    private function getPlatformBestPractices(string $platform): array
    {
        $practices = [
            'instagram' => [
                'optimal_length' => '125-150 characters',
                'hashtag_count' => '5-10',
                'best_media' => 'carousel posts',
                'tone' => 'visual, authentic',
            ],
            'facebook' => [
                'optimal_length' => '80-100 characters',
                'hashtag_count' => '1-2',
                'best_media' => 'video',
                'tone' => 'conversational, engaging',
            ],
            'twitter' => [
                'optimal_length' => '71-100 characters',
                'hashtag_count' => '1-2',
                'best_media' => 'images, threads',
                'tone' => 'concise, witty',
            ],
            'linkedin' => [
                'optimal_length' => '150-200 characters',
                'hashtag_count' => '3-5',
                'best_media' => 'documents, articles',
                'tone' => 'professional, thought-leadership',
            ],
            'tiktok' => [
                'optimal_length' => '50-100 characters',
                'hashtag_count' => '3-5',
                'best_media' => 'short-form video',
                'tone' => 'fun, trendy, authentic',
            ],
            'pinterest' => [
                'optimal_length' => '100-200 characters',
                'hashtag_count' => '2-5',
                'best_media' => 'vertical images',
                'tone' => 'inspirational, descriptive',
            ],
            'youtube' => [
                'optimal_length' => '150-300 characters',
                'hashtag_count' => '3-5',
                'best_media' => 'video',
                'tone' => 'informative, entertaining',
            ],
        ];

        return $practices[$platform] ?? [];
    }

    /**
     * Get performance metrics for the agency
     */
    private function getPerformanceMetrics(Agency $agency): array
    {
        $topPosts = SocialPost::where('agency_id', $agency->id)
            ->where('status', 'published')
            ->orderByDesc('engagement_rate')
            ->limit(5)
            ->get(['content', 'platform', 'engagement_rate'])
            ->toArray();

        $bestTimes = SocialPost::where('agency_id', $agency->id)
            ->where('status', 'published')
            ->selectRaw('HOUR(published_at) as hour, AVG(engagement_rate) as avg_engagement')
            ->groupBy('hour')
            ->orderByDesc('avg_engagement')
            ->limit(3)
            ->pluck('hour')
            ->toArray();

        return [
            'top_posts' => $topPosts,
            'best_times' => $bestTimes,
        ];
    }

    /**
     * Get campaign history
     */
    private function getCampaignHistory(Campaign $campaign): array
    {
        return SocialPost::where('campaign_id', $campaign->id)
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->limit(10)
            ->get(['content', 'platform', 'engagement_rate', 'published_at'])
            ->toArray();
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->user?->id,
            'agency_id' => $this->user?->agency_id,
            'role' => $this->user?->role,
            'language' => $this->userPreferences['language'] ?? 'en',
            'tone' => $this->userPreferences['tone'] ?? 'professional',
            'brand_voice' => $this->brandVoice,
            'recent_actions' => array_slice($this->recentActions, 0, 5),
            'performance' => $this->performanceMetrics,
        ];
    }
}
