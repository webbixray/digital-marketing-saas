<?php

namespace Tests\Feature\Email;

use App\Jobs\Email\SendEmailCampaign;
use App\Models\Agency;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Models\User;
use App\Services\Email\SmtpEmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class SendEmailCampaignJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_is_dispatched_via_service(): void
    {
        Bus::fake();

        $agency = Agency::factory()->create();
        $user = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'owner',
        ]);

        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $agency->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)
            ->post(route('email.campaigns.send', $campaign));

        $response->assertRedirect();
        Bus::assertDispatched(SendEmailCampaign::class);
        $this->assertTrue(true, 'Job dispatch verified via Bus::assertDispatched');
    }

    public function test_job_updates_campaign_status_to_sent(): void
    {
        $agency = Agency::factory()->create();
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $agency->id,
            'status' => 'draft',
        ]);

        EmailCampaignRecipient::factory()->count(3)->create([
            'email_campaign_id' => $campaign->id,
            'status' => 'pending',
        ]);

        $campaign->update(['recipients_count' => 3]);

        $job = new SendEmailCampaign($campaign->id);
        $job->handle(app(SmtpEmailService::class));

        $campaign->refresh();
        $this->assertEquals('sent', $campaign->status);
        $this->assertEquals(3, $campaign->sent_count);
    }

    public function test_job_handles_already_sent_campaign(): void
    {
        $agency = Agency::factory()->create();
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $agency->id,
            'status' => 'sent',
            'sent_count' => 5,
        ]);

        $campaign->recipients()->delete();

        $job = new SendEmailCampaign($campaign->id);
        $job->handle(app(SmtpEmailService::class));

        $campaign->refresh();
        $this->assertEquals(5, $campaign->sent_count);
    }
}
