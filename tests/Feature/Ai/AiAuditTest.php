<?php

namespace Tests\Feature\AI;

use App\Models\Agency;
use App\Models\AiAuditLog;
use App\Models\User;
use App\Services\AI\Audit\AiAuditService;
use App\Services\AI\Audit\BiasDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiAuditTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create(['agency_id' => $this->agency->id]);
    }

    public function test_it_creates_audit_log_entry(): void
    {
        $service = app(AiAuditService::class);

        $log = $service->logRequest(
            agencyId: $this->agency->id,
            userId: $this->user->id,
            action: 'generate',
            model: 'gpt-4o',
            input: 'Generate a social post about marketing',
            output: 'Check out our latest marketing tips for success!',
        );

        $this->assertInstanceOf(AiAuditLog::class, $log);
        $this->assertEquals($this->agency->id, $log->agency_id);
        $this->assertEquals($this->user->id, $log->user_id);
        $this->assertEquals('generate', $log->action);
        $this->assertEquals('gpt-4o', $log->model_used);
        $this->assertNotNull($log->input_hash);
        $this->assertNotNull($log->output_hash);
    }

    public function test_it_detects_bias_in_text(): void
    {
        $service = app(BiasDetectionService::class);

        $result = $service->detectBias('The chairman will review the blacklist of candidates');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('findings', $result);
        $this->assertGreaterThan(0, $result['score']);
    }

    public function test_it_calculates_bias_score(): void
    {
        $service = app(BiasDetectionService::class);

        $score = $service->calculateBiasScore('Normal text without any biased terms');

        $this->assertIsFloat($score);
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(1, $score);
    }

    public function test_it_checks_compliance(): void
    {
        $service = app(AiAuditService::class);

        $result = $service->checkCompliance('Clean output content');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('pass', $result['status']);
    }

    public function test_it_flags_high_toxicity(): void
    {
        $service = app(AiAuditService::class);

        $result = $service->checkCompliance('I hate this stupid worthless content');

        $this->assertIsArray($result);
        $this->assertContains($result['status'], ['fail', 'warn']);
    }

    public function test_it_retrieves_flagged_content(): void
    {
        AiAuditLog::factory()->create([
            'agency_id' => $this->agency->id,
            'user_id' => $this->user->id,
            'compliance_status' => 'fail',
            'action' => 'generate',
        ]);

        AiAuditLog::factory()->create([
            'agency_id' => $this->agency->id,
            'user_id' => $this->user->id,
            'compliance_status' => 'pass',
            'action' => 'generate',
        ]);

        $service = app(AiAuditService::class);
        $flagged = $service->getFlaggedContent($this->agency->id);

        $this->assertCount(1, $flagged);
        $this->assertEquals('fail', $flagged->first()->compliance_status);
    }

    public function test_it_generates_compliance_report(): void
    {
        AiAuditLog::factory()->count(5)->create([
            'agency_id' => $this->agency->id,
            'user_id' => $this->user->id,
            'compliance_status' => 'pass',
            'action' => 'generate',
        ]);

        AiAuditLog::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'user_id' => $this->user->id,
            'compliance_status' => 'fail',
            'action' => 'translate',
        ]);

        $service = app(AiAuditService::class);
        $report = $service->getComplianceReport($this->agency->id);

        $this->assertEquals(7, $report['total_logs']);
        $this->assertEquals(5, $report['pass_count']);
        $this->assertEquals(2, $report['fail_count']);
        $this->assertArrayHasKey('flagged_by_action', $report);
        $this->assertArrayHasKey('trend', $report);
    }

    public function test_it_filters_logs_by_compliance_status(): void
    {
        AiAuditLog::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'user_id' => $this->user->id,
            'compliance_status' => 'pass',
            'action' => 'generate',
        ]);

        AiAuditLog::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'user_id' => $this->user->id,
            'compliance_status' => 'warn',
            'action' => 'translate',
        ]);

        $service = app(AiAuditService::class);
        $logs = $service->getAuditLogs($this->agency->id, ['compliance_status' => 'pass']);

        $this->assertEquals(3, $logs->total());
    }

    public function test_it_requires_authentication(): void
    {
        $response = $this->get(route('ai-audit.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_audit_index(): void
    {
        $response = $this->actingAs($this->user)->get(route('ai-audit.index'));
        $response->assertOk();
        $response->assertViewIs('ai-audit.index');
    }

    public function test_cross_agency_logs_are_isolated(): void
    {
        $otherAgency = Agency::factory()->create();
        $otherUser = User::factory()->create(['agency_id' => $otherAgency->id]);

        AiAuditLog::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'user_id' => $this->user->id,
            'compliance_status' => 'pass',
            'action' => 'generate',
        ]);

        AiAuditLog::factory()->count(5)->create([
            'agency_id' => $otherAgency->id,
            'user_id' => $otherUser->id,
            'compliance_status' => 'fail',
            'action' => 'generate',
        ]);

        $service = app(AiAuditService::class);
        $logs = $service->getAuditLogs($this->agency->id);

        $this->assertEquals(3, $logs->total());
    }

    public function test_it_gets_bias_categories(): void
    {
        $service = app(BiasDetectionService::class);
        $categories = $service->getBiasCategories();

        $this->assertIsArray($categories);
        $this->assertNotEmpty($categories);
    }
}
