<?php

namespace App\Services\Integrations;

use App\Models\Campaign;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\SocialPost;
use App\Models\ZapierSubscription;
use App\Services\Social\SocialPostService;
use Illuminate\Support\Facades\Log;

class ZapierIntegrationService
{
    public function __construct(private SocialPostService $socialPostService) {}

    /**
     * Returns 50+ trigger definitions for Zapier integration.
     */
    public function getTriggers(): array
    {
        return [
            // Post triggers (1-10)
            [
                'id' => 'new_post_published',
                'name' => 'New Post Published',
                'description' => 'Triggers when a social post is successfully published',
                'category' => 'posts',
                'input_fields' => [
                    ['key' => 'platform', 'type' => 'string', 'label' => 'Platform', 'required' => false, 'choices' => ['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'pinterest', 'youtube']],
                ],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Post ID'],
                    ['key' => 'content', 'type' => 'string', 'label' => 'Content'],
                    ['key' => 'platform', 'type' => 'string', 'label' => 'Platform'],
                    ['key' => 'published_at', 'type' => 'datetime', 'label' => 'Published At'],
                    ['key' => 'external_post_id', 'type' => 'string', 'label' => 'External Post ID'],
                ],
            ],
            [
                'id' => 'post_failed',
                'name' => 'Post Failed',
                'description' => 'Triggers when a post fails to publish',
                'category' => 'posts',
                'input_fields' => [
                    ['key' => 'platform', 'type' => 'string', 'label' => 'Platform', 'required' => false],
                ],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Post ID'],
                    ['key' => 'error_message', 'type' => 'string', 'label' => 'Error Message'],
                    ['key' => 'failed_at', 'type' => 'datetime', 'label' => 'Failed At'],
                ],
            ],
            [
                'id' => 'post_scheduled',
                'name' => 'Post Scheduled',
                'description' => 'Triggers when a post is scheduled for future publishing',
                'category' => 'posts',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Post ID'],
                    ['key' => 'scheduled_at', 'type' => 'datetime', 'label' => 'Scheduled At'],
                ],
            ],
            [
                'id' => 'post_draft_created',
                'name' => 'Post Draft Created',
                'description' => 'Triggers when a new post draft is created',
                'category' => 'posts',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Post ID'],
                    ['key' => 'content', 'type' => 'string', 'label' => 'Content'],
                ],
            ],
            [
                'id' => 'post_approved',
                'name' => 'Post Approved',
                'description' => 'Triggers when a post is approved',
                'category' => 'posts',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Post ID'],
                    ['key' => 'approved_at', 'type' => 'datetime', 'label' => 'Approved At'],
                ],
            ],
            [
                'id' => 'post_pinned',
                'name' => 'Post Pinned',
                'description' => 'Triggers when a post is pinned',
                'category' => 'posts',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Post ID'],
                ],
            ],
            [
                'id' => 'post_metrics_updated',
                'name' => 'Post Metrics Updated',
                'description' => 'Triggers when post engagement metrics are updated',
                'category' => 'posts',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Post ID'],
                    ['key' => 'likes_count', 'type' => 'integer', 'label' => 'Likes'],
                    ['key' => 'comments_count', 'type' => 'integer', 'label' => 'Comments'],
                    ['key' => 'shares_count', 'type' => 'integer', 'label' => 'Shares'],
                    ['key' => 'views_count', 'type' => 'integer', 'label' => 'Views'],
                ],
            ],
            [
                'id' => 'post_deleted',
                'name' => 'Post Deleted',
                'description' => 'Triggers when a post is deleted',
                'category' => 'posts',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Post ID'],
                ],
            ],
            [
                'id' => 'post_republished',
                'name' => 'Post Republished',
                'description' => 'Triggers when a post is republished',
                'category' => 'posts',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Post ID'],
                ],
            ],
            [
                'id' => 'post_quality_scored',
                'name' => 'Post Quality Scored',
                'description' => 'Triggers when a post receives a quality score',
                'category' => 'posts',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Post ID'],
                    ['key' => 'quality_score', 'type' => 'integer', 'label' => 'Quality Score'],
                ],
            ],

            // Comment triggers (11-15)
            [
                'id' => 'comment_received',
                'name' => 'Comment Received',
                'description' => 'Triggers when a new comment is received on a post',
                'category' => 'comments',
                'input_fields' => [
                    ['key' => 'platform', 'type' => 'string', 'label' => 'Platform', 'required' => false],
                ],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Comment ID'],
                    ['key' => 'content', 'type' => 'string', 'label' => 'Comment Content'],
                    ['key' => 'author', 'type' => 'string', 'label' => 'Author'],
                    ['key' => 'post_id', 'type' => 'integer', 'label' => 'Post ID'],
                ],
            ],
            [
                'id' => 'comment_replied',
                'name' => 'Comment Replied',
                'description' => 'Triggers when a comment is replied to',
                'category' => 'comments',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Reply ID'],
                    ['key' => 'comment_id', 'type' => 'integer', 'label' => 'Comment ID'],
                ],
            ],
            [
                'id' => 'comment_liked',
                'name' => 'Comment Liked',
                'description' => 'Triggers when a comment receives a like',
                'category' => 'comments',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Comment ID'],
                ],
            ],
            [
                'id' => 'comment_flagged',
                'name' => 'Comment Flagged',
                'description' => 'Triggers when a comment is flagged for review',
                'category' => 'comments',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Comment ID'],
                ],
            ],
            [
                'id' => 'comment_deleted',
                'name' => 'Comment Deleted',
                'description' => 'Triggers when a comment is deleted',
                'category' => 'comments',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Comment ID'],
                ],
            ],

            // Campaign triggers (16-25)
            [
                'id' => 'campaign_status_changed',
                'name' => 'Campaign Status Changed',
                'description' => 'Triggers when a campaign status changes',
                'category' => 'campaigns',
                'input_fields' => [
                    ['key' => 'status', 'type' => 'string', 'label' => 'Status', 'required' => false, 'choices' => ['draft', 'active', 'paused', 'completed', 'cancelled']],
                ],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Campaign ID'],
                    ['key' => 'name', 'type' => 'string', 'label' => 'Campaign Name'],
                    ['key' => 'status', 'type' => 'string', 'label' => 'Status'],
                ],
            ],
            [
                'id' => 'campaign_created',
                'name' => 'Campaign Created',
                'description' => 'Triggers when a new campaign is created',
                'category' => 'campaigns',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Campaign ID'],
                    ['key' => 'name', 'type' => 'string', 'label' => 'Campaign Name'],
                ],
            ],
            [
                'id' => 'campaign_activated',
                'name' => 'Campaign Activated',
                'description' => 'Triggers when a campaign is activated',
                'category' => 'campaigns',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Campaign ID'],
                ],
            ],
            [
                'id' => 'campaign_paused',
                'name' => 'Campaign Paused',
                'description' => 'Triggers when a campaign is paused',
                'category' => 'campaigns',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Campaign ID'],
                ],
            ],
            [
                'id' => 'campaign_completed',
                'name' => 'Campaign Completed',
                'description' => 'Triggers when a campaign is completed',
                'category' => 'campaigns',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Campaign ID'],
                    ['key' => 'end_date', 'type' => 'date', 'label' => 'End Date'],
                ],
            ],
            [
                'id' => 'campaign_cancelled',
                'name' => 'Campaign Cancelled',
                'description' => 'Triggers when a campaign is cancelled',
                'category' => 'campaigns',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Campaign ID'],
                ],
            ],
            [
                'id' => 'campaign_post_added',
                'name' => 'Campaign Post Added',
                'description' => 'Triggers when a post is added to a campaign',
                'category' => 'campaigns',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'campaign_id', 'type' => 'integer', 'label' => 'Campaign ID'],
                    ['key' => 'post_id', 'type' => 'integer', 'label' => 'Post ID'],
                ],
            ],
            [
                'id' => 'campaign_reached_milestone',
                'name' => 'Campaign Reached Milestone',
                'description' => 'Triggers when a campaign reaches a milestone',
                'category' => 'campaigns',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Campaign ID'],
                    ['key' => 'milestone', 'type' => 'string', 'label' => 'Milestone'],
                ],
            ],
            [
                'id' => 'campaign_budget_exceeded',
                'name' => 'Campaign Budget Exceeded',
                'description' => 'Triggers when a campaign exceeds its budget',
                'category' => 'campaigns',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Campaign ID'],
                ],
            ],
            [
                'id' => 'campaign_end_date_reached',
                'name' => 'Campaign End Date Reached',
                'description' => 'Triggers when a campaign reaches its end date',
                'category' => 'campaigns',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Campaign ID'],
                ],
            ],

            // Invoice triggers (26-32)
            [
                'id' => 'invoice_paid',
                'name' => 'Invoice Paid',
                'description' => 'Triggers when an invoice is paid',
                'category' => 'invoices',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Invoice ID'],
                    ['key' => 'invoice_number', 'type' => 'string', 'label' => 'Invoice Number'],
                    ['key' => 'total', 'type' => 'decimal', 'label' => 'Total Amount'],
                    ['key' => 'paid_date', 'type' => 'date', 'label' => 'Paid Date'],
                ],
            ],
            [
                'id' => 'invoice_created',
                'name' => 'Invoice Created',
                'description' => 'Triggers when a new invoice is created',
                'category' => 'invoices',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Invoice ID'],
                    ['key' => 'invoice_number', 'type' => 'string', 'label' => 'Invoice Number'],
                ],
            ],
            [
                'id' => 'invoice_overdue',
                'name' => 'Invoice Overdue',
                'description' => 'Triggers when an invoice becomes overdue',
                'category' => 'invoices',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Invoice ID'],
                    ['key' => 'due_date', 'type' => 'date', 'label' => 'Due Date'],
                ],
            ],
            [
                'id' => 'invoice_sent',
                'name' => 'Invoice Sent',
                'description' => 'Triggers when an invoice is sent to a client',
                'category' => 'invoices',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Invoice ID'],
                ],
            ],
            [
                'id' => 'invoice_cancelled',
                'name' => 'Invoice Cancelled',
                'description' => 'Triggers when an invoice is cancelled',
                'category' => 'invoices',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Invoice ID'],
                ],
            ],
            [
                'id' => 'invoice_refunded',
                'name' => 'Invoice Refunded',
                'description' => 'Triggers when an invoice is refunded',
                'category' => 'invoices',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Invoice ID'],
                ],
            ],
            [
                'id' => 'invoice_payment_failed',
                'name' => 'Invoice Payment Failed',
                'description' => 'Triggers when an invoice payment fails',
                'category' => 'invoices',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Invoice ID'],
                ],
            ],

            // Client triggers (33-40)
            [
                'id' => 'client_created',
                'name' => 'Client Created',
                'description' => 'Triggers when a new client is created',
                'category' => 'clients',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Client ID'],
                    ['key' => 'name', 'type' => 'string', 'label' => 'Client Name'],
                    ['key' => 'email', 'type' => 'string', 'label' => 'Email'],
                ],
            ],
            [
                'id' => 'client_updated',
                'name' => 'Client Updated',
                'description' => 'Triggers when a client record is updated',
                'category' => 'clients',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Client ID'],
                ],
            ],
            [
                'id' => 'client_status_changed',
                'name' => 'Client Status Changed',
                'description' => 'Triggers when a client status changes',
                'category' => 'clients',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Client ID'],
                    ['key' => 'status', 'type' => 'string', 'label' => 'Status'],
                ],
            ],
            [
                'id' => 'client_deleted',
                'name' => 'Client Deleted',
                'description' => 'Triggers when a client is deleted',
                'category' => 'clients',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Client ID'],
                ],
            ],
            [
                'id' => 'client_contacted',
                'name' => 'Client Contacted',
                'description' => 'Triggers when a client is contacted',
                'category' => 'clients',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Client ID'],
                ],
            ],
            [
                'id' => 'client_onboarded',
                'name' => 'Client Onboarded',
                'description' => 'Triggers when a client is onboarded',
                'category' => 'clients',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Client ID'],
                ],
            ],
            [
                'id' => 'client_offboarded',
                'name' => 'Client Offboarded',
                'description' => 'Triggers when a client is offboarded',
                'category' => 'clients',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Client ID'],
                ],
            ],
            [
                'id' => 'client_contract_renewed',
                'name' => 'Client Contract Renewed',
                'description' => 'Triggers when a client contract is renewed',
                'category' => 'clients',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Client ID'],
                ],
            ],

            // Report triggers (41-45)
            [
                'id' => 'report_generated',
                'name' => 'Report Generated',
                'description' => 'Triggers when a report is generated',
                'category' => 'reports',
                'input_fields' => [
                    ['key' => 'type', 'type' => 'string', 'label' => 'Report Type', 'required' => false],
                ],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Report ID'],
                    ['key' => 'name', 'type' => 'string', 'label' => 'Report Name'],
                    ['key' => 'type', 'type' => 'string', 'label' => 'Report Type'],
                    ['key' => 'file_path', 'type' => 'string', 'label' => 'File Path'],
                ],
            ],
            [
                'id' => 'report_scheduled',
                'name' => 'Report Scheduled',
                'description' => 'Triggers when a report is scheduled',
                'category' => 'reports',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Report ID'],
                ],
            ],
            [
                'id' => 'report_exported',
                'name' => 'Report Exported',
                'description' => 'Triggers when a report is exported',
                'category' => 'reports',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Report ID'],
                ],
            ],
            [
                'id' => 'report_shared',
                'name' => 'Report Shared',
                'description' => 'Triggers when a report is shared',
                'category' => 'reports',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Report ID'],
                ],
            ],
            [
                'id' => 'report_failed',
                'name' => 'Report Failed',
                'description' => 'Triggers when a report generation fails',
                'category' => 'reports',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Report ID'],
                ],
            ],

            // Social Listening triggers (46-50)
            [
                'id' => 'mention_received',
                'name' => 'Mention Received',
                'description' => 'Triggers when a brand mention is detected',
                'category' => 'listening',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Mention ID'],
                    ['key' => 'content', 'type' => 'string', 'label' => 'Mention Content'],
                    ['key' => 'platform', 'type' => 'string', 'label' => 'Platform'],
                ],
            ],
            [
                'id' => 'sentiment_changed',
                'name' => 'Sentiment Changed',
                'description' => 'Triggers when sentiment analysis changes',
                'category' => 'listening',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'ID'],
                    ['key' => 'sentiment', 'type' => 'string', 'label' => 'Sentiment'],
                ],
            ],
            [
                'id' => 'trend_detected',
                'name' => 'Trend Detected',
                'description' => 'Triggers when a new trend is detected',
                'category' => 'listening',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Trend ID'],
                    ['key' => 'name', 'type' => 'string', 'label' => 'Trend Name'],
                ],
            ],
            [
                'id' => 'competitor_mentioned',
                'name' => 'Competitor Mentioned',
                'description' => 'Triggers when a competitor is mentioned',
                'category' => 'listening',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'ID'],
                    ['key' => 'competitor', 'type' => 'string', 'label' => 'Competitor'],
                ],
            ],
            [
                'id' => 'hashtag_trending',
                'name' => 'Hashtag Trending',
                'description' => 'Triggers when a hashtag starts trending',
                'category' => 'listening',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'ID'],
                    ['key' => 'hashtag', 'type' => 'string', 'label' => 'Hashtag'],
                ],
            ],

            // Additional triggers (51-55)
            [
                'id' => 'subscription_upgraded',
                'name' => 'Subscription Upgraded',
                'description' => 'Triggers when a subscription is upgraded',
                'category' => 'billing',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'agency_id', 'type' => 'integer', 'label' => 'Agency ID'],
                    ['key' => 'plan', 'type' => 'string', 'label' => 'Plan'],
                ],
            ],
            [
                'id' => 'subscription_cancelled',
                'name' => 'Subscription Cancelled',
                'description' => 'Triggers when a subscription is cancelled',
                'category' => 'billing',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'agency_id', 'type' => 'integer', 'label' => 'Agency ID'],
                ],
            ],
            [
                'id' => 'inbox_message_received',
                'name' => 'Inbox Message Received',
                'description' => 'Triggers when a new inbox message is received',
                'category' => 'inbox',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Message ID'],
                    ['key' => 'content', 'type' => 'string', 'label' => 'Content'],
                ],
            ],
            [
                'id' => 'social_account_connected',
                'name' => 'Social Account Connected',
                'description' => 'Triggers when a social account is connected',
                'category' => 'accounts',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Account ID'],
                    ['key' => 'platform', 'type' => 'string', 'label' => 'Platform'],
                ],
            ],
            [
                'id' => 'social_account_disconnected',
                'name' => 'Social Account Disconnected',
                'description' => 'Triggers when a social account is disconnected',
                'category' => 'accounts',
                'input_fields' => [],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Account ID'],
                ],
            ],
        ];
    }

    /**
     * Returns 30+ action definitions for Zapier integration.
     */
    public function getActions(): array
    {
        return [
            // Post actions (1-8)
            [
                'id' => 'create_post',
                'name' => 'Create Post',
                'description' => 'Creates a new social media post',
                'category' => 'posts',
                'input_fields' => [
                    ['key' => 'platform', 'type' => 'string', 'label' => 'Platform', 'required' => true, 'choices' => ['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'pinterest', 'youtube']],
                    ['key' => 'content', 'type' => 'text', 'label' => 'Content', 'required' => true],
                    ['key' => 'social_account_id', 'type' => 'integer', 'label' => 'Social Account ID', 'required' => true],
                    ['key' => 'media', 'type' => 'text', 'label' => 'Media URLs', 'required' => false],
                    ['key' => 'hashtags', 'type' => 'text', 'label' => 'Hashtags', 'required' => false],
                    ['key' => 'scheduled_at', 'type' => 'datetime', 'label' => 'Schedule At', 'required' => false],
                ],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Post ID'],
                    ['key' => 'status', 'type' => 'string', 'label' => 'Status'],
                ],
            ],
            [
                'id' => 'publish_post',
                'name' => 'Publish Post',
                'description' => 'Publishes an existing post immediately',
                'category' => 'posts',
                'input_fields' => [
                    ['key' => 'post_id', 'type' => 'integer', 'label' => 'Post ID', 'required' => true],
                ],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Post ID'],
                    ['key' => 'status', 'type' => 'string', 'label' => 'Status'],
                ],
            ],
            [
                'id' => 'schedule_post',
                'name' => 'Schedule Post',
                'description' => 'Schedules a post for future publishing',
                'category' => 'posts',
                'input_fields' => [
                    ['key' => 'platform', 'type' => 'string', 'label' => 'Platform', 'required' => true],
                    ['key' => 'content', 'type' => 'text', 'label' => 'Content', 'required' => true],
                    ['key' => 'social_account_id', 'type' => 'integer', 'label' => 'Social Account ID', 'required' => true],
                    ['key' => 'scheduled_at', 'type' => 'datetime', 'label' => 'Schedule At', 'required' => true],
                ],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Post ID'],
                ],
            ],
            [
                'id' => 'delete_post',
                'name' => 'Delete Post',
                'description' => 'Deletes an existing post',
                'category' => 'posts',
                'input_fields' => [
                    ['key' => 'post_id', 'type' => 'integer', 'label' => 'Post ID', 'required' => true],
                ],
                'output_fields' => [
                    ['key' => 'success', 'type' => 'boolean', 'label' => 'Success'],
                ],
            ],
            [
                'id' => 'update_post',
                'name' => 'Update Post',
                'description' => 'Updates an existing post',
                'category' => 'posts',
                'input_fields' => [
                    ['key' => 'post_id', 'type' => 'integer', 'label' => 'Post ID', 'required' => true],
                    ['key' => 'content', 'type' => 'text', 'label' => 'Content', 'required' => false],
                ],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Post ID'],
                ],
            ],
            [
                'id' => 'approve_post',
                'name' => 'Approve Post',
                'description' => 'Approves a pending post',
                'category' => 'posts',
                'input_fields' => [
                    ['key' => 'post_id', 'type' => 'integer', 'label' => 'Post ID', 'required' => true],
                ],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Post ID'],
                ],
            ],
            [
                'id' => 'pin_post',
                'name' => 'Pin Post',
                'description' => 'Pins a post to the top',
                'category' => 'posts',
                'input_fields' => [
                    ['key' => 'post_id', 'type' => 'integer', 'label' => 'Post ID', 'required' => true],
                ],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Post ID'],
                ],
            ],
            [
                'id' => 'get_post_metrics',
                'name' => 'Get Post Metrics',
                'description' => 'Retrieves metrics for a post',
                'category' => 'posts',
                'input_fields' => [
                    ['key' => 'post_id', 'type' => 'integer', 'label' => 'Post ID', 'required' => true],
                ],
                'output_fields' => [
                    ['key' => 'likes_count', 'type' => 'integer', 'label' => 'Likes'],
                    ['key' => 'comments_count', 'type' => 'integer', 'label' => 'Comments'],
                    ['key' => 'shares_count', 'type' => 'integer', 'label' => 'Shares'],
                ],
            ],

            // Campaign actions (9-15)
            [
                'id' => 'send_campaign',
                'name' => 'Send Campaign',
                'description' => 'Creates and activates a new campaign',
                'category' => 'campaigns',
                'input_fields' => [
                    ['key' => 'name', 'type' => 'string', 'label' => 'Campaign Name', 'required' => true],
                    ['key' => 'type', 'type' => 'string', 'label' => 'Campaign Type', 'required' => true, 'choices' => ['general', 'product_launch', 'seasonal', 'awareness', 'consideration', 'conversion', 'retention']],
                    ['key' => 'description', 'type' => 'text', 'label' => 'Description', 'required' => false],
                    ['key' => 'start_date', 'type' => 'date', 'label' => 'Start Date', 'required' => false],
                    ['key' => 'end_date', 'type' => 'date', 'label' => 'End Date', 'required' => false],
                    ['key' => 'client_id', 'type' => 'integer', 'label' => 'Client ID', 'required' => false],
                ],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Campaign ID'],
                    ['key' => 'name', 'type' => 'string', 'label' => 'Campaign Name'],
                    ['key' => 'status', 'type' => 'string', 'label' => 'Status'],
                ],
            ],
            [
                'id' => 'update_campaign',
                'name' => 'Update Campaign',
                'description' => 'Updates an existing campaign',
                'category' => 'campaigns',
                'input_fields' => [
                    ['key' => 'campaign_id', 'type' => 'integer', 'label' => 'Campaign ID', 'required' => true],
                    ['key' => 'name', 'type' => 'string', 'label' => 'Name', 'required' => false],
                    ['key' => 'description', 'type' => 'text', 'label' => 'Description', 'required' => false],
                ],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Campaign ID'],
                ],
            ],
            [
                'id' => 'pause_campaign',
                'name' => 'Pause Campaign',
                'description' => 'Pauses an active campaign',
                'category' => 'campaigns',
                'input_fields' => [
                    ['key' => 'campaign_id', 'type' => 'integer', 'label' => 'Campaign ID', 'required' => true],
                ],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Campaign ID'],
                ],
            ],
            [
                'id' => 'activate_campaign',
                'name' => 'Activate Campaign',
                'description' => 'Activates a campaign',
                'category' => 'campaigns',
                'input_fields' => [
                    ['key' => 'campaign_id', 'type' => 'integer', 'label' => 'Campaign ID', 'required' => true],
                ],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Campaign ID'],
                ],
            ],
            [
                'id' => 'complete_campaign',
                'name' => 'Complete Campaign',
                'description' => 'Marks a campaign as completed',
                'category' => 'campaigns',
                'input_fields' => [
                    ['key' => 'campaign_id', 'type' => 'integer', 'label' => 'Campaign ID', 'required' => true],
                ],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Campaign ID'],
                ],
            ],
            [
                'id' => 'cancel_campaign',
                'name' => 'Cancel Campaign',
                'description' => 'Cancels a campaign',
                'category' => 'campaigns',
                'input_fields' => [
                    ['key' => 'campaign_id', 'type' => 'integer', 'label' => 'Campaign ID', 'required' => true],
                ],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Campaign ID'],
                ],
            ],
            [
                'id' => 'add_post_to_campaign',
                'name' => 'Add Post to Campaign',
                'description' => 'Adds a post to a campaign',
                'category' => 'campaigns',
                'input_fields' => [
                    ['key' => 'campaign_id', 'type' => 'integer', 'label' => 'Campaign ID', 'required' => true],
                    ['key' => 'post_id', 'type' => 'integer', 'label' => 'Post ID', 'required' => true],
                ],
                'output_fields' => [
                    ['key' => 'success', 'type' => 'boolean', 'label' => 'Success'],
                ],
            ],

            // Client actions (16-21)
            [
                'id' => 'update_contact',
                'name' => 'Update Contact',
                'description' => 'Updates an existing client contact',
                'category' => 'clients',
                'input_fields' => [
                    ['key' => 'client_id', 'type' => 'integer', 'label' => 'Client ID', 'required' => true],
                    ['key' => 'name', 'type' => 'string', 'label' => 'Name', 'required' => false],
                    ['key' => 'email', 'type' => 'string', 'label' => 'Email', 'required' => false],
                    ['key' => 'phone', 'type' => 'string', 'label' => 'Phone', 'required' => false],
                    ['key' => 'company', 'type' => 'string', 'label' => 'Company', 'required' => false],
                ],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Client ID'],
                ],
            ],
            [
                'id' => 'create_client',
                'name' => 'Create Client',
                'description' => 'Creates a new client',
                'category' => 'clients',
                'input_fields' => [
                    ['key' => 'name', 'type' => 'string', 'label' => 'Name', 'required' => true],
                    ['key' => 'email', 'type' => 'string', 'label' => 'Email', 'required' => true],
                    ['key' => 'phone', 'type' => 'string', 'label' => 'Phone', 'required' => false],
                    ['key' => 'company', 'type' => 'string', 'label' => 'Company', 'required' => false],
                    ['key' => 'industry', 'type' => 'string', 'label' => 'Industry', 'required' => false],
                ],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Client ID'],
                ],
            ],
            [
                'id' => 'delete_client',
                'name' => 'Delete Client',
                'description' => 'Deletes a client',
                'category' => 'clients',
                'input_fields' => [
                    ['key' => 'client_id', 'type' => 'integer', 'label' => 'Client ID', 'required' => true],
                ],
                'output_fields' => [
                    ['key' => 'success', 'type' => 'boolean', 'label' => 'Success'],
                ],
            ],
            [
                'id' => 'get_client',
                'name' => 'Get Client',
                'description' => 'Retrieves client details',
                'category' => 'clients',
                'input_fields' => [
                    ['key' => 'client_id', 'type' => 'integer', 'label' => 'Client ID', 'required' => true],
                ],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Client ID'],
                    ['key' => 'name', 'type' => 'string', 'label' => 'Name'],
                    ['key' => 'email', 'type' => 'string', 'label' => 'Email'],
                ],
            ],
            [
                'id' => 'search_clients',
                'name' => 'Search Clients',
                'description' => 'Searches for clients',
                'category' => 'clients',
                'input_fields' => [
                    ['key' => 'query', 'type' => 'string', 'label' => 'Search Query', 'required' => true],
                ],
                'output_fields' => [
                    ['key' => 'clients', 'type' => 'array', 'label' => 'Clients'],
                ],
            ],
            [
                'id' => 'change_client_status',
                'name' => 'Change Client Status',
                'description' => 'Changes the status of a client',
                'category' => 'clients',
                'input_fields' => [
                    ['key' => 'client_id', 'type' => 'integer', 'label' => 'Client ID', 'required' => true],
                    ['key' => 'status', 'type' => 'string', 'label' => 'Status', 'required' => true, 'choices' => ['active', 'lead', 'inactive']],
                ],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Client ID'],
                ],
            ],

            // Invoice actions (22-27)
            [
                'id' => 'create_invoice',
                'name' => 'Create Invoice',
                'description' => 'Creates a new invoice',
                'category' => 'invoices',
                'input_fields' => [
                    ['key' => 'client_id', 'type' => 'integer', 'label' => 'Client ID', 'required' => true],
                    ['key' => 'total', 'type' => 'decimal', 'label' => 'Total Amount', 'required' => true],
                    ['key' => 'currency', 'type' => 'string', 'label' => 'Currency', 'required' => false],
                    ['key' => 'due_date', 'type' => 'date', 'label' => 'Due Date', 'required' => false],
                    ['key' => 'notes', 'type' => 'text', 'label' => 'Notes', 'required' => false],
                ],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Invoice ID'],
                    ['key' => 'invoice_number', 'type' => 'string', 'label' => 'Invoice Number'],
                ],
            ],
            [
                'id' => 'send_invoice',
                'name' => 'Send Invoice',
                'description' => 'Sends an invoice to a client',
                'category' => 'invoices',
                'input_fields' => [
                    ['key' => 'invoice_id', 'type' => 'integer', 'label' => 'Invoice ID', 'required' => true],
                ],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Invoice ID'],
                ],
            ],
            [
                'id' => 'mark_invoice_paid',
                'name' => 'Mark Invoice Paid',
                'description' => 'Marks an invoice as paid',
                'category' => 'invoices',
                'input_fields' => [
                    ['key' => 'invoice_id', 'type' => 'integer', 'label' => 'Invoice ID', 'required' => true],
                    ['key' => 'payment_method', 'type' => 'string', 'label' => 'Payment Method', 'required' => false],
                ],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Invoice ID'],
                ],
            ],
            [
                'id' => 'cancel_invoice',
                'name' => 'Cancel Invoice',
                'description' => 'Cancels an invoice',
                'category' => 'invoices',
                'input_fields' => [
                    ['key' => 'invoice_id', 'type' => 'integer', 'label' => 'Invoice ID', 'required' => true],
                ],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Invoice ID'],
                ],
            ],
            [
                'id' => 'get_invoice',
                'name' => 'Get Invoice',
                'description' => 'Retrieves invoice details',
                'category' => 'invoices',
                'input_fields' => [
                    ['key' => 'invoice_id', 'type' => 'integer', 'label' => 'Invoice ID', 'required' => true],
                ],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Invoice ID'],
                    ['key' => 'total', 'type' => 'decimal', 'label' => 'Total'],
                    ['key' => 'status', 'type' => 'string', 'label' => 'Status'],
                ],
            ],
            [
                'id' => 'refund_invoice',
                'name' => 'Refund Invoice',
                'description' => 'Refunds an invoice',
                'category' => 'invoices',
                'input_fields' => [
                    ['key' => 'invoice_id', 'type' => 'integer', 'label' => 'Invoice ID', 'required' => true],
                ],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Invoice ID'],
                ],
            ],

            // Report actions (28-32)
            [
                'id' => 'generate_report',
                'name' => 'Generate Report',
                'description' => 'Generates a new report',
                'category' => 'reports',
                'input_fields' => [
                    ['key' => 'name', 'type' => 'string', 'label' => 'Report Name', 'required' => true],
                    ['key' => 'type', 'type' => 'string', 'label' => 'Report Type', 'required' => true, 'choices' => ['analytics', 'engagement', 'campaign', 'financial']],
                    ['key' => 'format', 'type' => 'string', 'label' => 'Format', 'required' => false, 'choices' => ['pdf', 'csv', 'xlsx']],
                ],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Report ID'],
                ],
            ],
            [
                'id' => 'schedule_report',
                'name' => 'Schedule Report',
                'description' => 'Schedules a report for periodic generation',
                'category' => 'reports',
                'input_fields' => [
                    ['key' => 'name', 'type' => 'string', 'label' => 'Report Name', 'required' => true],
                    ['key' => 'type', 'type' => 'string', 'label' => 'Report Type', 'required' => true],
                    ['key' => 'schedule', 'type' => 'string', 'label' => 'Schedule', 'required' => true, 'choices' => ['daily', 'weekly', 'monthly']],
                ],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Report ID'],
                ],
            ],
            [
                'id' => 'export_report',
                'name' => 'Export Report',
                'description' => 'Exports a report',
                'category' => 'reports',
                'input_fields' => [
                    ['key' => 'report_id', 'type' => 'integer', 'label' => 'Report ID', 'required' => true],
                    ['key' => 'format', 'type' => 'string', 'label' => 'Format', 'required' => true, 'choices' => ['pdf', 'csv', 'xlsx']],
                ],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Report ID'],
                ],
            ],
            [
                'id' => 'share_report',
                'name' => 'Share Report',
                'description' => 'Shares a report with a client',
                'category' => 'reports',
                'input_fields' => [
                    ['key' => 'report_id', 'type' => 'integer', 'label' => 'Report ID', 'required' => true],
                    ['key' => 'client_id', 'type' => 'integer', 'label' => 'Client ID', 'required' => true],
                ],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Report ID'],
                ],
            ],
            [
                'id' => 'get_report',
                'name' => 'Get Report',
                'description' => 'Retrieves report details',
                'category' => 'reports',
                'input_fields' => [
                    ['key' => 'report_id', 'type' => 'integer', 'label' => 'Report ID', 'required' => true],
                ],
                'output_fields' => [
                    ['key' => 'id', 'type' => 'integer', 'label' => 'Report ID'],
                    ['key' => 'name', 'type' => 'string', 'label' => 'Name'],
                ],
            ],
        ];
    }

    /**
     * Executes the requested action.
     */
    public function executeAction(string $action, array $data): array
    {
        $agencyId = $data['agency_id'] ?? null;
        if (!$agencyId) {
            return ['success' => false, 'message' => 'Agency ID is required'];
        }

        unset($data['agency_id']);

        return match ($action) {
            'create_post' => $this->executeCreatePost($agencyId, $data),
            'publish_post' => $this->executePublishPost($data),
            'schedule_post' => $this->executeSchedulePost($agencyId, $data),
            'delete_post' => $this->executeDeletePost($data),
            'update_post' => $this->executeUpdatePost($data),
            'approve_post' => $this->executeApprovePost($data),
            'pin_post' => $this->executePinPost($data),
            'get_post_metrics' => $this->executeGetPostMetrics($data),
            'send_campaign' => $this->executeSendCampaign($agencyId, $data),
            'update_campaign' => $this->executeUpdateCampaign($data),
            'pause_campaign' => $this->executePauseCampaign($data),
            'activate_campaign' => $this->executeActivateCampaign($data),
            'complete_campaign' => $this->executeCompleteCampaign($data),
            'cancel_campaign' => $this->executeCancelCampaign($data),
            'add_post_to_campaign' => $this->executeAddPostToCampaign($data),
            'create_client' => $this->executeCreateClient($agencyId, $data),
            'update_contact' => $this->executeUpdateContact($data),
            'delete_client' => $this->executeDeleteClient($data),
            'get_client' => $this->executeGetClient($data),
            'search_clients' => $this->executeSearchClients($agencyId, $data),
            'change_client_status' => $this->executeChangeClientStatus($data),
            'create_invoice' => $this->executeCreateInvoice($agencyId, $data),
            'send_invoice' => $this->executeSendInvoice($data),
            'mark_invoice_paid' => $this->executeMarkInvoicePaid($data),
            'cancel_invoice' => $this->executeCancelInvoice($data),
            'get_invoice' => $this->executeGetInvoice($data),
            'refund_invoice' => $this->executeRefundInvoice($data),
            'generate_report' => $this->executeGenerateReport($agencyId, $data),
            'schedule_report' => $this->executeScheduleReport($agencyId, $data),
            'export_report' => $this->executeExportReport($data),
            'share_report' => $this->executeShareReport($data),
            'get_report' => $this->executeGetReport($data),
            default => ['success' => false, 'message' => "Unknown action: {$action}"],
        };
    }

    /**
     * Fetches recent data for a trigger.
     */
    public function getTriggerData(string $trigger, int $agencyId): array
    {
        return match ($trigger) {
            'new_post_published' => $this->getRecentPublishedPosts($agencyId),
            'post_failed' => $this->getRecentFailedPosts($agencyId),
            'post_scheduled' => $this->getRecentScheduledPosts($agencyId),
            'post_draft_created' => $this->getRecentDraftPosts($agencyId),
            'comment_received' => $this->getRecentComments($agencyId),
            'campaign_status_changed', 'campaign_created' => $this->getRecentCampaigns($agencyId),
            'invoice_paid' => $this->getRecentPaidInvoices($agencyId),
            'invoice_created' => $this->getRecentInvoices($agencyId),
            'client_created' => $this->getRecentClients($agencyId),
            'report_generated' => $this->getRecentReports($agencyId),
            'inbox_message_received' => $this->getRecentInboxMessages($agencyId),
            'mention_received' => $this->getRecentMentions($agencyId),
            'social_account_connected' => $this->getRecentSocialAccounts($agencyId),
            default => $this->getRecentPublishedPosts($agencyId),
        };
    }

    // Action execution methods

    private function executeCreatePost(int $agencyId, array $data): array
    {
        try {
            $post = $this->socialPostService->createPost($agencyId, $data);
            return ['success' => true, 'data' => ['id' => $post->id, 'status' => $post->status]];
        } catch (\Exception $e) {
            Log::error('Zapier create_post failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function executePublishPost(array $data): array
    {
        try {
            $post = SocialPost::findOrFail($data['post_id']);
            $result = $this->socialPostService->publishPost($post);
            return $result;
        } catch (\Exception $e) {
            Log::error('Zapier publish_post failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function executeSchedulePost(int $agencyId, array $data): array
    {
        try {
            $post = $this->socialPostService->schedulePost($agencyId, $data);
            return ['success' => true, 'data' => ['id' => $post->id]];
        } catch (\Exception $e) {
            Log::error('Zapier schedule_post failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function executeDeletePost(array $data): array
    {
        try {
            $post = SocialPost::findOrFail($data['post_id']);
            $post->delete();
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function executeUpdatePost(array $data): array
    {
        try {
            $post = SocialPost::findOrFail($data['post_id']);
            $updateData = array_diff_key($data, array_flip(['post_id']));
            $post->update($updateData);
            return ['success' => true, 'data' => ['id' => $post->id]];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function executeApprovePost(array $data): array
    {
        try {
            $post = SocialPost::findOrFail($data['post_id']);
            $post->update(['approval_status' => 'approved', 'approved_at' => now()]);
            return ['success' => true, 'data' => ['id' => $post->id]];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function executePinPost(array $data): array
    {
        try {
            $post = SocialPost::findOrFail($data['post_id']);
            $post->update(['is_pinned' => true]);
            return ['success' => true, 'data' => ['id' => $post->id]];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function executeGetPostMetrics(array $data): array
    {
        try {
            $post = SocialPost::findOrFail($data['post_id']);
            return [
                'success' => true,
                'data' => [
                    'likes_count' => $post->likes_count,
                    'comments_count' => $post->comments_count,
                    'shares_count' => $post->shares_count,
                    'views_count' => $post->views_count,
                    'engagement_rate' => $post->engagement_rate,
                ],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function executeSendCampaign(int $agencyId, array $data): array
    {
        try {
            $campaign = Campaign::create(array_merge($data, [
                'agency_id' => $agencyId,
                'status' => 'active',
                'slug' => \Illuminate\Support\Str::slug($data['name'] ?? 'campaign-' . time()),
            ]));
            return ['success' => true, 'data' => ['id' => $campaign->id, 'name' => $campaign->name, 'status' => $campaign->status]];
        } catch (\Exception $e) {
            Log::error('Zapier send_campaign failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function executeUpdateCampaign(array $data): array
    {
        try {
            $campaign = Campaign::findOrFail($data['campaign_id']);
            $updateData = array_diff_key($data, array_flip(['campaign_id']));
            $campaign->update($updateData);
            return ['success' => true, 'data' => ['id' => $campaign->id]];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function executePauseCampaign(array $data): array
    {
        try {
            $campaign = Campaign::findOrFail($data['campaign_id']);
            $campaign->update(['status' => 'paused']);
            return ['success' => true, 'data' => ['id' => $campaign->id]];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function executeActivateCampaign(array $data): array
    {
        try {
            $campaign = Campaign::findOrFail($data['campaign_id']);
            $campaign->update(['status' => 'active']);
            return ['success' => true, 'data' => ['id' => $campaign->id]];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function executeCompleteCampaign(array $data): array
    {
        try {
            $campaign = Campaign::findOrFail($data['campaign_id']);
            $campaign->update(['status' => 'completed']);
            return ['success' => true, 'data' => ['id' => $campaign->id]];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function executeCancelCampaign(array $data): array
    {
        try {
            $campaign = Campaign::findOrFail($data['campaign_id']);
            $campaign->update(['status' => 'cancelled']);
            return ['success' => true, 'data' => ['id' => $campaign->id]];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function executeAddPostToCampaign(array $data): array
    {
        try {
            $campaign = Campaign::findOrFail($data['campaign_id']);
            $campaign->posts()->attach($data['post_id']);
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function executeCreateClient(int $agencyId, array $data): array
    {
        try {
            $client = Client::create(array_merge($data, ['agency_id' => $agencyId]));
            return ['success' => true, 'data' => ['id' => $client->id]];
        } catch (\Exception $e) {
            Log::error('Zapier create_client failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function executeUpdateContact(array $data): array
    {
        try {
            $client = Client::findOrFail($data['client_id']);
            $updateData = array_diff_key($data, array_flip(['client_id']));
            $client->update($updateData);
            return ['success' => true, 'data' => ['id' => $client->id]];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function executeDeleteClient(array $data): array
    {
        try {
            $client = Client::findOrFail($data['client_id']);
            $client->delete();
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function executeGetClient(array $data): array
    {
        try {
            $client = Client::findOrFail($data['client_id']);
            return ['success' => true, 'data' => ['id' => $client->id, 'name' => $client->name, 'email' => $client->email]];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function executeSearchClients(int $agencyId, array $data): array
    {
        try {
            $clients = Client::where('agency_id', $agencyId)
                ->where(function ($q) use ($data) {
                    $q->where('name', 'like', "%{$data['query']}%")
                        ->orWhere('email', 'like', "%{$data['query']}%");
                })
                ->limit(10)
                ->get();
            return ['success' => true, 'data' => ['clients' => $clients]];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function executeChangeClientStatus(array $data): array
    {
        try {
            $client = Client::findOrFail($data['client_id']);
            $client->update(['status' => $data['status']]);
            return ['success' => true, 'data' => ['id' => $client->id]];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function executeCreateInvoice(int $agencyId, array $data): array
    {
        try {
            $invoice = Invoice::create(array_merge($data, [
                'agency_id' => $agencyId,
                'invoice_number' => Invoice::generateNumber(),
                'status' => 'pending',
                'issue_date' => now(),
            ]));
            return ['success' => true, 'data' => ['id' => $invoice->id, 'invoice_number' => $invoice->invoice_number]];
        } catch (\Exception $e) {
            Log::error('Zapier create_invoice failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function executeSendInvoice(array $data): array
    {
        try {
            $invoice = Invoice::findOrFail($data['invoice_id']);
            $invoice->update(['status' => 'sent']);
            return ['success' => true, 'data' => ['id' => $invoice->id]];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function executeMarkInvoicePaid(array $data): array
    {
        try {
            $invoice = Invoice::findOrFail($data['invoice_id']);
            $invoice->markPaid($data['payment_method'] ?? 'manual', 'zapier-' . time());
            return ['success' => true, 'data' => ['id' => $invoice->id]];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function executeCancelInvoice(array $data): array
    {
        try {
            $invoice = Invoice::findOrFail($data['invoice_id']);
            $invoice->update(['status' => 'cancelled']);
            return ['success' => true, 'data' => ['id' => $invoice->id]];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function executeGetInvoice(array $data): array
    {
        try {
            $invoice = Invoice::findOrFail($data['invoice_id']);
            return ['success' => true, 'data' => ['id' => $invoice->id, 'total' => $invoice->total, 'status' => $invoice->status]];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function executeRefundInvoice(array $data): array
    {
        try {
            $invoice = Invoice::findOrFail($data['invoice_id']);
            $invoice->update(['status' => 'refunded']);
            return ['success' => true, 'data' => ['id' => $invoice->id]];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function executeGenerateReport(int $agencyId, array $data): array
    {
        try {
            $report = Report::create(array_merge($data, [
                'agency_id' => $agencyId,
                'status' => 'pending',
            ]));
            return ['success' => true, 'data' => ['id' => $report->id]];
        } catch (\Exception $e) {
            Log::error('Zapier generate_report failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function executeScheduleReport(int $agencyId, array $data): array
    {
        try {
            $report = Report::create(array_merge($data, [
                'agency_id' => $agencyId,
                'status' => 'scheduled',
            ]));
            return ['success' => true, 'data' => ['id' => $report->id]];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function executeExportReport(array $data): array
    {
        try {
            $report = Report::findOrFail($data['report_id']);
            $report->update(['status' => 'exporting']);
            return ['success' => true, 'data' => ['id' => $report->id]];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function executeShareReport(array $data): array
    {
        try {
            $report = Report::findOrFail($data['report_id']);
            return ['success' => true, 'data' => ['id' => $report->id]];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function executeGetReport(array $data): array
    {
        try {
            $report = Report::findOrFail($data['report_id']);
            return ['success' => true, 'data' => ['id' => $report->id, 'name' => $report->name]];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Trigger data fetch methods

    private function getRecentPublishedPosts(int $agencyId): array
    {
        return SocialPost::where('agency_id', $agencyId)
            ->published()
            ->orderBy('published_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getRecentFailedPosts(int $agencyId): array
    {
        return SocialPost::where('agency_id', $agencyId)
            ->where('status', 'failed')
            ->orderBy('failed_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getRecentScheduledPosts(int $agencyId): array
    {
        return SocialPost::where('agency_id', $agencyId)
            ->scheduled()
            ->orderBy('scheduled_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getRecentDraftPosts(int $agencyId): array
    {
        return SocialPost::where('agency_id', $agencyId)
            ->drafts()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getRecentComments(int $agencyId): array
    {
        return \App\Models\SocialComment::where('agency_id', $agencyId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getRecentCampaigns(int $agencyId): array
    {
        return Campaign::where('agency_id', $agencyId)
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getRecentPaidInvoices(int $agencyId): array
    {
        return Invoice::where('agency_id', $agencyId)
            ->paid()
            ->orderBy('paid_date', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getRecentInvoices(int $agencyId): array
    {
        return Invoice::where('agency_id', $agencyId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getRecentClients(int $agencyId): array
    {
        return Client::where('agency_id', $agencyId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getRecentReports(int $agencyId): array
    {
        return Report::where('agency_id', $agencyId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getRecentInboxMessages(int $agencyId): array
    {
        return \App\Models\InboxMessage::where('agency_id', $agencyId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getRecentMentions(int $agencyId): array
    {
        return \App\Models\SocialListening::where('agency_id', $agencyId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getRecentSocialAccounts(int $agencyId): array
    {
        return \App\Models\SocialAccount::where('agency_id', $agencyId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }
}
