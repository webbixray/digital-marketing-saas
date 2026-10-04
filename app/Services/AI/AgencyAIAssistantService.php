<?php

namespace App\Services\AI;

use App\Models\ActivityLog;
use App\Models\Agency;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\SocialPost;
use App\Models\User;
use App\Services\QuotaService;
use Illuminate\Support\Str;

class AgencyAIAssistantService
{
    public function __construct(
        private QuotaService $quota,
        private AiContentService $aiContent
    ) {}

    /**
     * Process a natural language command and return a response.
     */
    /**
     * @return array<string, mixed>
     */
    public function processCommand(Agency $agency, User $user, string $message): array
    {
        $intent = $this->detectIntent(strtolower(trim($message)));

        return match ($intent['action']) {
            'dashboard_stats' => $this->getDashboardStats($agency),
            'list_posts' => $this->listPosts($agency, $intent),
            'create_post' => $this->createPost($agency, $user, $intent),
            'schedule_post' => $this->schedulePost($agency, $user, $intent),
            'list_campaigns' => $this->listCampaigns($agency, $intent),
            'create_campaign' => $this->createCampaign($agency, $intent),
            'list_clients' => $this->listClients($agency, $intent),
            'create_client' => $this->createClient($agency, $intent),
            'list_invoices' => $this->listInvoices($agency, $intent),
            'generate_content' => $this->generateContent($agency, $intent),
            'help' => $this->getHelp(),
            'quota_status' => $this->getQuotaStatus($agency),
            'recent_activity' => $this->getRecentActivity($agency),
            'search' => $this->performSearch($agency, $intent),
            'greeting' => $this->getGreeting($user),
            default => $this->getFallbackResponse($message),
        };
    }

    /**
     * Detect intent from natural language input.
     */
    /**
     * @return array<string, mixed>
     */
    private function detectIntent(string $message): array
    {
        $intents = [
            'dashboard_stats' => ['stats', 'dashboard', 'overview', 'summary', 'how am i doing', 'performance', 'metrics', 'analytics'],
            'list_posts' => ['posts', 'show posts', 'list posts', 'my posts', 'published posts', 'scheduled posts', 'failed posts'],
            'create_post' => ['create post', 'new post', 'write post', 'make post', 'add post', 'compose post'],
            'schedule_post' => ['schedule', 'post later', 'schedule post', 'queue post', 'plan post'],
            'list_campaigns' => ['campaigns', 'show campaigns', 'list campaigns', 'my campaigns'],
            'create_campaign' => ['create campaign', 'new campaign', 'launch campaign', 'add campaign'],
            'list_clients' => ['clients', 'show clients', 'list clients', 'my customers', 'customers'],
            'create_client' => ['add client', 'new client', 'create client', 'register client'],
            'list_invoices' => ['invoices', 'show invoices', 'list invoices', 'overdue', 'unpaid', 'paid invoices'],
            'generate_content' => ['write', 'generate', 'create content', 'ai write', 'compose', 'draft', 'hashtags', 'caption'],
            'quota_status' => ['quota', 'usage', 'remaining', 'limits', 'plan'],
            'recent_activity' => ['activity', 'recent', 'latest', 'what happened', 'updates'],
            'search' => ['search', 'find', 'look up', 'where is', 'show me'],
            'greeting' => ['hello', 'hi', 'hey', 'good morning', 'good afternoon', 'start', 'help'],
            'help' => ['help', 'commands', 'what can you do', 'how to', 'menu'],
        ];

        foreach ($intents as $action => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($message, $keyword)) {
                    return ['action' => $action, 'keywords' => $keywords];
                }
            }
        }

        return ['action' => 'unknown', 'keywords' => []];
    }

    /**
     * @return array<string, mixed>
     */
    private function getDashboardStats(Agency $agency): array
    {
        $posts = SocialPost::where('agency_id', $agency->id)->count();
        $published = SocialPost::where('agency_id', $agency->id)->where('status', 'published')->count();
        $scheduled = SocialPost::where('agency_id', $agency->id)->where('status', 'scheduled')->count();
        $failed = SocialPost::where('agency_id', $agency->id)->where('status', 'failed')->count();
        $campaigns = Campaign::where('agency_id', $agency->id)->count();
        $clients = Client::where('agency_id', $agency->id)->count();
        $invoices = Invoice::where('agency_id', $agency->id)->count();

        $text = "📊 **Dashboard Summary**\n\n";
        $text .= "📝 Total Posts: {$posts}\n";
        $text .= "✅ Published: {$published}\n";
        $text .= "⏰ Scheduled: {$scheduled}\n";
        $text .= "❌ Failed: {$failed}\n";
        $text .= "📢 Campaigns: {$campaigns}\n";
        $text .= "👥 Clients: {$clients}\n";
        $text .= "💰 Invoices: {$invoices}\n";

        return ['type' => 'text', 'content' => $text];
    }

    /**
     * @param  array<string, mixed>  $intent
     * @return array<string, mixed>
     */
    private function listPosts(Agency $agency, array $intent): array
    {
        $query = SocialPost::where('agency_id', $agency->id);

        if (str_contains(json_encode($intent), 'published')) {
            $query->where('status', 'published');
        } elseif (str_contains(json_encode($intent), 'scheduled')) {
            $query->where('status', 'scheduled');
        } elseif (str_contains(json_encode($intent), 'failed')) {
            $query->where('status', 'failed');
        }

        $posts = $query->orderBy('created_at', 'desc')->limit(5)->get();

        if ($posts->isEmpty()) {
            return ['type' => 'text', 'content' => 'No posts found.'];
        }

        $text = "📝 **Recent Posts**\n\n";
        foreach ($posts as $post) {
            $status = match ($post->status) {
                'published' => '✅',
                'scheduled' => '⏰',
                'failed' => '❌',
                default => '📝'
            };
            $text .= "{$status} ".Str::limit($post->content ?? '', 50)."\n";
        }

        return ['type' => 'text', 'content' => $text];
    }

    /**
     * @param  array<string, mixed>  $intent
     * @return array<string, mixed>
     */
    private function createPost(Agency $agency, User $user, array $intent): array
    {
        return ['type' => 'text', 'content' => "To create a post, use:\n\n/newpost [content]\n\nOr use the web panel for rich media posts."];
    }

    /**
     * @param  array<string, mixed>  $intent
     * @return array<string, mixed>
     */
    private function schedulePost(Agency $agency, User $user, array $intent): array
    {
        return ['type' => 'text', 'content' => "To schedule a post:\n\n/schedule [content] YYYY-MM-DD HH:MM\n\nExample: /schedule \"Launch day tomorrow!\" 2026-09-06 09:00"];
    }

    /**
     * @param  array<string, mixed>  $intent
     * @return array<string, mixed>
     */
    private function listCampaigns(Agency $agency, array $intent): array
    {
        $campaigns = Campaign::where('agency_id', $agency->id)->orderBy('created_at', 'desc')->limit(5)->get();

        if ($campaigns->isEmpty()) {
            return ['type' => 'text', 'content' => 'No campaigns found.'];
        }

        $text = "📢 **Recent Campaigns**\n\n";
        foreach ($campaigns as $campaign) {
            $status = match ($campaign->status) {
                'active' => '🟢',
                'paused' => '🟡',
                'completed' => '✅',
                default => '⚪'
            };
            $text .= "{$status} {$campaign->name} ({$campaign->status})\n";
        }

        return ['type' => 'text', 'content' => $text];
    }

    /**
     * @param  array<string, mixed>  $intent
     * @return array<string, mixed>
     */
    private function createCampaign(Agency $agency, array $intent): array
    {
        return ['type' => 'text', 'content' => "To create a campaign:\n\n/newcampaign [name] [type] [client]\n\nTypes: brand_awareness, lead_generation, engagement, traffic, conversions\nExample: /newcampaign \"Summer Sale\" brand_awareness"];
    }

    /**
     * @param  array<string, mixed>  $intent
     * @return array<string, mixed>
     */
    private function listClients(Agency $agency, array $intent): array
    {
        $query = Client::where('agency_id', $agency->id);

        if (str_contains(json_encode($intent), 'active')) {
            $query->where('status', 'active');
        } elseif (str_contains(json_encode($intent), 'lead')) {
            $query->where('status', 'lead');
        }

        $clients = $query->orderBy('created_at', 'desc')->limit(5)->get();

        if ($clients->isEmpty()) {
            return ['type' => 'text', 'content' => 'No clients found.'];
        }

        $text = "👥 **Recent Clients**\n\n";
        foreach ($clients as $client) {
            $status = match ($client->status) {
                'active' => '🟢',
                'lead' => '🟡',
                default => '⚪'
            };
            $text .= "{$status} {$client->name} ({$client->company})\n";
        }

        return ['type' => 'text', 'content' => $text];
    }

    /**
     * @param  array<string, mixed>  $intent
     * @return array<string, mixed>
     */
    private function createClient(Agency $agency, array $intent): array
    {
        return ['type' => 'text', 'content' => "To add a client:\n\n/addclient [name] | [email] | [company]\n\nExample: /addclient \"John Doe\" john@acme.com \"Acme Corp\""];
    }

    /**
     * @param  array<string, mixed>  $intent
     * @return array<string, mixed>
     */
    private function listInvoices(Agency $agency, array $intent): array
    {
        $query = Invoice::where('agency_id', $agency->id);

        if (str_contains(json_encode($intent), 'overdue')) {
            $query->where('status', 'overdue');
        } elseif (str_contains(json_encode($intent), 'paid')) {
            $query->where('status', 'paid');
        } elseif (str_contains(json_encode($intent), 'unpaid')) {
            $query->whereIn('status', ['sent', 'overdue']);
        }

        $invoices = $query->orderBy('created_at', 'desc')->limit(5)->get();

        if ($invoices->isEmpty()) {
            return ['type' => 'text', 'content' => 'No invoices found.'];
        }

        $text = "💰 **Recent Invoices**\n\n";
        foreach ($invoices as $invoice) {
            $status = match ($invoice->status) {
                'paid' => '✅',
                'overdue' => '🔴',
                'sent' => '📤',
                default => '📝'
            };
            $text .= "{$status} {$invoice->invoice_number} - \${$invoice->total} ({$invoice->status})\n";
        }

        return ['type' => 'text', 'content' => $text];
    }

    /**
     * @param  array<string, mixed>  $intent
     * @return array<string, mixed>
     */
    private function generateContent(Agency $agency, array $intent): array
    {
        return ['type' => 'text', 'content' => "To generate AI content:\n\n/ai [prompt]\n\nExamples:\n/ai \"Write a tweet about our summer sale\"\n/ai \"Generate hashtags for fitness brand\"\n/ai \"Write email newsletter for product launch\""];
    }

    /**
     * @return array<string, mixed>
     */
    private function getQuotaStatus(Agency $agency): array
    {
        $quotas = [
            'posts' => $this->quota->remainingPosts($agency),
            'ai' => $this->quota->remainingAiGenerations($agency),
            'campaigns' => $this->quota->remainingCampaigns($agency),
            'clients' => $this->quota->remainingClients($agency),
            'accounts' => $this->quota->remainingSocialAccounts($agency),
        ];

        $text = "📊 **Plan Usage** ({$agency->subscription_plan})\n\n";
        foreach ($quotas as $key => $value) {
            $label = ucfirst(str_replace('_', ' ', $key));
            $text .= "• {$label}: ".($value === -1 ? '∞' : $value)."\n";
        }

        return ['type' => 'text', 'content' => $text];
    }

    /**
     * @return array<string, mixed>
     */
    private function getRecentActivity(Agency $agency): array
    {
        $activities = ActivityLog::where('agency_id', $agency->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        if ($activities->isEmpty()) {
            return ['type' => 'text', 'content' => 'No recent activity.'];
        }

        $text = "📋 **Recent Activity**\n\n";
        foreach ($activities as $activity) {
            $text .= "• {$activity->description}\n";
        }

        return ['type' => 'text', 'content' => $text];
    }

    /**
     * @param  array<string, mixed>  $intent
     * @return array<string, mixed>
     */
    private function performSearch(Agency $agency, array $intent): array
    {
        return ['type' => 'text', 'content' => "Search across all modules:\n\n/search [query]\n\nExample: /search \"summer sale campaign\""];
    }

    /**
     * @return array<string, mixed>
     */
    private function getHelp(): array
    {
        $text = "🤖 **AI Assistant Commands**\n\n";
        $text .= "**Dashboard & Stats:**\n";
        $text .= "/stats - View dashboard summary\n";
        $text .= "/quota - Check plan usage\n";
        $text .= "/activity - Recent activity\n\n";
        $text .= "**Posts:**\n";
        $text .= "/posts - List recent posts\n";
        $text .= "/newpost [content] - Create a post\n";
        $text .= "/schedule [content] [datetime] - Schedule post\n\n";
        $text .= "**Campaigns:**\n";
        $text .= "/campaigns - List campaigns\n";
        $text .= "/newcampaign [name] [type] - Create campaign\n\n";
        $text .= "**Clients:**\n";
        $text .= "/clients - List clients\n";
        $text .= "/addclient [name] | [email] | [company]\n\n";
        $text .= "**Invoices:**\n";
        $text .= "/invoices - List invoices\n";
        $text .= "/overdue - Show overdue invoices\n\n";
        $text .= "**AI Content:**\n";
        $text .= "/ai [prompt] - Generate content\n";
        $text .= "/hashtags [topic] - Generate hashtags\n\n";
        $text .= "**Other:**\n";
        $text .= "/search [query] - Search everything\n";
        $text .= "/help - Show this menu\n";

        return ['type' => 'text', 'content' => $text];
    }

    /**
     * @return array<string, mixed>
     */
    private function getGreeting(User $user): array
    {
        $text = "👋 Hello {$user->name}!\n\n";
        $text .= "I'm your AI Agency Assistant. I can help you manage everything right from Telegram!\n\n";
        $text .= "Type /help to see all commands, or just ask me anything naturally.\n\n";
        $text .= "Examples:\n";
        $text .= "• \"Show me my stats\"\n";
        $text .= "• \"List my published posts\"\n";
        $text .= '• "Write a tweet about our new product"';

        return ['type' => 'text', 'content' => $text];
    }

    /**
     * @return array<string, mixed>
     */
    private function getFallbackResponse(string $message): array
    {
        $text = "🤔 I'm not sure what you mean by that.\n\n";
        $text .= "Type /help to see available commands.\n";
        $text .= "Or try natural language like:\n";
        $text .= "• \"Show me my dashboard\"\n";
        $text .= "• \"List my campaigns\"\n";
        $text .= '• "Check my quota"';

        return ['type' => 'text', 'content' => $text];
    }
}
