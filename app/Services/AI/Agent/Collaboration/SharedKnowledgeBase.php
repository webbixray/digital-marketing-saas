<?php

namespace App\Services\AI\Agent\Collaboration;

use App\Models\AgentSharedKnowledge;
use Illuminate\Support\Facades\Log;

class SharedKnowledgeBase
{
    private const MAX_INSIGHTS_PER_CATEGORY = 500;

    /**
     * Share an insight from an agent to the knowledge base.
     */
    public function shareInsight(int $agencyId, string $fromAgent, string $insight, string $category, float $confidence = 1.0): void
    {
        AgentSharedKnowledge::create([
            'agency_id' => $agencyId,
            'from_agent' => $fromAgent,
            'insight' => $insight,
            'category' => $category,
            'confidence' => $confidence,
        ]);

        Log::debug("SharedKnowledgeBase: insight shared by [{$fromAgent}] in category [{$category}] for agency [{$agencyId}]");
    }

    /**
     * Get insights for an agency and category.
     */
    public function getInsights(int $agencyId, string $category): array
    {
        return AgentSharedKnowledge::byAgency($agencyId)
            ->byCategory($category)
            ->recent(self::MAX_INSIGHTS_PER_CATEGORY)
            ->get()
            ->map(fn ($row) => [
                'from_agent' => $row->from_agent,
                'insight' => $row->insight,
                'category' => $row->category,
                'agency_id' => $row->agency_id,
                'confidence' => (float) $row->confidence,
                'created_at' => $row->created_at->toIso8601String(),
            ])
            ->toArray();
    }

    /**
     * Get patterns learned across multiple agents.
     */
    public function getCrossAgentPatterns(int $agencyId): array
    {
        $crossAgentCategories = AgentSharedKnowledge::byAgency($agencyId)
            ->crossAgent()
            ->get();

        $patterns = [];
        foreach ($crossAgentCategories as $row) {
            $category = $row->category;
            $insights = $this->getInsights($agencyId, $category);
            $agents = array_unique(array_column($insights, 'from_agent'));

            $patterns[$category] = [
                'category' => $category,
                'agent_count' => $row->agent_count,
                'agents' => array_values($agents),
                'insight_count' => $row->insight_count,
                'latest_insights' => array_slice($insights, -5),
            ];
        }

        return $patterns;
    }

    /**
     * Get best practices learned from cross-agent insights.
     */
    public function getBestPractices(int $agencyId, string $domain): array
    {
        $insights = AgentSharedKnowledge::byAgency($agencyId)
            ->where(function ($query) use ($domain) {
                $query->where('category', 'like', "%{$domain}%")
                    ->orWhere('insight', 'like', "%{$domain}%");
            })
            ->get();

        if ($insights->isEmpty()) {
            return [];
        }

        // Aggregate best practices by agent
        $practicesByAgent = [];
        foreach ($insights as $insight) {
            $agent = $insight->from_agent;
            if (! isset($practicesByAgent[$agent])) {
                $practicesByAgent[$agent] = [];
            }
            $practicesByAgent[$agent][] = [
                'insight' => $insight->insight,
                'confidence' => (float) $insight->confidence,
                'created_at' => $insight->created_at->toIso8601String(),
            ];
        }

        $bestPractices = [];
        foreach ($practicesByAgent as $agent => $practices) {
            // Sort by confidence descending, then by recency
            usort($practices, function ($a, $b) {
                $confDiff = $b['confidence'] <=> $a['confidence'];
                if ($confDiff !== 0) {
                    return $confDiff;
                }

                return strtotime($b['created_at']) <=> strtotime($a['created_at']);
            });

            $bestPractices[] = [
                'agent' => $agent,
                'practice_count' => count($practices),
                'practices' => array_column($practices, 'insight'),
                'avg_confidence' => round(array_sum(array_column($practices, 'confidence')) / count($practices), 4),
                'last_updated' => end($practices)['created_at'] ?? null,
            ];
        }

        // Sort by practice count descending
        usort($bestPractices, fn ($a, $b) => $b['practice_count'] <=> $a['practice_count']);

        return $bestPractices;
    }

    /**
     * Get collaboration statistics for an agency.
     */
    public function getCollaborationStats(int $agencyId): array
    {
        $baseQuery = AgentSharedKnowledge::byAgency($agencyId);

        $totalInsights = $baseQuery->count();
        $uniqueAgents = (clone $baseQuery)->distinct('from_agent')->count('from_agent');
        $uniqueCategories = (clone $baseQuery)->distinct('category')->count('category');
        $avgConfidence = (clone $baseQuery)->avg('confidence') ?? 0;

        // Top contributing agents
        $topAgents = (clone $baseQuery)
            ->select('from_agent')
            ->selectRaw('COUNT(*) as insight_count')
            ->groupBy('from_agent')
            ->orderByDesc('insight_count')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'agent' => $row->from_agent,
                'insight_count' => $row->insight_count,
            ])
            ->toArray();

        // Category breakdown
        $categories = (clone $baseQuery)
            ->select('category')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('COUNT(DISTINCT from_agent) as agent_count')
            ->groupBy('category')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'category' => $row->category,
                'insight_count' => $row->count,
                'agent_count' => $row->agent_count,
            ])
            ->toArray();

        // Cross-agent categories
        $crossAgentCategories = (clone $baseQuery)
            ->crossAgent()
            ->get()
            ->count();

        return [
            'total_insights' => $totalInsights,
            'unique_agents' => $uniqueAgents,
            'unique_categories' => $uniqueCategories,
            'avg_confidence' => round((float) $avgConfidence, 4),
            'cross_agent_categories' => $crossAgentCategories,
            'top_agents' => $topAgents,
            'categories' => $categories,
        ];
    }

    /**
     * Get insights filtered by minimum confidence threshold.
     */
    public function getHighConfidenceInsights(int $agencyId, string $category, float $minConfidence = 0.8): array
    {
        return AgentSharedKnowledge::byAgency($agencyId)
            ->byCategory($category)
            ->byMinConfidence($minConfidence)
            ->orderByDesc('confidence')
            ->get()
            ->map(fn ($row) => [
                'from_agent' => $row->from_agent,
                'insight' => $row->insight,
                'category' => $row->category,
                'confidence' => (float) $row->confidence,
                'created_at' => $row->created_at->toIso8601String(),
            ])
            ->toArray();
    }
}
