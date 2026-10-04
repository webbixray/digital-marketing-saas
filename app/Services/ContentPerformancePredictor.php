<?php

namespace App\Services;

class ContentPerformancePredictor
{
    /**
     * Predict engagement score (0-100) for a piece of content.
     *
     * @param  string  $content  The post content text
     * @param  string  $platform  Platform identifier (e.g., twitter, instagram, facebook, linkedin)
     * @param  array  $hashtags  Array of hashtags (without # symbol)
     * @param  array  $emojis  Array of emojis used in content
     * @return int Predicted engagement score (0-100)
     */
    /**
     * @param  array<int, string>  $hashtags
     * @param  array<int, string>  $emojis
     */
    public function predict(string $content, string $platform, array $hashtags = [], array $emojis = []): int
    {
        $score = 0;

        // Content length contribution (max 40 points)
        $score += $this->scoreLength($content, $platform) * 0.4;

        // Hashtag contribution (max 30 points)
        $score += $this->scoreHashtags($hashtags, $platform) * 0.3;

        // Emoji contribution (max 30 points)
        $score += $this->scoreEmojis($emojis, $platform) * 0.3;

        return (int) round(min(100, max(0, $score)));
    }

    /**
     * Score content length based on platform-specific optimal ranges.
     */
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
            if ($length > 280 && $length <= 500) {
                return 60;
            }

            return 30;
        }

        if ($platform === 'instagram') {
            // Instagram: 100-500 chars is ideal
            if ($length >= 100 && $length <= 500) {
                return 100;
            }
            if ($length > 0 && $length < 100) {
                return 70;
            }
            if ($length > 500 && $length <= 2200) {
                return 80;
            }

            return 50;
        }

        if ($platform === 'linkedin') {
            // LinkedIn: 200-600 chars is ideal
            if ($length >= 200 && $length <= 600) {
                return 100;
            }
            if ($length > 0 && $length < 200) {
                return 70;
            }
            if ($length > 600 && $length <= 3000) {
                return 80;
            }

            return 50;
        }

        // Facebook / default: 100-500 chars is ideal
        if ($length >= 100 && $length <= 500) {
            return 100;
        }
        if ($length > 0 && $length < 100) {
            return 70;
        }
        if ($length > 500) {
            return 80;
        }

        return 0;
    }

    /**
     * Score hashtag usage based on platform-specific optimal counts.
     */
    /**
     * @param  array<int, string>  $hashtags
     */
    protected function scoreHashtags(array $hashtags, string $platform): int
    {
        $count = count($hashtags);

        if ($platform === 'instagram') {
            // Instagram: 5-15 hashtags is ideal
            if ($count >= 5 && $count <= 15) {
                return 100;
            }
            if ($count > 0 && $count < 5) {
                return 70;
            }
            if ($count > 15 && $count <= 30) {
                return 80;
            }

            return 40;
        }

        if ($platform === 'twitter') {
            // Twitter: 1-3 hashtags is ideal
            if ($count >= 1 && $count <= 3) {
                return 100;
            }
            if ($count === 0) {
                return 60;
            }

            return 50;
        }

        if ($platform === 'linkedin') {
            // LinkedIn: 3-5 hashtags is ideal
            if ($count >= 3 && $count <= 5) {
                return 100;
            }
            if ($count > 0 && $count < 3) {
                return 70;
            }
            if ($count > 5 && $count <= 10) {
                return 80;
            }

            return 50;
        }

        // Facebook / default: 2-5 hashtags is good
        if ($count >= 2 && $count <= 5) {
            return 100;
        }
        if ($count === 1) {
            return 70;
        }
        if ($count > 5 && $count <= 10) {
            return 80;
        }

        return 50;
    }

    /**
     * Score emoji usage based on platform-specific norms.
     */
    /**
     * @param  array<int, string>  $emojis
     */
    protected function scoreEmojis(array $emojis, string $platform): int
    {
        $count = count($emojis);

        if ($platform === 'linkedin') {
            // LinkedIn: 0-2 emojis is ideal (professional)
            if ($count >= 0 && $count <= 2) {
                return 100;
            }
            if ($count > 2 && $count <= 5) {
                return 70;
            }

            return 40;
        }

        if ($platform === 'twitter') {
            // Twitter: 1-3 emojis is ideal
            if ($count >= 1 && $count <= 3) {
                return 100;
            }
            if ($count === 0) {
                return 70;
            }
            if ($count > 3 && $count <= 6) {
                return 80;
            }

            return 50;
        }

        if ($platform === 'instagram') {
            // Instagram: 3-10 emojis is ideal
            if ($count >= 3 && $count <= 10) {
                return 100;
            }
            if ($count > 0 && $count < 3) {
                return 70;
            }
            if ($count === 0) {
                return 50;
            }

            return 60;
        }

        // Facebook / default: 1-5 emojis is good
        if ($count >= 1 && $count <= 5) {
            return 100;
        }
        if ($count === 0) {
            return 70;
        }
        if ($count > 5 && $count <= 10) {
            return 80;
        }

        return 50;
    }

    /**
     * Get engagement label for a predicted score.
     */
    public function getEngagementLabel(int $score): string
    {
        if ($score >= 80) {
            return 'high';
        }
        if ($score >= 60) {
            return 'medium';
        }
        if ($score >= 40) {
            return 'low';
        }

        return 'very_low';
    }
}
