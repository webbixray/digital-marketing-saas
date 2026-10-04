<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreSocialPostRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_social_post_request_has_rules(): void
    {
        $request = new StoreSocialPostRequest;
        $this->assertArrayHasKey('content', $request->rules());
    }

    public function test_store_social_post_request_has_messages(): void
    {
        $request = new StoreSocialPostRequest;
        $this->assertIsArray($request->messages());
    }
}
