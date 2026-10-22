<?php

namespace Tests\Feature\Email;

use App\Models\Agency;
use App\Models\Client;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailCampaignControllerTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'owner',
        ]);
    }

    // ── Index ──────────────────────────────────────────────────────────

    public function test_index_requires_auth(): void
    {
        $response = $this->get(route('email.campaigns.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_index_returns_campaigns_for_own_agency(): void
    {
        EmailCampaign::factory()->count(3)->create(['agency_id' => $this->agency->id]);
        EmailCampaign::factory()->count(2)->create(['agency_id' => Agency::factory()->create()->id]);

        $response = $this->actingAs($this->user)->get(route('email.campaigns.index'));

        $response->assertOk();
        $campaigns = $response->viewData('campaigns');
        $this->assertCount(3, $campaigns);
    }

    // ── Show ───────────────────────────────────────────────────────────

    public function test_show_requires_auth(): void
    {
        $campaign = EmailCampaign::factory()->create(['agency_id' => $this->agency->id]);
        $response = $this->get(route('email.campaigns.show', $campaign));
        $response->assertRedirect(route('login'));
    }

    public function test_show_returns_campaign_for_own_agency(): void
    {
        EmailCampaign::factory()->create([
            'agency_id' => $this->agency->id,
            'name' => 'My Campaign',
        ]);

        $response = $this->actingAs($this->user)->get(route('email.campaigns.show', 1));
        $response->assertOk();
        $this->assertStringContainsString('My Campaign', $response->getContent());
    }

    public function test_show_returns_404_for_other_agency(): void
    {
        $campaign = EmailCampaign::factory()->create(['agency_id' => Agency::factory()->create()->id]);
        $response = $this->actingAs($this->user)->get(route('email.campaigns.show', $campaign));
        $response->assertNotFound();
    }

    public function test_show_returns_404_for_nonexistent(): void
    {
        $response = $this->actingAs($this->user)->get(route('email.campaigns.show', 999));
        $response->assertNotFound();
    }

    // ── Create ─────────────────────────────────────────────────────────

    public function test_create_requires_auth(): void
    {
        $response = $this->get(route('email.campaigns.create'));
        $response->assertRedirect(route('login'));
    }

    public function test_create_returns_form(): void
    {
        $response = $this->actingAs($this->user)->get(route('email.campaigns.create'));
        $response->assertOk();
        $response->assertViewIs('email.campaigns.create');
    }

    // ── Store ──────────────────────────────────────────────────────────

    public function test_store_requires_auth(): void
    {
        $response = $this->post(route('email.campaigns.store'), [
            'name' => 'Test', 'type' => 'newsletter', 'subject' => 'Test',
        ]);
        $response->assertRedirect(route('login'));
    }

    public function test_store_creates_campaign(): void
    {
        $response = $this->actingAs($this->user)->post(route('email.campaigns.store'), [
            'name' => 'New Campaign',
            'type' => 'newsletter',
            'subject' => 'Test Subject',
            'content' => '<p>Content</p>',
            'from_name' => 'Agency',
            'from_email' => 'test@agency.com',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('email_campaigns', [
            'name' => 'New Campaign',
            'agency_id' => $this->agency->id,
            'status' => 'draft',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('email.campaigns.store'), ['name' => '']);
        $response->assertSessionHasErrors(['name']);
    }

    public function test_store_validates_type(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('email.campaigns.store'), [
                'name' => 'Test', 'type' => 'invalid',
            ]);
        $response->assertSessionHasErrors(['type']);
    }

    public function test_store_validates_email(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('email.campaigns.store'), [
                'name' => 'Test', 'type' => 'newsletter', 'subject' => 'Test',
                'from_email' => 'not-an-email',
            ]);
        $response->assertSessionHasErrors(['from_email']);
    }

    public function test_store_generates_slug(): void
    {
        $this->actingAs($this->user)->post(route('email.campaigns.store'), [
            'name' => 'My Campaign',
            'type' => 'newsletter',
            'subject' => 'Subject',
        ]);

        $this->assertDatabaseHas('email_campaigns', [
            'name' => 'My Campaign',
        ]);
        $campaign = EmailCampaign::where('name', 'My Campaign')->first();
        $this->assertNotNull($campaign->slug);
        $this->assertStringStartsWith('my-campaign-', $campaign->slug);
    }

    // ── Edit ───────────────────────────────────────────────────────────

    public function test_edit_requires_auth(): void
    {
        $campaign = EmailCampaign::factory()->create(['agency_id' => $this->agency->id]);
        $response = $this->get(route('email.campaigns.edit', $campaign));
        $response->assertRedirect(route('login'));
    }

    public function test_edit_returns_form(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $this->agency->id,
            'name' => 'Edit Me',
        ]);

        $response = $this->actingAs($this->user)->get(route('email.campaigns.edit', $campaign));

        $response->assertOk();
        $response->assertViewIs('email.campaigns.edit');
        $this->assertEquals('Edit Me', $response->viewData('campaign')->name);
    }

    public function test_edit_returns_404_for_other_agency(): void
    {
        $campaign = EmailCampaign::factory()->create(['agency_id' => Agency::factory()->create()->id]);
        $response = $this->actingAs($this->user)->get(route('email.campaigns.edit', $campaign));
        $response->assertNotFound();
    }

    // ── Update ─────────────────────────────────────────────────────────

    public function test_update_requires_auth(): void
    {
        $campaign = EmailCampaign::factory()->create(['agency_id' => $this->agency->id]);
        $response = $this->put(route('email.campaigns.update', $campaign), [
            'name' => 'Updated', 'type' => 'newsletter', 'subject' => 'Updated',
        ]);
        $response->assertRedirect(route('login'));
    }

    public function test_update_modifies_campaign(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $this->agency->id,
            'name' => 'Original',
            'subject' => 'Original Subject',
        ]);

        $response = $this->actingAs($this->user)->put(route('email.campaigns.update', $campaign), [
            'name' => 'Updated Name',
            'type' => 'newsletter',
            'subject' => 'Updated Subject',
        ]);

        $response->assertRedirect(route('email.campaigns.show', $campaign));
        $campaign->refresh();
        $this->assertEquals('Updated Name', $campaign->name);
        $this->assertEquals('Updated Subject', $campaign->subject);
    }

    public function test_update_returns_404_for_other_agency(): void
    {
        $campaign = EmailCampaign::factory()->create(['agency_id' => Agency::factory()->create()->id]);
        $response = $this->actingAs($this->user)->put(route('email.campaigns.update', $campaign), [
            'name' => 'Updated', 'type' => 'newsletter', 'subject' => 'Updated',
        ]);
        $response->assertNotFound();
    }

    public function test_update_validates_name(): void
    {
        $campaign = EmailCampaign::factory()->create(['agency_id' => $this->agency->id]);
        $response = $this->actingAs($this->user)->put(route('email.campaigns.update', $campaign), [
            'name' => '', 'type' => 'newsletter', 'subject' => 'Updated',
        ]);
        $response->assertSessionHasErrors(['name']);
    }

    public function test_update_preserves_unchanged_fields(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $this->agency->id,
            'name' => 'Original',
            'subject' => 'Original Subject',
            'from_name' => 'From Name',
            'from_email' => 'from@test.com',
        ]);

        $response = $this->actingAs($this->user)->put(route('email.campaigns.update', $campaign), [
            'name' => 'Updated',
            'type' => 'newsletter',
            'subject' => 'Updated Subject',
        ]);

        $response->assertRedirect();
        $campaign->refresh();
        $this->assertEquals('Updated', $campaign->name);
        $this->assertEquals('Updated Subject', $campaign->subject);
        $this->assertEquals('From Name', $campaign->from_name);
        $this->assertEquals('from@test.com', $campaign->from_email);
    }

    // ── Destroy ────────────────────────────────────────────────────────

    public function test_destroy_requires_auth(): void
    {
        $campaign = EmailCampaign::factory()->create(['agency_id' => $this->agency->id]);
        $response = $this->delete(route('email.campaigns.destroy', $campaign));
        $response->assertRedirect(route('login'));
    }

    public function test_destroy_soft_deletes_campaign(): void
    {
        $campaign = EmailCampaign::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)
            ->delete(route('email.campaigns.destroy', $campaign));

        $response->assertRedirect(route('email.campaigns.index'));
        $this->assertSoftDeleted('email_campaigns', ['id' => $campaign->id]);
    }

    public function test_destroy_returns_404_for_other_agency(): void
    {
        $campaign = EmailCampaign::factory()->create(['agency_id' => Agency::factory()->create()->id]);
        $response = $this->actingAs($this->user)
            ->delete(route('email.campaigns.destroy', $campaign));
        $response->assertNotFound();
    }

    public function test_destroy_returns_404_for_nonexistent(): void
    {
        $response = $this->actingAs($this->user)
            ->delete(route('email.campaigns.destroy', 999));
        $response->assertNotFound();
    }

    // ── Send ───────────────────────────────────────────────────────────

    public function test_send_requires_auth(): void
    {
        $campaign = EmailCampaign::factory()->create(['agency_id' => $this->agency->id]);
        $response = $this->post(route('email.campaigns.send', $campaign));
        $response->assertRedirect(route('login'));
    }

    public function test_send_marks_campaign_as_sent(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'draft',
            'recipients_count' => 3,
        ]);
        EmailCampaignRecipient::factory()->count(3)->create([
            'email_campaign_id' => $campaign->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)->post(route('email.campaigns.send', $campaign));

        $response->assertRedirect(route('email.campaigns.show', $campaign));
        $campaign->refresh();
        $this->assertEquals('sent', $campaign->status);
        $this->assertEquals(3, $campaign->sent_count);
    }

    public function test_send_returns_404_for_other_agency(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => Agency::factory()->create()->id,
            'status' => 'draft',
        ]);
        $response = $this->actingAs($this->user)
            ->post(route('email.campaigns.send', $campaign));
        $response->assertNotFound();
    }

    public function test_cannot_send_already_sent_campaign(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'sent',
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('email.campaigns.send', $campaign));

        $response->assertSessionHasErrors();
    }

    // ── Add Clients ────────────────────────────────────────────────────

    public function test_add_clients_requires_auth(): void
    {
        $campaign = EmailCampaign::factory()->create(['agency_id' => $this->agency->id]);
        $response = $this->post(route('email.campaigns.add-clients', $campaign));
        $response->assertRedirect(route('login'));
    }

    public function test_add_clients_adds_agency_clients(): void
    {
        Client::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'status' => 'active',
        ]);
        $campaign = EmailCampaign::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)
            ->post(route('email.campaigns.add-clients', $campaign));

        $response->assertRedirect(route('email.campaigns.show', $campaign));
        $this->assertDatabaseHas('email_campaign_recipients', [
            'email_campaign_id' => $campaign->id,
        ]);
    }

    public function test_add_clients_returns_404_for_other_agency(): void
    {
        $campaign = EmailCampaign::factory()->create(['agency_id' => Agency::factory()->create()->id]);
        $response = $this->actingAs($this->user)
            ->post(route('email.campaigns.add-clients', $campaign));
        $response->assertNotFound();
    }

    public function test_add_clients_handles_no_clients(): void
    {
        $campaign = EmailCampaign::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)
            ->post(route('email.campaigns.add-clients', $campaign));

        $response->assertRedirect(route('email.campaigns.show', $campaign));
        $this->assertDatabaseMissing('email_campaign_recipients', [
            'email_campaign_id' => $campaign->id,
        ]);
    }
}
