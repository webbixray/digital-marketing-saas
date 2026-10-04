<?php

namespace Tests\Unit\Models;

use App\Models\ActivityFeed;
use App\Models\Comment;
use App\Models\ConsentRecord;
use App\Models\DataDeletionRequest;
use App\Models\EmailTemplate;
use App\Models\MediaAsset;
use App\Models\Report;
use App\Models\SocialPost;
use App\Models\WhiteLabelSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_asset_has_human_size_accessor(): void
    {
        $asset = MediaAsset::factory()->create(['file_size' => 1048576]);
        $this->assertEquals('1.00 MB', $asset->human_size);
    }

    public function test_media_asset_has_thumbnail_url_accessor(): void
    {
        $asset = MediaAsset::factory()->create(['file_path' => 'media/1/test.jpg']);
        $this->assertStringContainsString('storage/media/1/test.jpg', $asset->thumbnail_url);
    }

    public function test_white_label_has_display_name_accessor(): void
    {
        $setting = WhiteLabelSetting::factory()->create(['brand_name' => 'Test Brand']);
        $this->assertEquals('Test Brand', $setting->display_name);
    }

    public function test_white_label_falls_back_to_app_name(): void
    {
        $setting = WhiteLabelSetting::factory()->create(['brand_name' => null]);
        $this->assertEquals(config('app.name'), $setting->display_name);
    }

    public function test_email_template_renders_variables(): void
    {
        $template = EmailTemplate::factory()->create([
            'subject' => 'Hello {{ name }}',
            'html_content' => '<h1>Welcome {{ name }}</h1>',
        ]);
        $rendered = $template->render(['name' => 'John']);
        $this->assertEquals('Hello John', $rendered['subject']);
        $this->assertEquals('<h1>Welcome John</h1>', $rendered['html']);
    }

    public function test_report_has_download_url_accessor(): void
    {
        $report = Report::factory()->create(['file_path' => 'reports/test.pdf']);
        $this->assertStringContainsString('storage/reports/test.pdf', $report->download_url);
    }

    public function test_activity_feed_has_icon_accessor(): void
    {
        $activity = ActivityFeed::factory()->create(['action' => 'post_created']);
        $this->assertEquals('fa-pen-fancy', $activity->icon);
    }

    public function test_activity_feed_has_description_accessor(): void
    {
        $activity = ActivityFeed::factory()->create(['action' => 'post_created']);
        $this->assertStringContainsString('created a new post', $activity->description);
    }

    public function test_consent_record_has_granted_scope(): void
    {
        ConsentRecord::factory()->create(['consent_type' => 'marketing', 'granted' => true]);
        ConsentRecord::factory()->create(['consent_type' => 'analytics', 'granted' => false]);
        $this->assertEquals(1, ConsentRecord::granted()->count());
    }

    public function test_data_deletion_request_has_overdue_scope(): void
    {
        DataDeletionRequest::factory()->create(['status' => 'pending', 'scheduled_at' => now()->subDay()]);
        DataDeletionRequest::factory()->create(['status' => 'pending', 'scheduled_at' => now()->addDays(30)]);
        $this->assertEquals(1, DataDeletionRequest::overdue()->count());
    }

    public function test_comment_has_for_commentable_scope(): void
    {
        Comment::factory()->create(['commentable_type' => 'App\Models\SocialPost', 'commentable_id' => 1]);
        Comment::factory()->create(['commentable_type' => 'App\Models\SocialPost', 'commentable_id' => 2]);
        // forceFill is required: 'id' is not mass-assignable, so new SocialPost(['id' => 1]) drops it.
        $post = (new SocialPost)->forceFill(['id' => 1]);
        $this->assertEquals(1, Comment::forCommentable($post)->count());
    }
}
