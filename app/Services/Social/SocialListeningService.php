<?php

namespace App\Services\Social;

use App\Models\SocialAccount;
use App\Models\SocialListening;
use App\Services\AI\SentimentAnalysisService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SocialListeningService
{
    /**
     * Add a new keyword to monitor.
     */
    public function addKeyword(int $agencyId, string $keyword, string $platform = 'all'): SocialListening
    {
        return SocialListening::firstOrCreate([
            'agency_id' => $agencyId,
            'keyword' => strtolower(trim($keyword)),
            'platform' => $platform,
        ]);
    }

    /**
     * Remove a keyword from monitoring.
     */
    public function removeKeyword(int $agencyId, int $keywordId): bool
    {
        $keyword = SocialListening::where('agency_id', $agencyId)->findOrFail($keywordId);
        return $keyword->delete();
    }

    /**
     * Get all keywords for an agency.
     */
    public function getKeywords(int $agencyId, ?string $platform = null): Collection
    {
        $query = SocialListening::where('agency_id', $agencyId)->active();

        if ($platform) {
            $query->byPlatform($platform);
        }

        return $query->orderBy('match_count', 'desc')->get();
    }

    /**
     * Check mentions across all active keywords for an agency.
     */
    public function checkMentions(int $agencyId): array
    {
        $keywords = SocialListening::where('agency_id', $agencyId)->active()->get();
        $results = [];
        $totalNewMentions = 0;

        foreach ($keywords as $keyword) {
            $mentions = $this->fetchMentionsForKeyword($keyword);
            $totalNewMentions += count($mentions);
            $results[$keyword->id] = $mentions;
        }

        return [
            'total_keywords' => $keywords->count(),
            'total_new_mentions' => $totalNewMentions,
            'results' => $results,
        ];
    }

    /**
     * Fetch mentions for a specific keyword from all connected accounts.
     */
    protected function fetchMentionsForKeyword(SocialListening $keyword): array
    {
        $accounts = SocialAccount::where('agency_id', $keyword->agency_id)
            ->active()
            ->when($keyword->platform !== 'all', fn ($q) => $q->byPlatform($keyword->platform))
            ->get();

        $allMentions = [];

        foreach ($accounts as $account) {
            $mentions = $this->fetchFromPlatform($account, $keyword->keyword);

            if (! empty($mentions)) {
                foreach ($mentions as $mention) {
                    $sentiment = $this->analyzeSentiment($mention['text'] ?? $mention['message'] ?? '');
                    $mention['sentiment'] = $sentiment;
                    $mention['keyword_id'] = $keyword->id;
                    $mention['platform'] = $account->platform;

                    // Update sentiment counts
                    $this->updateSentimentCounts($keyword, $sentiment);

                    $allMentions[] = $mention;
                }
            }
        }

        // Update keyword stats
        $keyword->update([
            'match_count' => $keyword->match_count + count($allMentions),
            'last_checked_at' => now(),
        ]);

        return $allMentions;
    }

    /**
     * Fetch mentions from a specific platform.
     */
    protected function fetchFromPlatform(SocialAccount $account, string $keyword): array
    {
        try {
            return match ($account->platform) {
                'twitter' => $this->searchTwitterMentions($account, $keyword),
                'facebook' => $this->searchFacebookMentions($account, $keyword),
                'instagram' => $this->searchInstagramMentions($account, $keyword),
                'linkedin' => $this->searchLinkedInMentions($account, $keyword),
                'tiktok' => $this->searchTikTokMentions($account, $keyword),
                default => [],
            };
        } catch (\Exception $e) {
            Log::error("Social listening fetch failed for {$account->platform}: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Search Twitter/X for mentions.
     */
    protected function searchTwitterMentions(SocialAccount $account, string $keyword): array
    {
        try {
            $url = 'https://api.twitter.com/2/tweets/search/recent';
            $response = Http::withToken($account->access_token)
                ->timeout(30)
                ->get($url, [
                    'query' => "\"{$keyword}\" -is:retweet",
                    'max_results' => 25,
                    'tweet.fields' => 'created_at,public_metrics,author_id',
                    'expansions' => 'author_id',
                ]);

            if ($response->successful()) {
                return $response->json()['data'] ?? [];
            }

            Log::warning("Twitter search failed: " . ($response->json()['detail'] ?? 'Unknown error'));
            return [];
        } catch (\Exception $e) {
            Log::error("Twitter keyword search error: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Search Facebook for mentions.
     */
    protected function searchFacebookMentions(SocialAccount $account, string $keyword): array
    {
        try {
            $url = "https://graph.facebook.com/v18.0/{$account->platform_account_id}/feed";
            $response = Http::timeout(30)->get($url, [
                'access_token' => $account->access_token,
                'fields' => 'message,comments.limit(50){message,from,created_time}',
                'limit' => 25,
            ]);

            if ($response->successful()) {
                $data = $response->json()['data'] ?? [];
                return $this->filterByKeyword($data, $keyword);
            }

            return [];
        } catch (\Exception $e) {
            Log::error("Facebook keyword search error: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Search Instagram for mentions.
     */
    protected function searchInstagramMentions(SocialAccount $account, string $keyword): array
    {
        try {
            $url = "https://graph.facebook.com/v18.0/{$account->platform_account_id}/media";
            $response = Http::timeout(30)->get($url, [
                'access_token' => $account->access_token,
                'fields' => 'caption,comments.limit(50){text,username,timestamp}',
                'limit' => 25,
            ]);

            if ($response->successful()) {
                $data = $response->json()['data'] ?? [];
                return $this->filterByKeyword($data, $keyword);
            }

            return [];
        } catch (\Exception $e) {
            Log::error("Instagram keyword search error: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Search LinkedIn for mentions.
     */
    protected function searchLinkedInMentions(SocialAccount $account, string $keyword): array
    {
        try {
            $url = 'https://api.linkedin.com/v2/posts';
            $response = Http::withToken($account->access_token)
                ->timeout(30)
                ->get($url, [
                    'q' => 'author',
                    'author' => "urn:li:organization:{$account->platform_account_id}",
                    'count' => 25,
                ]);

            if ($response->successful()) {
                $data = $response->json()['elements'] ?? [];
                return $this->filterByKeyword($data, $keyword);
            }

            return [];
        } catch (\Exception $e) {
            Log::error("LinkedIn keyword search error: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Search TikTok for mentions.
     */
    protected function searchTikTokMentions(SocialAccount $account, string $keyword): array
    {
        try {
            $url = 'https://open.tiktokapis.com/v2/research/video/query/';
            $response = Http::withToken($account->access_token)
                ->timeout(30)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, [
                    'query' => [
                        'and' => [['operation' => 'IN', 'field_name' => 'keyword', 'field_values' => [$keyword]]],
                    ],
                    'max_count' => 25,
                ]);

            if ($response->successful()) {
                return $response->json()['data']['videos'] ?? [];
            }

            return [];
        } catch (\Exception $e) {
            Log::error("TikTok keyword search error: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Filter data by keyword presence.
     */
    protected function filterByKeyword(array $data, string $keyword): array
    {
        return array_filter($data, function ($item) use ($keyword) {
            $text = strtolower(json_encode($item));
            return str_contains($text, strtolower($keyword));
        });
    }

    /**
     * Analyze sentiment of text using AI.
     */
    public function analyzeSentiment(string $text): string
    {
        // Try AI-based sentiment analysis first
        try {
            $service = app(SentimentAnalysisService::class);
            if ($service) {
                return $service->analyze($text);
            }
        } catch (\Exception $e) {
            Log::debug('AI sentiment service unavailable, using rule-based: ' . $e->getMessage());
        }

        // Fallback to rule-based sentiment analysis
        return $this->ruleBasedSentiment($text);
    }

    /**
     * Rule-based sentiment analysis (fallback).
     */
    protected function ruleBasedSentiment(string $text): string
    {
        $positiveWords = [
            'love', 'great', 'amazing', 'excellent', 'awesome', 'fantastic',
            'wonderful', 'perfect', 'best', 'happy', 'pleased', 'delighted',
            'outstanding', 'superb', 'brilliant', 'impressive', 'thank', 'thanks',
            'recommend', 'beautiful', 'nice', 'good', 'enjoy', 'favorite',
            'excited', 'glad', 'incredible', 'satisfied', 'helpful',
        ];

        $negativeWords = [
            'hate', 'terrible', 'awful', 'horrible', 'worst', 'bad', 'poor',
            'disappointed', 'disappointing', 'useless', 'broken', 'fail',
            'failed', 'failure', 'problem', 'issue', 'bug', 'slow', 'crash',
            'never', 'waste', 'wrong', 'annoying', 'frustrating', 'angry',
            'sad', 'unhappy', 'regret', 'complaint', 'refund',
        ];

        $text = strtolower($text);
        $score = 0;

        foreach ($positiveWords as $word) {
            if (str_contains($text, $word)) {
                $score++;
            }
        }

        foreach ($negativeWords as $word) {
            if (str_contains($text, $word)) {
                $score--;
            }
        }

        if ($score > 0) {
            return 'positive';
        } elseif ($score < 0) {
            return 'negative';
        }

        return 'neutral';
    }

    /**
     * Update sentiment counts for a keyword.
     */
    protected function updateSentimentCounts(SocialListening $keyword, string $sentiment): void
    {
        $column = "sentiment_{$sentiment}";
        if (in_array($column, ['sentiment_positive', 'sentiment_negative', 'sentiment_neutral'])) {
            $keyword->increment($column);
        }
    }

    /**
     * Get dashboard statistics for social listening.
     */
    public function getDashboardStats(int $agencyId): array
    {
        $keywords = SocialListening::where('agency_id', $agencyId)->get();
        $activeKeywords = $keywords->where('is_active', true);

        $totalPositive = $keywords->sum('sentiment_positive');
        $totalNegative = $keywords->sum('sentiment_negative');
        $totalNeutral = $keywords->sum('sentiment_neutral');
        $totalMentions = $totalPositive + $totalNegative + $totalNeutral;

        $sentimentPercentages = [
            'positive' => $totalMentions > 0 ? round(($totalPositive / $totalMentions) * 100, 1) : 0,
            'negative' => $totalMentions > 0 ? round(($totalNegative / $totalMentions) * 100, 1) : 0,
            'neutral' => $totalMentions > 0 ? round(($totalNeutral / $totalMentions) * 100, 1) : 0,
        ];

        $platformBreakdown = $keywords->groupBy('platform')->map(fn ($group) => [
            'count' => $group->count(),
            'mentions' => $group->sum('match_count'),
            'positive' => $group->sum('sentiment_positive'),
            'negative' => $group->sum('sentiment_negative'),
            'neutral' => $group->sum('sentiment_neutral'),
        ]);

        return [
            'total_keywords' => $keywords->count(),
            'active_keywords' => $activeKeywords->count(),
            'total_mentions' => $totalMentions,
            'total_positive' => $totalPositive,
            'total_negative' => $totalNegative,
            'total_neutral' => $totalNeutral,
            'sentiment_percentages' => $sentimentPercentages,
            'overall_sentiment_score' => $totalMentions > 0
                ? round((($totalPositive - $totalNegative) / $totalMentions) * 100, 2)
                : 0,
            'platform_breakdown' => $platformBreakdown,
            'top_keywords' => $keywords->sortByDesc('match_count')->take(5)->values(),
            'recent_activity' => $keywords->where('last_checked_at', '>', now()->subDay())->count(),
        ];
    }

    /**
     * Get recent mention feed with sentiment.
     */
    public function getMentionFeed(int $agencyId, int $limit = 50): Collection
    {
        return SocialListening::where('agency_id', $agencyId)
            ->active()
            ->orderBy('last_checked_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn ($keyword) => [
                'id' => $keyword->id,
                'keyword' => $keyword->keyword,
                'platform' => $keyword->platform,
                'match_count' => $keyword->match_count,
                'sentiment_score' => $keyword->sentiment_score,
                'last_checked' => $keyword->last_checked_at?->diffForHumans(),
            ]);
    }

    /**
     * Fetch recent mentions from Twitter (legacy compatibility).
     */
    public function getTwitterMentions(SocialAccount $account, int $count = 20): array
    {
        try {
            $url = "https://api.twitter.com/2/users/{$account->platform_account_id}/mentions";
            $response = Http::withToken($account->access_token)
                ->timeout(30)
                ->get($url, [
                    'max_results' => $count,
                    'tweet.fields' => 'created_at,public_metrics,author_id',
                    'expansions' => 'author_id',
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()['data'] ?? [],
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['detail'] ?? 'Failed to fetch mentions',
            ];
        } catch (\Exception $e) {
            Log::error('Twitter mentions fetch failed: ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Fetch recent comments from Facebook page (legacy compatibility).
     */
    public function getFacebookComments(SocialAccount $account, int $count = 20): array
    {
        try {
            $url = "https://graph.facebook.com/v18.0/{$account->platform_account_id}/feed";
            $response = Http::timeout(30)->get($url, [
                'access_token' => $account->access_token,
                'fields' => 'message,comments.limit(' . $count . '){message,from,created_time}',
                'limit' => $count,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()['data'] ?? [],
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['error']['message'] ?? 'Failed to fetch comments',
            ];
        } catch (\Exception $e) {
            Log::error('Facebook comments fetch failed: ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Fetch recent Instagram comments (legacy compatibility).
     */
    public function getInstagramComments(SocialAccount $account, int $count = 20): array
    {
        try {
            $url = "https://graph.facebook.com/v18.0/{$account->platform_account_id}/media";
            $response = Http::timeout(30)->get($url, [
                'access_token' => $account->access_token,
                'fields' => 'caption,comments.limit(' . $count . '){text,username,timestamp}',
                'limit' => $count,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()['data'] ?? [],
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['error']['message'] ?? 'Failed to fetch comments',
            ];
        } catch (\Exception $e) {
            Log::error('Instagram comments fetch failed: ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Fetch data from the appropriate platform (legacy compatibility).
     */
    public function fetchPlatformData(SocialAccount $account, int $count = 20): array
    {
        return match ($account->platform) {
            'twitter' => $this->getTwitterMentions($account, $count),
            'facebook' => $this->getFacebookComments($account, $count),
            'instagram' => $this->getInstagramComments($account, $count),
            default => ['success' => false, 'error' => "Listening not supported for {$account->platform}"],
        };
    }
}
