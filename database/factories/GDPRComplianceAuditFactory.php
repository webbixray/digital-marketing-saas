<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\GDPRComplianceAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GDPRComplianceAuditFactory extends Factory
{
    protected $model = GDPRComplianceAudit::class;

    public function definition(): array
    {
        $actions = [
            'consent_recorded', 'consent_withdrawn', 'consent_expired',
            'export_requested', 'export_completed', 'export_processed',
            'deletion_requested', 'deletion_completed', 'deletion_processed',
            'data_retention_cleaned', 'ccpa_opt_out', 'ccpa_opt_in',
        ];
        $action = fake()->randomElement($actions);
        $category = str_starts_with($action, 'ccpa') ? 'ccpa' : 'gdpr';

        return [
            'agency_id' => Agency::factory(),
            'user_id' => User::factory(),
            'action' => $action,
            'category' => $category,
            'subject_type' => null,
            'subject_id' => null,
            'metadata' => ['source' => 'factory'],
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'severity' => 'info',
        ];
    }

    public function consentRecorded(): static
    {
        return $this->state(fn () => [
            'action' => 'consent_recorded',
            'category' => 'gdpr',
            'severity' => 'info',
        ]);
    }

    public function consentWithdrawn(): static
    {
        return $this->state(fn () => [
            'action' => 'consent_withdrawn',
            'category' => 'gdpr',
            'severity' => 'warning',
        ]);
    }

    public function exportCompleted(): static
    {
        return $this->state(fn () => [
            'action' => 'export_completed',
            'category' => 'gdpr',
            'severity' => 'info',
        ]);
    }

    public function deletionCompleted(): static
    {
        return $this->state(fn () => [
            'action' => 'deletion_completed',
            'category' => 'gdpr',
            'severity' => 'critical',
        ]);
    }

    public function ccpaOptOut(): static
    {
        return $this->state(fn () => [
            'action' => 'ccpa_opt_out',
            'category' => 'ccpa',
            'severity' => 'warning',
        ]);
    }

    public function critical(): static
    {
        return $this->state(['severity' => 'critical']);
    }

    public function warning(): static
    {
        return $this->state(['severity' => 'warning']);
    }

    public function forAgency(Agency $agency = null): static
    {
        return $this->state(fn () => ['agency_id' => $agency ?? Agency::factory()]);
    }

    public function forUser(User $user = null): static
    {
        return $this->state(fn () => ['user_id' => $user ?? User::factory()]);
    }
}
