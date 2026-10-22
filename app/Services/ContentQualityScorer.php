<?php

namespace App\Services;

use App\Models\SocialPost;

class ContentQualityScorer
{
    /**
     * Score a social media post on quality metrics.
     */
    public function score(SocialPost $post): int
    {
        $content = $post->content ?? '';
        $platform = $post->platform;
        $media = $post->media ?? [];
        $hashtags = $post->hashtags ?? [];
        $links = $post->links ?? [];

        $score = 0;
        $weights = config('platform.quality_scoring.weights', []);

        // Length score
        $lengthScore = $this->scoreLength($content, $platform);
        $score += $lengthScore * ($weights['length'] ?? 10) / 100;

        // Hashtag score
        $hashtagScore = $this->scoreHashtags($hashtags, $platform);
        $score += $hashtagScore * ($weights['hashtags'] ?? 15) / 100;

        // Media score
        $mediaScore = $this->scoreMedia($media);
        $score += $mediaScore * ($weights['media'] ?? 15) / 100;

        // Link score
        $linkScore = $this->scoreLinks($links, $platform);
        $score += $linkScore * ($weights['links'] ?? 10) / 100;

        // Readability score
        $readabilityScore = $this->scoreReadability($content);
        $score += $readabilityScore * ($weights['readability'] ?? 15) / 100;

        // Sentiment score
        $sentimentScore = $this->scoreSentiment($content);
        $score += $sentimentScore * ($weights['sentiment'] ?? 10) / 100;

        return (int) round(min(100, max(0, $score)));
    }

    protected function scoreLength(string $content, string $platform): int
    {
        $length = strlen($content);

        if ($platform === 'twitter') {
            // Twitter: 70-280 chars is ideal
            if ($length >= 70 && $length <= 280) {
                return 100;
            }
            if ($length > 0 && $length < 70) {
                return 70;
            }

            return 0;
        }

        // Other platforms: more is fine
        if ($length >= 50 && $length <= 500) {
            return 100;
        }
        if ($length > 0 && $length < 50) {
            return 70;
        }
        if ($length > 500) {
            return 80;
        }

        return 0;
    }

    protected function scoreHashtags(array $hashtags, string $platform): int
    {
        $count = count($hashtags);

        if ($platform === 'instagram') {
            // Instagram: 5-15 is ideal
            if ($count >= 5 && $count <= 15) {
                return 100;
            }
            if ($count > 0 && $count < 5) {
                return 70;
            }
            if ($count > 15 && $count <= 30) {
                return 80;
            }

            return 50;
        }

        if ($platform === 'twitter') {
            // Twitter: 1-3 is ideal
            if ($count >= 1 && $count <= 3) {
                return 100;
            }
            if ($count > 0 && $count < 1) {
                return 70;
            }

            return 50;
        }

        // Default: 3-10 is good
        if ($count >= 3 && $count <= 10) {
            return 100;
        }
        if ($count > 0 && $count < 3) {
            return 70;
        }

        return 80;
    }

    protected function scoreMedia(array $media): int
    {
        if (count($media) > 0) {
            return 100;
        }

        return 60;
    }

    protected function scoreLinks(array $links, string $platform): int
    {
        $count = count($links);

        if ($platform === 'twitter') {
            if ($count <= 2) {
                return 100;
            }

            return 70;
        }

        if ($count <= 3) {
            return 100;
        }

        return 70;
    }

    protected function scoreReadability(string $content): int
    {
        $wordCount = str_word_count($content);
        $sentenceCount = preg_match_all('/[.!?]+/', $content) ?: 1;

        if ($wordCount === 0) {
            return 50;
        }

        $avgWordsPerSentence = $wordCount / $sentenceCount;

        // Ideal: 10-20 words per sentence
        if ($avgWordsPerSentence >= 10 && $avgWordsPerSentence <= 20) {
            return 100;
        }
        if ($avgWordsPerSentence > 0 && $avgWordsPerSentence < 10) {
            return 80;
        }

        return 70;
    }

    protected function scoreSentiment(string $content): int
    {
        $positiveWords = ['great', 'amazing', 'love', 'excellent', 'best', 'exciting', 'wonderful', 'fantastic'];
        $negativeWords = ['bad', 'terrible', 'worst', 'awful', 'hate', 'disappointing'];

        $contentLower = strtolower($content);
        $positive = 0;
        $negative = 0;

        foreach ($positiveWords as $word) {
            if (str_contains($contentLower, $word)) {
                $positive++;
            }
        }

        foreach ($negativeWords as $word) {
            if (str_contains($contentLower, $word)) {
                $negative++;
            }
        }

        if ($positive > $negative) {
            return 100;
        }
        if ($positive === $negative) {
            return 80;
        }

        return 60;
    }

    /**
     * Get label for a score.
     */
    public function getLabel(int $score): string
    {
        $thresholds = config('platform.quality_scoring.thresholds', []);

        if ($score >= ($thresholds['excellent'] ?? 80)) {
            return 'excellent';
        }
        if ($score >= ($thresholds['good'] ?? 60)) {
            return 'good';
        }
        if ($score >= ($thresholds['fair'] ?? 40)) {
            return 'fair';
        }

        return 'poor';
    }
}
