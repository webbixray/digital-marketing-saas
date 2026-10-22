<?php

namespace Tests\Unit\Services;

use App\Models\Agency;
use App\Models\MediaAsset;
use App\Models\User;
use App\Services\MediaUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaUploadServiceTest extends TestCase
{
    use RefreshDatabase;

    private MediaUploadService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MediaUploadService;
        Storage::fake('public');
    }

    public function test_it_detects_image_type(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');
        $this->assertEquals('image', $this->callPrivateMethod($this->service, 'detectFileType', [$file->getMimeType()]));
    }

    public function test_it_detects_video_type(): void
    {
        $file = UploadedFile::fake()->create('test.mp4', 1024, 'video/mp4');
        $this->assertEquals('video', $this->callPrivateMethod($this->service, 'detectFileType', [$file->getMimeType()]));
    }

    public function test_it_detects_document_type(): void
    {
        $file = UploadedFile::fake()->create('test.pdf', 1024, 'application/pdf');
        $this->assertEquals('document', $this->callPrivateMethod($this->service, 'detectFileType', [$file->getMimeType()]));
    }

    public function test_it_uploads_valid_image_and_records_dimensions(): void
    {
        Storage::fake('public');

        $agency = Agency::factory()->create(['name' => 'Test Agency', 'slug' => 'test-agency']);
        $user = User::factory()->create(['agency_id' => $agency->id, 'role' => 'owner']);

        $file = UploadedFile::fake()->image('photo.jpg')->size(50);

        $asset = $this->service->upload($file, $agency, $user->id, ['folder' => 'test']);

        $this->assertInstanceOf(MediaAsset::class, $asset);
        $this->assertEquals($agency->id, $asset->agency_id);
        $this->assertEquals($user->id, $asset->user_id);
        $this->assertEquals('image', $asset->file_type);
        $this->assertEquals('image/jpeg', $asset->mime_type);
        $this->assertNotNull($asset->width);
        $this->assertNotNull($asset->height);
        $this->assertGreaterThan(0, $asset->file_size);
        $this->assertTrue(Storage::disk('public')->exists($asset->file_path));
    }

    public function test_it_uploads_valid_png_image(): void
    {
        Storage::fake('public');

        $agency = Agency::factory()->create(['name' => 'Test Agency', 'slug' => 'test-agency']);
        $user = User::factory()->create(['agency_id' => $agency->id, 'role' => 'owner']);

        $file = UploadedFile::fake()->image('photo.png');

        $asset = $this->service->upload($file, $agency, $user->id);

        $this->assertInstanceOf(MediaAsset::class, $asset);
        $this->assertEquals('image/png', $asset->mime_type);
        $this->assertEquals('image', $asset->file_type);
    }

    public function test_it_rejects_executable_file_types(): void
    {
        Storage::fake('public');

        $agency = Agency::factory()->create(['name' => 'Test Agency', 'slug' => 'test-agency']);
        $user = User::factory()->create(['agency_id' => $agency->id, 'role' => 'owner']);

        $file = UploadedFile::fake()->create('malicious.exe', 1024, 'application/x-msdownload');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File type');

        $this->service->upload($file, $agency, $user->id);
    }

    public function test_it_rejects_php_file_types(): void
    {
        Storage::fake('public');

        $agency = Agency::factory()->create(['name' => 'Test Agency', 'slug' => 'test-agency']);
        $user = User::factory()->create(['agency_id' => $agency->id, 'role' => 'owner']);

        $file = UploadedFile::fake()->create('shell.php', 1024, 'application/x-httpd-php');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File type');

        $this->service->upload($file, $agency, $user->id);
    }

    public function test_it_rejects_zero_byte_files(): void
    {
        Storage::fake('public');

        $agency = Agency::factory()->create(['name' => 'Test Agency', 'slug' => 'test-agency']);
        $user = User::factory()->create(['agency_id' => $agency->id, 'role' => 'owner']);

        $file = UploadedFile::fake()->create('empty.jpg', 0, 'image/jpeg');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File type');

        $this->service->upload($file, $agency, $user->id);
    }

    private function callPrivateMethod($object, string $method, array $args)
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $args);
    }
}
