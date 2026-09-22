<?php

namespace Tests\Feature\GDPR;

use App\Models\Agency;
use App\Models\ConsentRecord;
use App\Models\DataDeletionRequest;
use App\Models\DataExportRequest;
use App\Models\GDPRComplianceAudit;
use App\Models\User;
use App\Services\GDPR\GDPRComplianceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GDPRAdminComplianceTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;
    private User $adminUser;
    private User $regularUser;
    private GDPRComplianceService $gdprService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create([
            'data_retention_days' => 365,
        ]);

        // Create roles
        Role::create(['name' => 'owner', 'guard_name' => 'web']);
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'member', 'guard_name' => 'web']);

        $this->adminUser = User::factory()->create([
            'agency_id' => $this->agency->id,
        ]);
        $this->adminUser->assignRole('admin');

        $this->regularUser = User::factory()->create([
            'agency_id' => $this->agency->id,
        ]);
        $this->regularUser->assignRole('member');

        $this->gdprService = app(GDPRComplianceService::class);
    }

    // ==================== ADMIN DASHBOARD ====================

    public function test_admin_dashboard_loads_for_admin_user(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.gdpr.dashboard'));

        $response->assertOk();
        $response->assertViewIs('gdpr.admin.dashboard');
        $response->assertViewHas('overview');
        $response->assertViewHas('pendingRequests');
        $response->assertViewHas('recentAudits');
        $response->assertViewHas('consentStats');
    }

    public function test_admin_dashboard_shows_compliance_overview_stats(): void
    {
        ConsentRecord::factory()->count(3)->create(['user_id' => $this->regularUser->id]);
        DataExportRequest::factory()->create(['user_id' => $this->regularUser->id]);
        GDPRComplianceAudit::factory()->count(5)->forAgency($this->agency)->create();

        $response = $this->actingAs($this->adminUser)->get(route('admin.gdpr.dashboard'));

        $response->assertOk();
        $overview = $response->viewData('overview');
        $this->assertArrayHasKey('total_audits', $overview);
        $this->assertArrayHasKey('pending_exports', $overview);
        $this->assertArrayHasKey('active_consents', $overview);
        $this->assertArrayHasKey('ccpa_opt_outs', $overview);
        $this->assertEquals(5, $overview['total_audits']);
    }

    public function test_admin_dashboard_shows_pending_requests(): void
    {
        DataExportRequest::factory()->create(['user_id' => $this->regularUser->id]);
        DataDeletionRequest::factory()->create(['user_id' => $this->regularUser->id]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.gdpr.dashboard'));

        $response->assertOk();
        $pending = $response->viewData('pendingRequests');
        $this->assertCount(2, $pending);
    }

    public function test_non_admin_cannot_access_dashboard(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.gdpr.dashboard'));

        $response->assertForbidden();
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get(route('admin.gdpr.dashboard'));

        $response->assertRedirect(route('login'));
    }

    // ==================== PROCESS EXPORT ====================

    public function test_admin_can_process_export_request(): void
    {
        $export = DataExportRequest::factory()->create([
            'user_id' => $this->regularUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.gdpr.process-export', $export->id));

        $response->assertRedirect(route('admin.gdpr.dashboard'));
        $response->assertSessionHas('success');

        $export->refresh();
        $this->assertEquals('completed', $export->status);
        $this->assertNotNull($export->file_path);
        $this->assertNotNull($export->completed_at);
    }

    public function test_processing_export_creates_audit_log(): void
    {
        $export = DataExportRequest::factory()->create([
            'user_id' => $this->regularUser->id,
        ]);

        $this->actingAs($this->adminUser)
            ->post(route('admin.gdpr.process-export', $export->id));

        $this->assertDatabaseHas('gdpr_compliance_audits', [
            'action' => 'export_processed',
            'category' => 'gdpr',
            'agency_id' => $this->agency->id,
        ]);
    }

    public function test_cannot_process_already_completed_export(): void
    {
        $export = DataExportRequest::factory()->completed()->create([
            'user_id' => $this->regularUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.gdpr.process-export', $export->id));

        $response->assertRedirect(route('admin.gdpr.dashboard'));
        $response->assertSessionHas('error');
    }

    // ==================== PROCESS DELETION ====================

    public function test_admin_can_process_deletion_request(): void
    {
        $deletion = DataDeletionRequest::factory()->create([
            'user_id' => $this->regularUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.gdpr.process-deletion', $deletion->id));

        $response->assertRedirect(route('admin.gdpr.dashboard'));
        $response->assertSessionHas('success');

        $deletion->refresh();
        $this->assertEquals('completed', $deletion->status);
    }

    public function test_processing_deletion_logs_critical_audit(): void
    {
        $deletion = DataDeletionRequest::factory()->create([
            'user_id' => $this->regularUser->id,
        ]);

        $this->actingAs($this->adminUser)
            ->post(route('admin.gdpr.process-deletion', $deletion->id));

        $this->assertDatabaseHas('gdpr_compliance_audits', [
            'action' => 'deletion_processed',
            'category' => 'gdpr',
            'severity' => 'critical',
            'agency_id' => $this->agency->id,
        ]);
    }

    // ==================== AUDIT LOG ====================

    public function test_audit_log_page_loads(): void
    {
        GDPRComplianceAudit::factory()->count(10)->forAgency($this->agency)->create();

        $response = $this->actingAs($this->adminUser)->get(route('admin.gdpr.audit-log'));

        $response->assertOk();
        $response->assertViewIs('gdpr.admin.audit-log');
        $response->assertViewHas('audits');
        $response->assertViewHas('actions');
        $response->assertViewHas('categories');
        $response->assertViewHas('severities');
    }

    public function test_audit_log_can_filter_by_action(): void
    {
        GDPRComplianceAudit::factory()->consentRecorded()->create(['agency_id' => $this->agency->id]);
        GDPRComplianceAudit::factory()->ccpaOptOut()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.gdpr.audit-log', [
            'action' => 'consent_recorded',
        ]));

        $response->assertOk();
        $audits = $response->viewData('audits');
        $this->assertCount(1, $audits);
        $this->assertEquals('consent_recorded', $audits->first()->action);
    }

    public function test_audit_log_can_filter_by_category(): void
    {
        GDPRComplianceAudit::factory()->create(['agency_id' => $this->agency->id, 'category' => 'gdpr']);
        GDPRComplianceAudit::factory()->create(['agency_id' => $this->agency->id, 'category' => 'ccpa']);

        $response = $this->actingAs($this->adminUser)->get(route('admin.gdpr.audit-log', [
            'category' => 'ccpa',
        ]));

        $response->assertOk();
        $audits = $response->viewData('audits');
        $this->assertCount(1, $audits);
        $this->assertEquals('ccpa', $audits->first()->category);
    }

    public function test_audit_log_can_filter_by_severity(): void
    {
        GDPRComplianceAudit::factory()->create(['agency_id' => $this->agency->id, 'severity' => 'info']);
        GDPRComplianceAudit::factory()->create(['agency_id' => $this->agency->id, 'severity' => 'critical']);

        $response = $this->actingAs($this->adminUser)->get(route('admin.gdpr.audit-log', [
            'severity' => 'critical',
        ]));

        $response->assertOk();
        $audits = $response->viewData('audits');
        $this->assertCount(1, $audits);
        $this->assertEquals('critical', $audits->first()->severity);
    }

    public function test_audit_log_can_filter_by_date_range(): void
    {
        GDPRComplianceAudit::factory()->create([
            'agency_id' => $this->agency->id,
            'created_at' => now()->subDays(10),
        ]);
        GDPRComplianceAudit::factory()->create([
            'agency_id' => $this->agency->id,
            'created_at' => now()->subDays(1),
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.gdpr.audit-log', [
            'date_from' => now()->subDays(3)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ]));

        $response->assertOk();
        $audits = $response->viewData('audits');
        $this->assertCount(1, $audits);
    }

    public function test_non_admin_cannot_access_audit_log(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.gdpr.audit-log'));

        $response->assertForbidden();
    }

    // ==================== DATA RETENTION ====================

    public function test_retention_cleanup_logs_audit_entry(): void
    {
        // Call the service directly since route needs agency context
        $this->gdprService->runDataRetentionCleanup($this->agency->id);

        $this->assertDatabaseHas('gdpr_compliance_audits', [
            'action' => 'data_retention_cleaned',
            'agency_id' => $this->agency->id,
        ]);
    }

    // ==================== CONSENT EXPIRY ====================

    public function test_consent_expiry_marks_expired_consents(): void
    {
        ConsentRecord::factory()->expired()->create(['user_id' => $this->regularUser->id]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.gdpr.consent-expiry'));

        $response->assertRedirect(route('admin.gdpr.dashboard'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('consent_records', [
            'user_id' => $this->regularUser->id,
            'granted' => false,
        ]);
    }

    public function test_consent_expiry_logs_audit_entry(): void
    {
        ConsentRecord::factory()->expired()->create(['user_id' => $this->regularUser->id]);

        $this->actingAs($this->adminUser)->post(route('admin.gdpr.consent-expiry'));

        $this->assertDatabaseHas('gdpr_compliance_audits', [
            'action' => 'consent_expired',
        ]);
    }

    // ==================== CCPA OPT-OUT ====================

    public function test_user_can_opt_out_of_data_sale(): void
    {
        $response = $this->actingAs($this->regularUser)->post(route('gdpr.ccpa-opt-out'), [
            'opt_out' => true,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true, 'ccpa_opt_out' => true]);

        $this->regularUser->refresh();
        $this->assertTrue((bool) $this->regularUser->ccpa_opt_out);
        $this->assertNotNull($this->regularUser->ccpa_opt_out_at);
    }

    public function test_user_can_reverse_opt_out(): void
    {
        $this->regularUser->update([
            'ccpa_opt_out' => true,
            'ccpa_opt_out_at' => now(),
        ]);

        $response = $this->actingAs($this->regularUser)->post(route('gdpr.ccpa-opt-out'), [
            'opt_out' => false,
        ]);

        $response->assertOk();
        $this->regularUser->refresh();
        $this->assertFalse((bool) $this->regularUser->ccpa_opt_out);
    }

    public function test_ccpa_opt_out_creates_audit_log(): void
    {
        $this->actingAs($this->regularUser)->post(route('gdpr.ccpa-opt-out'), [
            'opt_out' => true,
        ]);

        $this->assertDatabaseHas('gdpr_compliance_audits', [
            'action' => 'ccpa_opt_out',
            'category' => 'ccpa',
            'user_id' => $this->regularUser->id,
        ]);
    }

    // ==================== SERVICE METHODS ====================

    public function test_service_audit_log_creates_record(): void
    {
        $this->gdprService->auditLog(
            action: 'test_action',
            category: 'gdpr',
            agencyId: $this->agency->id,
            userId: $this->adminUser->id,
            severity: 'info'
        );

        $this->assertDatabaseHas('gdpr_compliance_audits', [
            'action' => 'test_action',
            'category' => 'gdpr',
            'agency_id' => $this->agency->id,
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_service_get_compliance_overview(): void
    {
        ConsentRecord::factory()->granted()->create(['user_id' => $this->regularUser->id]);
        DataExportRequest::factory()->create(['user_id' => $this->regularUser->id]);
        GDPRComplianceAudit::factory()->forAgency($this->agency)->create();

        $overview = $this->gdprService->getComplianceOverview($this->agency->id);

        $this->assertIsArray($overview);
        $this->assertArrayHasKey('total_audits', $overview);
        $this->assertArrayHasKey('pending_exports', $overview);
        $this->assertArrayHasKey('active_consents', $overview);
        $this->assertGreaterThanOrEqual(1, $overview['active_consents']);
    }

    public function test_service_get_pending_requests(): void
    {
        DataExportRequest::factory()->create(['user_id' => $this->regularUser->id]);
        DataDeletionRequest::factory()->create(['user_id' => $this->regularUser->id]);

        $pending = $this->gdprService->getPendingRequests($this->agency->id);

        $this->assertCount(2, $pending);
    }

    public function test_service_run_consent_expiry(): void
    {
        ConsentRecord::factory()->expired()->create(['user_id' => $this->regularUser->id]);
        ConsentRecord::factory()->granted()->create(['user_id' => $this->regularUser->id]);

        $expiredCount = $this->gdprService->runConsentExpiry();

        $this->assertGreaterThanOrEqual(1, $expiredCount);
    }

    public function test_service_ccpa_opt_out_and_in(): void
    {
        $this->gdprService->ccpaOptOut($this->regularUser->id);
        $this->regularUser->refresh();
        $this->assertTrue((bool) $this->regularUser->ccpa_opt_out);

        $this->gdprService->ccpaOptIn($this->regularUser->id);
        $this->regularUser->refresh();
        $this->assertFalse((bool) $this->regularUser->ccpa_opt_out);
    }

    public function test_service_get_audit_logs_with_filters(): void
    {
        GDPRComplianceAudit::factory()->create([
            'agency_id' => $this->agency->id,
            'action' => 'consent_recorded',
            'category' => 'gdpr',
            'severity' => 'info',
        ]);
        GDPRComplianceAudit::factory()->create([
            'agency_id' => $this->agency->id,
            'action' => 'ccpa_opt_out',
            'category' => 'ccpa',
            'severity' => 'warning',
        ]);

        $logs = $this->gdprService->getAuditLogs(['category' => 'ccpa']);
        $this->assertCount(1, $logs);
        $this->assertEquals('ccpa_opt_out', $logs->first()->action);
    }

    public function test_service_get_consent_statistics(): void
    {
        ConsentRecord::factory()->marketing()->granted()->create(['user_id' => $this->regularUser->id]);
        ConsentRecord::factory()->analytics()->granted()->create(['user_id' => $this->regularUser->id]);

        $stats = $this->gdprService->getConsentStatistics($this->agency->id);

        $this->assertArrayHasKey('by_type', $stats);
        $this->assertArrayHasKey('by_status', $stats);
        $this->assertArrayHasKey('by_day', $stats);
        $this->assertArrayHasKey('marketing', $stats['by_type']);
        $this->assertArrayHasKey('analytics', $stats['by_type']);
    }

    // ==================== CONSENT WITH EXPIRY ====================

    public function test_service_record_consent_with_expiry(): void
    {
        $consent = $this->gdprService->recordConsent(
            $this->agency->id,
            $this->regularUser->id,
            'marketing',
            365
        );

        $this->assertNotNull($consent->expires_at);
        $this->assertTrue($consent->granted);
    }

    // ==================== USER CCPA FIELDS ====================

    public function test_user_factory_ccpa_opted_out_state(): void
    {
        $user = User::factory()->ccpaOptedOut()->create([
            'agency_id' => $this->agency->id,
        ]);

        $this->assertTrue((bool) $user->ccpa_opt_out);
        $this->assertNotNull($user->ccpa_opt_out_at);
    }

    // ==================== AGENCY RETENTION FIELDS ====================

    public function test_agency_has_data_retention_days(): void
    {
        $this->assertEquals(365, $this->agency->data_retention_days);
    }

    // ==================== GDPR FACTORY ====================

    public function test_gdpr_audit_factory_creates_valid_record(): void
    {
        $audit = GDPRComplianceAudit::factory()->create();

        $this->assertDatabaseHas('gdpr_compliance_audits', [
            'id' => $audit->id,
            'action' => $audit->action,
            'category' => $audit->category,
        ]);
    }

    public function test_gdpr_audit_factory_ccpa_state(): void
    {
        $audit = GDPRComplianceAudit::factory()->ccpaOptOut()->create();

        $this->assertEquals('ccpa_opt_out', $audit->action);
        $this->assertEquals('ccpa', $audit->category);
    }

    // ==================== CONSENT RECORD FACTORY ENHANCEMENTS ====================

    public function test_consent_record_with_expiry_state(): void
    {
        $consent = ConsentRecord::factory()->withExpiry(30)->create([
            'user_id' => $this->regularUser->id,
        ]);

        $this->assertNotNull($consent->expires_at);
        $this->assertTrue($consent->granted);
    }

    public function test_consent_record_expired_state(): void
    {
        $consent = ConsentRecord::factory()->expired()->create([
            'user_id' => $this->regularUser->id,
        ]);

        $this->assertNotNull($consent->expires_at);
        $this->assertTrue($consent->expires_at->isPast());
    }
}
