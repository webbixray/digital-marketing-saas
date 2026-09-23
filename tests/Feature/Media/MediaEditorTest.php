<?php

namespace Tests\Feature\Media;

use App\Models\Agency;
use App\Models\MediaAsset;
use App\Models\MediaFolder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaEditorTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $user;

    private Agency $otherAgency;

    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create(['subscription_plan' => 'starter']);
        $this->user = User::factory()->create(['agency_id' => $this->agency->id]);
        $this->otherAgency = Agency::factory()->create(['subscription_plan' => 'pro']);
        $this->otherUser = User::factory()->create(['agency_id' => $this->otherAgency->id]);
    }

    public function test_edit_page_loads_for_image(): void
    {
        $asset = MediaAsset::factory()->create([
            'agency_id' => $this->agency->id,
            'user_id' => $this->user->id,
            'file_type' => 'image',
        ]);

        $response = $this->actingAs($this->user)->get(route('media.edit', $asset));

        $response->assertOk();
        $response->assertViewIs('media.edit');
        $response->assertViewHas('asset');
        $response->assertViewHas('quota');
        $response->assertViewHas('folders');
    }

    public function test_edit_redirects_for_non_image(): void
    {
        $asset = MediaAsset::factory()->create([
            'agency_id' => $this->agency->id,
            'user_id' => $this->user->id,
            'file_type' => 'video',
        ]);

        $response = $this->actingAs($this->user)->get(route('media.edit', $asset));

        $response->assertRedirect(route('media.show', $asset));
        $response->assertSessionHas('error');
    }

    public function test_update_saves_edited_image(): void
    {
        $asset = MediaAsset::factory()->create([
            'agency_id' => $this->agency->id,
            'user_id' => $this->user->id,
            'file_type' => 'image',
            'mime_type' => 'image/jpeg',
            'file_size' => 500000,
        ]);

        // Create a 1x1 pixel JPEG as base64
        $imageData = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFAABAAAAAAAAAAAAAAAAAAAACf/EABQQAQAAAAAAAAAAAAAAAAAAAAD/xAAUAQEAAAAAAAAAAAAAAAAAAAAA/8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAwDAQACEQMRAD8AKwA//9k=';

        $response = $this->actingAs($this->user)
            ->putJson(route('media.update', $asset), [
                'image_data' => $imageData,
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    public function test_quota_check_returns_valid_data(): void
    {
        MediaAsset::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'file_size' => 10485760, // 10 MB each
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('media.quota'));

        $response->assertOk();
        $response->assertJsonStructure([
            'plan_limit_bytes',
            'used_bytes',
            'remaining_bytes',
            'used_percentage',
        ]);
    }

    public function test_update_rejects_invalid_base64(): void
    {
        $asset = MediaAsset::factory()->create([
            'agency_id' => $this->agency->id,
            'user_id' => $this->user->id,
            'file_type' => 'image',
            'mime_type' => 'image/jpeg',
            'file_size' => 500000,
        ]);

        // Invalid base64 data should be rejected
        $response = $this->actingAs($this->user)
            ->putJson(route('media.update', $asset), [
                'image_data' => 'data:image/jpeg;base64,invalid!!!',
            ]);

        $response->assertStatus(422);
    }

    public function test_folder_crud_create(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('media-folders.store'), [
                'name' => 'Test Folder',
            ]);

        $response->assertCreated();
        $response->assertJsonStructure(['success', 'folder']);
        $this->assertDatabaseHas('media_folders', [
            'name' => 'Test Folder',
            'agency_id' => $this->agency->id,
        ]);
    }

    public function test_folder_crud_rename(): void
    {
        $folder = MediaFolder::create([
            'agency_id' => $this->agency->id,
            'name' => 'Old Name',
            'slug' => 'old-name-abc1',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson(route('media-folders.update', $folder), [
                'name' => 'New Name',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('media_folders', [
            'id' => $folder->id,
            'name' => 'New Name',
        ]);
    }

    public function test_folder_crud_delete(): void
    {
        $folder = MediaFolder::create([
            'agency_id' => $this->agency->id,
            'name' => 'Delete Me',
            'slug' => 'delete-me-xyz1',
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson(route('media-folders.destroy', $folder));

        $response->assertOk();
        $this->assertSoftDeleted('media_folders', [
            'id' => $folder->id,
        ]);
    }

    public function test_auth_required_for_editor(): void
    {
        $asset = MediaAsset::factory()->create([
            'agency_id' => $this->agency->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->get(route('media.edit', $asset));
        $response->assertRedirect(route('login'));
    }

    public function test_cross_agency_access_denied(): void
    {
        $asset = MediaAsset::factory()->create([
            'agency_id' => $this->agency->id,
            'user_id' => $this->user->id,
            'file_type' => 'image',
        ]);

        // Other user from different agency tries to access
        $response = $this->actingAs($this->otherUser)
            ->get(route('media.edit', $asset));

        $response->assertForbidden();

        // Also test update
        $response = $this->actingAs($this->otherUser)
            ->putJson(route('media.update', $asset), [
                'image_data' => 'data:image/jpeg;base64,abc123',
            ]);

        $response->assertForbidden();
    }

    public function test_cross_agency_folder_access_denied(): void
    {
        $folder = MediaFolder::create([
            'agency_id' => $this->agency->id,
            'name' => 'Private Folder',
            'slug' => 'private-folder',
        ]);

        // Other user tries to access folder from different agency
        $response = $this->actingAs($this->otherUser)
            ->putJson(route('media-folders.update', $folder), [
                'name' => 'Hacked',
            ]);

        $response->assertForbidden();

        $response = $this->actingAs($this->otherUser)
            ->deleteJson(route('media-folders.destroy', $folder));

        $response->assertForbidden();
    }

    public function test_folder_cannot_be_created_without_name(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('media-folders.store'), [
                'name' => '',
            ]);

        $response->assertStatus(422);
    }

    public function test_folders_index_returns_only_own_agency(): void
    {
        MediaFolder::create([
            'agency_id' => $this->agency->id,
            'name' => 'My Folder',
            'slug' => 'my-folder-abc1',
        ]);

        MediaFolder::create([
            'agency_id' => $this->otherAgency->id,
            'name' => 'Other Folder',
            'slug' => 'other-folder-def2',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('media-folders.index'));

        $response->assertOk();
        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertEquals('My Folder', $data[0]['name']);
    }
}
