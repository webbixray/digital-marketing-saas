<?php

namespace Tests\Unit\Services;

use App\Services\MediaUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MediaUploadServiceTest extends TestCase
{
    use RefreshDatabase;

    private MediaUploadService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MediaUploadService;
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

    private function callPrivateMethod($object, string $method, array $args)
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $args);
    }
}
