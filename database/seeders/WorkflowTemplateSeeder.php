<?php

namespace Database\Seeders;

use App\Models\WorkflowTemplate;
use Illuminate\Database\Seeder;

class WorkflowTemplateSeeder extends Seeder
{
    public function run(): void
    {
        if (WorkflowTemplate::exists()) {
            $this->command->warn('Workflow templates already exist — skipping.');
            return;
        }

        $templates = [
            // Social Media Automation
            [
                'name' => 'Auto-Reply to Comments',
                'slug' => 'auto-reply-comments',
                'description' => 'Automatically reply to comments on your social media posts with a custom message.',
                'category' => 'social',
                'icon' => 'fa-reply',
                'nodes' => [
                    ['id' => 1, 'type' => 'trigger', 'subtype' => 'comment_received', 'x' => 100, 'y' => 200, 'config' => [], 'label' => 'Comment Received'],
                    ['id' => 2, 'type' => 'action', 'subtype' => 'auto_reply', 'x' => 350, 'y' => 200, 'config' => ['message' => 'Thanks for your comment! We appreciate your feedback.'], 'label' => 'Auto Reply'],
                ],
                'connections' => [['from' => 1, 'to' => 2]],
            ],
            [
                'name' => 'New Post Notification',
                'slug' => 'new-post-notification',
                'description' => 'Send a notification when a new post is published to any social account.',
                'category' => 'social',
                'icon' => 'fa-bell',
                'nodes' => [
                    ['id' => 1, 'type' => 'trigger', 'subtype' => 'post_published', 'x' => 100, 'y' => 200, 'config' => [], 'label' => 'Post Published'],
                    ['id' => 2, 'type' => 'action', 'subtype' => 'send_notification', 'x' => 350, 'y' => 200, 'config' => ['message' => 'New post published successfully!'], 'label' => 'Send Notification'],
                ],
                'connections' => [['from' => 1, 'to' => 2]],
            ],
            [
                'name' => 'Mention Response Bot',
                'slug' => 'mention-response-bot',
                'description' => 'Monitor brand mentions and send an acknowledgment when someone mentions you.',
                'category' => 'social',
                'icon' => 'fa-at',
                'nodes' => [
                    ['id' => 1, 'type' => 'trigger', 'subtype' => 'mention_received', 'x' => 100, 'y' => 200, 'config' => [], 'label' => 'Mention Received'],
                    ['id' => 2, 'type' => 'action', 'subtype' => 'send_notification', 'x' => 350, 'y' => 150, 'config' => ['message' => 'New brand mention detected!'], 'label' => 'Notify Team'],
                    ['id' => 3, 'type' => 'action', 'subtype' => 'auto_reply', 'x' => 350, 'y' => 250, 'config' => ['message' => 'Thanks for mentioning us!'], 'label' => 'Auto Reply'],
                ],
                'connections' => [['from' => 1, 'to' => 2], ['from' => 1, 'to' => 3]],
            ],
            // Content Automation
            [
                'name' => 'AI Content Generator',
                'slug' => 'ai-content-generator',
                'description' => 'Generate social media content using AI on a schedule.',
                'category' => 'content',
                'icon' => 'fa-robot',
                'nodes' => [
                    ['id' => 1, 'type' => 'trigger', 'subtype' => 'schedule', 'x' => 100, 'y' => 200, 'config' => ['cron' => '0 9 * * *'], 'label' => 'Daily at 9am'],
                    ['id' => 2, 'type' => 'action', 'subtype' => 'ai_generate', 'x' => 350, 'y' => 200, 'config' => ['prompt' => 'Generate an engaging social post about our latest product update'], 'label' => 'AI Generate'],
                    ['id' => 3, 'type' => 'action', 'subtype' => 'create_post', 'x' => 600, 'y' => 200, 'config' => [], 'label' => 'Create Post'],
                ],
                'connections' => [['from' => 1, 'to' => 2], ['from' => 2, 'to' => 3]],
            ],
            [
                'name' => 'Content to Email',
                'slug' => 'content-to-email',
                'description' => 'When new content is created, send an email notification to subscribers.',
                'category' => 'content',
                'icon' => 'fa-envelope',
                'nodes' => [
                    ['id' => 1, 'type' => 'trigger', 'subtype' => 'post_published', 'x' => 100, 'y' => 200, 'config' => [], 'label' => 'Post Published'],
                    ['id' => 2, 'type' => 'action', 'subtype' => 'send_email', 'x' => 350, 'y' => 200, 'config' => ['subject' => 'New Post: {post_title}'], 'label' => 'Send Email'],
                ],
                'connections' => [['from' => 1, 'to' => 2]],
            ],
            // Engagement
            [
                'name' => 'DM Auto-Responder',
                'slug' => 'dm-auto-responder',
                'description' => 'Automatically respond to direct messages with a welcome message.',
                'category' => 'engagement',
                'icon' => 'fa-comment-dots',
                'nodes' => [
                    ['id' => 1, 'type' => 'trigger', 'subtype' => 'message_received', 'x' => 100, 'y' => 200, 'config' => [], 'label' => 'DM Received'],
                    ['id' => 2, 'type' => 'action', 'subtype' => 'auto_reply', 'x' => 350, 'y' => 200, 'config' => ['message' => 'Hi! Thanks for reaching out. We\'ll get back to you soon!'], 'label' => 'Welcome Message'],
                ],
                'connections' => [['from' => 1, 'to' => 2]],
            ],
            [
                'name' => 'Failed Post Retry',
                'slug' => 'failed-post-retry',
                'description' => 'Automatically retry failed posts after a delay.',
                'category' => 'social',
                'icon' => 'fa-redo',
                'nodes' => [
                    ['id' => 1, 'type' => 'trigger', 'subtype' => 'post_failed', 'x' => 100, 'y' => 200, 'config' => [], 'label' => 'Post Failed'],
                    ['id' => 2, 'type' => 'util', 'subtype' => 'delay', 'x' => 350, 'y' => 200, 'config' => ['seconds' => 300], 'label' => 'Wait 5 min'],
                    ['id' => 3, 'type' => 'action', 'subtype' => 'send_notification', 'x' => 600, 'y' => 200, 'config' => ['message' => 'Post failed - retrying in 5 minutes'], 'label' => 'Notify Admin'],
                ],
                'connections' => [['from' => 1, 'to' => 2], ['from' => 2, 'to' => 3]],
            ],
            // Analytics
            [
                'name' => 'Weekly Report Webhook',
                'slug' => 'weekly-report-webhook',
                'description' => 'Send analytics data to an external service via webhook every week.',
                'category' => 'analytics',
                'icon' => 'fa-chart-bar',
                'nodes' => [
                    ['id' => 1, 'type' => 'trigger', 'subtype' => 'schedule', 'x' => 100, 'y' => 200, 'config' => ['cron' => '0 9 * * 1'], 'label' => 'Weekly Monday'],
                    ['id' => 2, 'type' => 'action', 'subtype' => 'webhook_call', 'x' => 350, 'y' => 200, 'config' => ['url' => 'https://hooks.example.com/weekly', 'method' => 'POST'], 'label' => 'Send Report'],
                ],
                'connections' => [['from' => 1, 'to' => 2]],
            ],
            [
                'name' => 'Webhook to Post',
                'slug' => 'webhook-to-post',
                'description' => 'Receive webhook data and automatically create a social post from it.',
                'category' => 'automation',
                'icon' => 'fa-globe',
                'nodes' => [
                    ['id' => 1, 'type' => 'trigger', 'subtype' => 'webhook', 'x' => 100, 'y' => 200, 'config' => [], 'label' => 'Webhook'],
                    ['id' => 2, 'type' => 'action', 'subtype' => 'create_post', 'x' => 350, 'y' => 200, 'config' => [], 'label' => 'Create Post'],
                ],
                'connections' => [['from' => 1, 'to' => 2]],
            ],
            [
                'name' => 'Conditional Platform Reply',
                'slug' => 'conditional-platform-reply',
                'description' => 'Reply differently based on which platform the comment came from.',
                'category' => 'social',
                'icon' => 'fa-code-branch',
                'nodes' => [
                    ['id' => 1, 'type' => 'trigger', 'subtype' => 'comment_received', 'x' => 100, 'y' => 200, 'config' => [], 'label' => 'Comment Received'],
                    ['id' => 2, 'type' => 'condition', 'subtype' => 'if', 'x' => 350, 'y' => 200, 'config' => ['field' => 'platform', 'operator' => 'equals', 'value' => 'instagram'], 'label' => 'Platform = Instagram?'],
                    ['id' => 3, 'type' => 'action', 'subtype' => 'auto_reply', 'x' => 600, 'y' => 150, 'config' => ['message' => 'Thanks for the love on Instagram!'], 'label' => 'IG Reply'],
                    ['id' => 4, 'type' => 'action', 'subtype' => 'auto_reply', 'x' => 600, 'y' => 250, 'config' => ['message' => 'Thank you for your comment!'], 'label' => 'Generic Reply'],
                ],
                'connections' => [['from' => 1, 'to' => 2], ['from' => 2, 'to' => 3], ['from' => 2, 'to' => 4]],
            ],
        ];

        foreach ($templates as $template) {
            WorkflowTemplate::create($template);
        }
    }
}
