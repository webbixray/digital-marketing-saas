<?php

namespace Tests\Unit\Models;

use App\Models\Agency;
use App\Models\AgentAccessToken;
use App\Models\BulkUpload;
use App\Models\ClientPortalSetting;
use App\Models\ContentGenome;
use App\Models\OnboardingProgress;
use App\Models\OptimalPostingTime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewModelsTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────
    // AgentAccessToken
    // ──────────────────────────────────────────────

    public function test_agent_access_token_can_be_created(): void
    {
        $token = AgentAccessToken::factory()->create();

        $this->assertDatabaseHas('agent_access_tokens', [
            'id' => $token->id,
            'name' => $token->name,
        ]);
    }

    public function test_agent_access_token_fillable_attributes(): void
    {
        $agency = Agency::factory()->create();
        $token = AgentAccessToken::create([
            'agency_id' => $agency->id,
            'name' => 'test-token',
            'token' => 'abc123',
            'abilities' => ['read', 'write'],
            'last_used_at' => null,
            'expires_at' => null,
            'is_active' => true,
        ]);

        $this->assertNotNull($token);
        $this->assertEquals('test-token', $token->name);
        $this->assertEquals('abc123', $token->token);
    }

    public function test_agent_access_token_casts(): void
    {
        $token = AgentAccessToken::factory()->create([
            'abilities' => ['read', 'write'],
            'is_active' => 1,
            'last_used_at' => '2024-01-01 00:00:00',
            'expires_at' => '2025-01-01 00:00:00',
        ]);

        $this->assertIsArray($token->abilities);
        $this->assertEquals(['read', 'write'], $token->abilities);
        $this->assertIsBool($token->is_active);
        $this->assertTrue($token->is_active);
        $this->assertInstanceOf(\Carbon\Carbon::class, $token->last_used_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $token->expires_at);
    }

    public function test_agent_access_token_belongs_to_agency(): void
    {
        $agency = Agency::factory()->create();
        $token = AgentAccessToken::factory()->create(['agency_id' => $agency->id]);

        $this->assertInstanceOf(Agency::class, $token->agency);
        $this->assertEquals($agency->id, $token->agency->id);
    }

    public function test_agent_access_token_scope_active(): void
    {
        AgentAccessToken::factory()->create(['is_active' => true]);
        AgentAccessToken::factory()->create(['is_active' => false]);

        $active = AgentAccessToken::active()->get();

        $this->assertCount(1, $active);
        $this->assertTrue($active->first()->is_active);
    }

    public function test_agent_access_token_scope_expired(): void
    {
        AgentAccessToken::factory()->create(['expires_at' => now()->subDay()]);
        AgentAccessToken::factory()->create(['expires_at' => now()->addDay()]);
        AgentAccessToken::factory()->create(['expires_at' => null]);

        $expired = AgentAccessToken::expired()->get();

        $this->assertCount(1, $expired);
        $this->assertTrue($expired->first()->expires_at->isPast());
    }

    public function test_agent_access_token_scope_not_expired(): void
    {
        AgentAccessToken::factory()->create(['expires_at' => now()->subDay()]);
        AgentAccessToken::factory()->create(['expires_at' => now()->addDay()]);
        AgentAccessToken::factory()->create(['expires_at' => null]);

        $notExpired = AgentAccessToken::notExpired()->get();

        $this->assertCount(2, $notExpired);
    }

    public function test_agent_access_token_is_valid_active_and_not_expired(): void
    {
        $token = AgentAccessToken::factory()->create([
            'is_active' => true,
            'expires_at' => now()->addDay(),
        ]);

        $this->assertTrue($token->isValid());
    }

    public function test_agent_access_token_is_not_valid_when_inactive(): void
    {
        $token = AgentAccessToken::factory()->create([
            'is_active' => false,
            'expires_at' => now()->addDay(),
        ]);

        $this->assertFalse($token->isValid());
    }

    public function test_agent_access_token_is_not_valid_when_expired(): void
    {
        $token = AgentAccessToken::factory()->create([
            'is_active' => true,
            'expires_at' => now()->subDay(),
        ]);

        $this->assertFalse($token->isValid());
    }

    public function test_agent_access_token_is_valid_when_no_expiration(): void
    {
        $token = AgentAccessToken::factory()->create([
            'is_active' => true,
            'expires_at' => null,
        ]);

        $this->assertTrue($token->isValid());
    }

    public function test_agent_access_token_mark_used_updates_last_used_at(): void
    {
        $token = AgentAccessToken::factory()->create(['last_used_at' => null]);

        $token->markUsed();

        $this->assertNotNull($token->last_used_at);
        $this->assertTrue($token->last_used_at->isCurrentMinute());
    }

    // ──────────────────────────────────────────────
    // ContentGenome
    // ──────────────────────────────────────────────

    public function test_content_genome_can_be_created(): void
    {
        $genome = ContentGenome::factory()->create();

        $this->assertDatabaseHas('content_genomes', ['id' => $genome->id]);
    }

    public function test_content_genome_fillable_attributes(): void
    {
        $agency = Agency::factory()->create();
        $genome = ContentGenome::create([
            'agency_id' => $agency->id,
            'platform' => 'facebook',
            'optimal_length' => 200,
            'best_hashtags' => ['#test'],
            'best_times' => ['09:00'],
            'content_themes' => ['educational'],
            'tone_patterns' => ['professional'],
            'media_types' => ['image'],
            'cta_patterns' => ['Learn More'],
            'engagement_prediction' => ['likes' => 0.8],
            'accuracy_score' => 0.9,
            'last_updated_at' => now(),
            'data_points_count' => 500,
            'genome_data' => ['version' => '1.0'],
        ]);

        $this->assertNotNull($genome);
        $this->assertEquals('facebook', $genome->platform);
        $this->assertEquals(200, $genome->optimal_length);
    }

    public function test_content_genome_casts(): void
    {
        $genome = ContentGenome::factory()->create([
            'best_hashtags' => ['#marketing', '#social'],
            'best_times' => ['09:00', '18:00'],
            'engagement_prediction' => ['likes' => 0.8, 'shares' => 0.5],
            'genome_data' => ['version' => '1.0'],
            'last_updated_at' => '2024-01-01 00:00:00',
        ]);

        $this->assertIsArray($genome->best_hashtags);
        $this->assertIsArray($genome->best_times);
        $this->assertIsArray($genome->content_themes);
        $this->assertIsArray($genome->tone_patterns);
        $this->assertIsArray($genome->media_types);
        $this->assertIsArray($genome->cta_patterns);
        $this->assertIsArray($genome->engagement_prediction);
        $this->assertIsArray($genome->genome_data);
        $this->assertInstanceOf(\Carbon\Carbon::class, $genome->last_updated_at);
    }

    public function test_content_genome_belongs_to_agency(): void
    {
        $agency = Agency::factory()->create();
        $genome = ContentGenome::factory()->create(['agency_id' => $agency->id]);

        $this->assertInstanceOf(Agency::class, $genome->agency);
        $this->assertEquals($agency->id, $genome->agency->id);
    }

    public function test_content_genome_scope_for_agency(): void
    {
        $agency1 = Agency::factory()->create();
        $agency2 = Agency::factory()->create();
        ContentGenome::factory()->create(['agency_id' => $agency1->id]);
        ContentGenome::factory()->create(['agency_id' => $agency2->id]);

        $results = ContentGenome::forAgency($agency1->id)->get();

        $this->assertCount(1, $results);
        $this->assertEquals($agency1->id, $results->first()->agency_id);
    }

    public function test_content_genome_scope_by_platform(): void
    {
        ContentGenome::factory()->create(['platform' => 'facebook']);
        ContentGenome::factory()->create(['platform' => 'instagram']);

        $results = ContentGenome::byPlatform('facebook')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('facebook', $results->first()->platform);
    }

    public function test_content_genome_scope_high_accuracy(): void
    {
        ContentGenome::factory()->create(['accuracy_score' => 0.9]);
        ContentGenome::factory()->create(['accuracy_score' => 0.5]);

        $results = ContentGenome::highAccuracy(0.75)->get();

        $this->assertCount(1, $results);
        $this->assertGreaterThanOrEqual(0.75, $results->first()->accuracy_score);
    }

    public function test_content_genome_scope_recently_updated(): void
    {
        ContentGenome::factory()->create(['last_updated_at' => now()->subDays(10)]);
        ContentGenome::factory()->create(['last_updated_at' => now()->subDays(60)]);

        $results = ContentGenome::recentlyUpdated(30)->get();

        $this->assertCount(1, $results);
    }

    // ──────────────────────────────────────────────
    // BulkUpload
    // ──────────────────────────────────────────────

    public function test_bulk_upload_can_be_created(): void
    {
        $upload = BulkUpload::factory()->create();

        $this->assertDatabaseHas('bulk_uploads', ['id' => $upload->id]);
    }

    public function test_bulk_upload_fillable_attributes(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->create();
        $upload = BulkUpload::create([
            'agency_id' => $agency->id,
            'user_id' => $user->id,
            'original_filename' => 'test.csv',
            'stored_path' => 'uploads/test.csv',
            'file_type' => 'csv',
            'total_rows' => 100,
            'processed_rows' => 0,
            'success_count' => 0,
            'error_count' => 0,
            'errors' => [],
            'status' => 'pending',
            'processed_at' => null,
        ]);

        $this->assertNotNull($upload);
        $this->assertEquals('test.csv', $upload->original_filename);
        $this->assertEquals(100, $upload->total_rows);
    }

    public function test_bulk_upload_casts(): void
    {
        $upload = BulkUpload::factory()->create([
            'errors' => ['row 1 invalid'],
            'total_rows' => 100,
            'processed_rows' => 50,
            'success_count' => 45,
            'error_count' => 5,
            'processed_at' => '2024-01-01 00:00:00',
        ]);

        $this->assertIsArray($upload->errors);
        $this->assertIsInt($upload->total_rows);
        $this->assertIsInt($upload->processed_rows);
        $this->assertIsInt($upload->success_count);
        $this->assertIsInt($upload->error_count);
        $this->assertInstanceOf(\Carbon\Carbon::class, $upload->processed_at);
    }

    public function test_bulk_upload_belongs_to_agency(): void
    {
        $agency = Agency::factory()->create();
        $upload = BulkUpload::factory()->create(['agency_id' => $agency->id]);

        $this->assertInstanceOf(Agency::class, $upload->agency);
    }

    public function test_bulk_upload_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $upload = BulkUpload::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $upload->user);
    }

    public function test_bulk_upload_scope_pending(): void
    {
        BulkUpload::factory()->create(['status' => 'pending']);
        BulkUpload::factory()->create(['status' => 'completed']);

        $results = BulkUpload::pending()->get();

        $this->assertCount(1, $results);
        $this->assertEquals('pending', $results->first()->status);
    }

    public function test_bulk_upload_scope_processing(): void
    {
        BulkUpload::factory()->create(['status' => 'processing']);
        BulkUpload::factory()->create(['status' => 'pending']);

        $results = BulkUpload::processing()->get();

        $this->assertCount(1, $results);
        $this->assertEquals('processing', $results->first()->status);
    }

    public function test_bulk_upload_scope_completed(): void
    {
        BulkUpload::factory()->create(['status' => 'completed']);
        BulkUpload::factory()->create(['status' => 'pending']);

        $results = BulkUpload::completed()->get();

        $this->assertCount(1, $results);
        $this->assertEquals('completed', $results->first()->status);
    }

    public function test_bulk_upload_scope_failed(): void
    {
        BulkUpload::factory()->create(['status' => 'failed']);
        BulkUpload::factory()->create(['status' => 'pending']);

        $results = BulkUpload::failed()->get();

        $this->assertCount(1, $results);
        $this->assertEquals('failed', $results->first()->status);
    }

    public function test_bulk_upload_is_pending(): void
    {
        $upload = BulkUpload::factory()->create(['status' => 'pending']);
        $this->assertTrue($upload->isPending());
    }

    public function test_bulk_upload_is_processing(): void
    {
        $upload = BulkUpload::factory()->create(['status' => 'processing']);
        $this->assertTrue($upload->isProcessing());
    }

    public function test_bulk_upload_is_completed(): void
    {
        $upload = BulkUpload::factory()->create(['status' => 'completed']);
        $this->assertTrue($upload->isCompleted());
    }

    public function test_bulk_upload_progress_percentage(): void
    {
        $upload = BulkUpload::factory()->create([
            'total_rows' => 100,
            'processed_rows' => 50,
        ]);

        $this->assertEquals(50, $upload->progress_percentage);
    }

    public function test_bulk_upload_progress_percentage_zero_when_no_rows(): void
    {
        $upload = BulkUpload::factory()->create([
            'total_rows' => 0,
            'processed_rows' => 0,
        ]);

        $this->assertEquals(0, $upload->progress_percentage);
    }

    // ──────────────────────────────────────────────
    // OptimalPostingTime
    // ──────────────────────────────────────────────

    public function test_optimal_posting_time_can_be_created(): void
    {
        $opt = OptimalPostingTime::factory()->create();

        $this->assertDatabaseHas('optimal_posting_times', ['id' => $opt->id]);
    }

    public function test_optimal_posting_time_fillable_attributes(): void
    {
        $agency = Agency::factory()->create();
        $opt = OptimalPostingTime::create([
            'agency_id' => $agency->id,
            'platform' => 'facebook',
            'day_of_week' => 1,
            'hour' => 14,
            'engagement_score' => 0.85,
            'sample_size' => 200,
        ]);

        $this->assertNotNull($opt);
        $this->assertEquals('facebook', $opt->platform);
        $this->assertEquals(0.85, $opt->engagement_score);
    }

    public function test_optimal_posting_time_casts(): void
    {
        $opt = OptimalPostingTime::factory()->create([
            'day_of_week' => 3,
            'hour' => 10,
            'engagement_score' => 0.95,
            'sample_size' => 150,
        ]);

        $this->assertIsInt($opt->day_of_week);
        $this->assertIsInt($opt->hour);
        $this->assertIsFloat($opt->engagement_score);
        $this->assertIsInt($opt->sample_size);
    }

    public function test_optimal_posting_time_belongs_to_agency(): void
    {
        $agency = Agency::factory()->create();
        $opt = OptimalPostingTime::factory()->create(['agency_id' => $agency->id]);

        $this->assertInstanceOf(Agency::class, $opt->agency);
    }

    public function test_optimal_posting_time_scope_for_platform(): void
    {
        OptimalPostingTime::factory()->create(['platform' => 'facebook']);
        OptimalPostingTime::factory()->create(['platform' => 'instagram']);

        $results = OptimalPostingTime::forPlatform('facebook')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('facebook', $results->first()->platform);
    }

    public function test_optimal_posting_time_scope_best(): void
    {
        OptimalPostingTime::factory()->create(['engagement_score' => 0.5]);
        OptimalPostingTime::factory()->create(['engagement_score' => 0.9]);

        $results = OptimalPostingTime::best()->get();

        $this->assertEquals(0.9, $results->first()->engagement_score);
    }

    public function test_optimal_posting_time_scope_for_day(): void
    {
        OptimalPostingTime::factory()->create(['day_of_week' => 1]);
        OptimalPostingTime::factory()->create(['day_of_week' => 3]);

        $results = OptimalPostingTime::forDay(1)->get();

        $this->assertCount(1, $results);
        $this->assertEquals(1, $results->first()->day_of_week);
    }

    public function test_optimal_posting_time_day_name_accessor(): void
    {
        $opt = OptimalPostingTime::factory()->create(['day_of_week' => 0]);
        $this->assertEquals('Sunday', $opt->day_name);

        $opt = OptimalPostingTime::factory()->create(['day_of_week' => 3]);
        $this->assertEquals('Wednesday', $opt->day_name);
    }

    public function test_optimal_posting_time_time_slot_accessor(): void
    {
        $opt = OptimalPostingTime::factory()->create(['hour' => 9]);
        $this->assertEquals('09:00', $opt->time_slot);

        $opt = OptimalPostingTime::factory()->create(['hour' => 23]);
        $this->assertEquals('23:00', $opt->time_slot);
    }

    // ──────────────────────────────────────────────
    // ClientPortalSetting
    // ──────────────────────────────────────────────

    public function test_client_portal_setting_can_be_created(): void
    {
        $setting = ClientPortalSetting::factory()->create();

        $this->assertDatabaseHas('client_portal_settings', ['id' => $setting->id]);
    }

    public function test_client_portal_setting_fillable_attributes(): void
    {
        $agency = Agency::factory()->create();
        $setting = ClientPortalSetting::create([
            'agency_id' => $agency->id,
            'brand_name' => 'Test Brand',
            'brand_color' => '#ff5733',
            'logo_url' => 'https://example.com/logo.png',
            'custom_domain' => 'test.example.com',
            'is_enabled' => true,
            'show_analytics' => true,
            'show_invoices' => false,
            'allow_approvals' => true,
            'show_team_activity' => false,
            'welcome_message' => 'Welcome!',
        ]);

        $this->assertNotNull($setting);
        $this->assertEquals('Test Brand', $setting->brand_name);
        $this->assertEquals('#ff5733', $setting->brand_color);
    }

    public function test_client_portal_setting_casts(): void
    {
        $setting = ClientPortalSetting::factory()->create([
            'is_enabled' => 1,
            'show_analytics' => 1,
            'show_invoices' => 0,
            'allow_approvals' => 1,
            'show_team_activity' => 0,
        ]);

        $this->assertIsBool($setting->is_enabled);
        $this->assertTrue($setting->is_enabled);
        $this->assertIsBool($setting->show_analytics);
        $this->assertTrue($setting->show_analytics);
        $this->assertIsBool($setting->show_invoices);
        $this->assertFalse($setting->show_invoices);
        $this->assertIsBool($setting->allow_approvals);
        $this->assertTrue($setting->allow_approvals);
        $this->assertIsBool($setting->show_team_activity);
        $this->assertFalse($setting->show_team_activity);
    }

    public function test_client_portal_setting_belongs_to_agency(): void
    {
        $agency = Agency::factory()->create();
        $setting = ClientPortalSetting::factory()->create(['agency_id' => $agency->id]);

        $this->assertInstanceOf(Agency::class, $setting->agency);
    }

    public function test_client_portal_setting_logo_url_accessor_returns_agency_logo_when_null(): void
    {
        $agency = Agency::factory()->create(['logo' => 'agency-logo.png']);
        $setting = ClientPortalSetting::factory()->create([
            'agency_id' => $agency->id,
            'logo_url' => null,
        ]);

        $this->assertEquals('agency-logo.png', $setting->logo_url);
    }

    public function test_client_portal_setting_logo_url_accessor_returns_own_value_when_set(): void
    {
        $agency = Agency::factory()->create(['logo' => 'agency-logo.png']);
        $setting = ClientPortalSetting::factory()->create([
            'agency_id' => $agency->id,
            'logo_url' => 'custom-logo.png',
        ]);

        $this->assertEquals('custom-logo.png', $setting->logo_url);
    }

    // ──────────────────────────────────────────────
    // OnboardingProgress
    // ──────────────────────────────────────────────

    public function test_onboarding_progress_can_be_created(): void
    {
        $progress = OnboardingProgress::factory()->create();

        $this->assertDatabaseHas('onboarding_progress', ['id' => $progress->id]);
    }

    public function test_onboarding_progress_fillable_attributes(): void
    {
        $agency = Agency::factory()->create();
        $progress = OnboardingProgress::create([
            'agency_id' => $agency->id,
            'step' => 'profile',
            'completed_at' => null,
            'data' => [],
        ]);

        $this->assertNotNull($progress);
        $this->assertEquals('profile', $progress->step);
    }

    public function test_onboarding_progress_casts(): void
    {
        $progress = OnboardingProgress::factory()->create([
            'completed_at' => '2024-01-01 00:00:00',
            'data' => ['key' => 'value'],
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $progress->completed_at);
        $this->assertIsArray($progress->data);
        $this->assertEquals(['key' => 'value'], $progress->data);
    }

    public function test_onboarding_progress_belongs_to_agency(): void
    {
        $agency = Agency::factory()->create();
        $progress = OnboardingProgress::factory()->create(['agency_id' => $agency->id]);

        $this->assertInstanceOf(Agency::class, $progress->agency);
    }

    public function test_onboarding_progress_scope_completed(): void
    {
        OnboardingProgress::factory()->create(['completed_at' => now()]);
        OnboardingProgress::factory()->create(['completed_at' => null]);

        $results = OnboardingProgress::completed()->get();

        $this->assertCount(1, $results);
        $this->assertNotNull($results->first()->completed_at);
    }

    public function test_onboarding_progress_scope_step(): void
    {
        OnboardingProgress::factory()->create(['step' => 'profile']);
        OnboardingProgress::factory()->create(['step' => 'branding']);

        $results = OnboardingProgress::step('profile')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('profile', $results->first()->step);
    }

    public function test_onboarding_progress_is_completed(): void
    {
        $progress = OnboardingProgress::factory()->create(['completed_at' => now()]);
        $this->assertTrue($progress->isCompleted());

        $progress = OnboardingProgress::factory()->create(['completed_at' => null]);
        $this->assertFalse($progress->isCompleted());
    }

    public function test_onboarding_progress_complete_sets_completed_at_and_data(): void
    {
        $progress = OnboardingProgress::factory()->create([
            'completed_at' => null,
            'data' => ['existing' => 'data'],
        ]);

        $progress->complete(['new' => 'info']);

        $this->assertNotNull($progress->completed_at);
        $this->assertEquals(['existing' => 'data', 'new' => 'info'], $progress->data);
    }
}
