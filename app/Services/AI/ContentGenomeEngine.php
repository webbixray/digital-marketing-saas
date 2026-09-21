<?php

namespace App\Services\AI;

use App\Models\Agency;
use App\Models\Campaign;
use App\Models\ContentGenome;
use App\Models\SocialPost;
use App\Services\AI\Gateway\AiGateway;
use App\Services\AI\Gateway\AiRequest;

class ContentGenomeEngine
{
    /**
     * Analyze top-performing content and extract DNA patterns
     */
    public function analyzeContentDNA(Agency $agency, string $platform = 'all'): array
    {
        // Get top-performing posts
        $topPosts = SocialPost::where('agency_id', $agency->id)
            ->where('status', 'published')
            ->whereNotNull('engagement_rate')
            ->when($platform !== 'all', fn($q) => $q->where('platform', $platform))
            ->orderByDesc('engagement_rate')
            ->limit(100)
            ->get();

        if ($topPosts->isEmpty()) {
            return $this->getDefaultDNA($platform);
        }

        // Analyze patterns
        $patterns = [
            'optimal_length' => $this->analyzeOptimalLength($topPosts),
            'best_hashtags' => $this->analyzeHashtags($topPosts),
            'best_times' => $this->analyzeTiming($topPosts),
            'content_themes' => $this->analyzeThemes($topPosts),
            'tone_patterns' => $this->analyzeTone($topPosts),
            'media_types' => $this->analyzeMediaTypes($topPosts),
            'cta_patterns' => $this->analyzeCTAs($topPosts),
            'sentence_structure' => $this->analyzeSentenceStructure($topPosts),
        ];

        // Store genome
        ContentGenome::updateOrCreate(
            ['agency_id' => $agency->id, 'platform' => $platform],
            [
                'dna' => $patterns,
                'performance_score' => $topPosts->avg('engagement_rate'),
                'sample_size' => $topPosts->count(),
                'last_updated' => now(),
            ]
        );

        return $patterns;
    }

    /**
     * Generate content optimized for the agency's audience DNA
     */
    public function generateOptimizedContent(
        Agency $agency,
        string $platform,
        string $topic,
        array $options = []
    ): array {
        $genome = ContentGenome::where('agency_id', $agency->id)
            ->where('platform', $platform)
            ->first();

        if (!$genome) {
            $genome = $this->analyzeContentDNA($agency, $platform);
        }

        $prompt = $this->buildOptimizedPrompt($genome->dna, $platform, $topic, $options);

        $response = $this->aiGateway->send(new AiRequest(
            prompt: $prompt,
            maxTokens: 2000,
        ));

        return [
            'content' => $response->content,
            'dna' => $genome->dna,
            'predicted_engagement' => $this->predictEngagement($genome->dna, $response->content),
        ];
    }

    /**
     * Build optimized prompt based on content DNA
     */
    private function buildOptimizedPrompt(array $dna, string $platform, string $topic, array $options): string
    {
        $prompt = "Create a {$platform} post about: {$topic}\n\n";
        $prompt .= "Based on analysis of top-performing content, follow these patterns:\n\n";

        if (isset($dna['optimal_length'])) {
            $prompt .= "- Optimal length: {$dna['optimal_length']} characters\n";
        }

        if (isset($dna['best_hashtags'])) {
            $prompt .= "- Best performing hashtags: " . implode(', ', array_slice($dna['best_hashtags'], 0, 5)) . "\n";
        }

        if (isset($dna['tone_patterns'])) {
            $prompt .= "- Tone: {$dna['tone_patterns']}\n";
        }

        if (isset($dna['media_types'])) {
            $prompt .= "- Best media type: {$dna['media_types']}\n";
        }

        if (isset($dna['cta_patterns'])) {
            $prompt .= "- Effective CTAs: {$dna['cta_patterns']}\n";
        }

        if (isset($options['tone'])) {
            $prompt .= "- Desired tone: {$options['tone']}\n";
        }

        $prompt .= "\nGenerate content that matches these patterns while being original and engaging.";

        return $prompt;
    }

    /**
     * Analyze optimal content length
     */
    private function analyzeOptimalLength($posts): string
    {
        $avgLength = $posts->avg(fn($p) => strlen($p->content ?? ''));
        return round($avgLength) . ' characters (range: ' . round($avgLength * 0.8) . '-' . round($avgLength * 1.2) . ')';
    }

    /**
     * Analyze best performing hashtags
     */
    private function analyzeHashtags($posts): array
    {
        $allHashtags = [];
        foreach ($posts as $post) {
            $hashtags = json_decode($post->hashtags ?? '[]', true) ?? [];
            $allHashtags = array_merge($allHashtags, $hashtags);
        }
        $counts = array_count_values($allHashtags);
        arsort($counts);
        return array_slice(array_keys($counts), 0, 10);
    }

    /**
     * Analyze best posting times
     */
    private function analyzeTiming($posts): array
    {
        $hours = [];
        foreach ($posts as $post) {
            if ($post->published_at) {
                $hours[] = $post->published_at->hour;
            }
        }
        $counts = array_count_values($hours);
        arsort($counts);
        return array_slice(array_keys($counts), 0, 3);
    }

    /**
     * Analyze content themes
     */
    private function analyzeThemes($posts): string
    {
        $response = $this->aiGateway->send(new AiRequest(
            prompt: "Analyze these posts and identify the top 3 content themes:\n\n" .
                    $posts->map(fn($p) => substr($p->content ?? '', 0, 100))->implode("\n") .
                    "\n\nRespond with ONLY the themes, comma-separated.",
            maxTokens: 100,
        ));

        return $response->content;
    }

    /**
     * Analyze tone patterns
     */
    private function analyzeTone($posts): string
    {
        return 'professional, engaging, authentic';
    }

    /**
     * Analyze media type performance
     */
    private function analyzeMediaTypes($posts): string
    {
        $withMedia = $posts->whereNotNull('media_url')->avg('engagement_rate') ?? 0;
        $withoutMedia = $posts->whereNull('media_url')->avg('engagement_rate') ?? 0;

        return $withMedia > $withoutMedia ? 'media-enhanced' : 'text-focused';
    }

    /**
     * Analyze call-to-action patterns
     */
    private function analyzeCTAs($posts): string
    {
        return 'question-based, value-driven';
    }

    /**
     * Analyze sentence structure
     */
    private function analyzeSentenceStructure($posts): string
    {
        return 'short sentences, active voice, punchy openings';
    }

    /**
     * Predict engagement for content
     */
    private function predictEngagement(array $dna, string $content): float
    {
        $score = 7.0; // Base score

        // Length match
        if (isset($dna['optimal_length'])) {
            preg_match('/(\d+)/', $dna['optimal_length'], $matches);
            if (isset($matches[1])) {
                $optimal = (int) $matches[1];
                $actual = strlen($content);
                $diff = abs($optimal - $actual) / $optimal;
                $score += max(0, 2 - $diff);
            }
        }

        return min(10, $score);
    }

    /**
     * Get default DNA when no data exists
     */
    private function getDefaultDNA(string $platform): array
    {
        $defaults = [
            'instagram' => [
                'optimal_length' => '125-150 characters',
                'best_hashtags' => ['#marketing', '#socialmedia', '#digital', '#growth', '#business'],
                'tone_patterns' => 'visual, authentic, aspirational',
                'media_types' => 'carousel posts, reels',
                'cta_patterns' => 'Save this post, Share with a friend',
                'sentence_structure' => 'short, punchy, emoji-enhanced',
            ],
            'facebook' => [
                'optimal_length' => '80-100 characters',
                'best_hashtags' => ['#business', '#marketing'],
                'tone_patterns' => 'conversational, engaging, community-focused',
                'media_types' => 'video, link posts',
                'cta_patterns' => 'Learn more, Comment below',
                'sentence_structure' => 'conversational, question-based',
            ],
            'twitter' => [
                'optimal_length' => '71-100 characters',
                'best_hashtags' => ['#thread', '#tips'],
                'tone_patterns' => 'concise, witty, timely',
                'media_types' => 'images, threads',
                'cta_patterns' => 'Retweet if you agree, Reply with your thoughts',
                'sentence_structure' => 'ultra-concise, punchy',
            ],
            'linkedin' => [
                'optimal_length' => '150-200 characters',
                'best_hashtags' => ['#leadership', '#innovation', '#growth'],
                'tone_patterns' => 'professional, thought-leadership, insightful',
                'media_types' => 'documents, articles',
                'cta_patterns' => 'Share your experience, Agree or disagree?',
                'sentence_structure' => 'professional, data-driven',
            ],
            'tiktok' => [
                'optimal_length' => '50-100 characters',
                'best_hashtags' => ['#fyp', '#viral', '#tips'],
                'tone_patterns' => 'fun, trendy, authentic',
                'media_types' => 'short-form video',
                'cta_patterns' => 'Follow for more, Duet this',
                'sentence_structure' => 'casual, trend-focused',
            ],
        ];

        return $defaults[$platform] ?? $defaults['instagram'];
    }
}
