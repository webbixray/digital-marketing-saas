<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicTest extends TestCase
{
    public function test_landing_page_returns_200_without_auth(): void
    {
        $response = $this->get(route('public.landing'));
        $response->assertStatus(200);
    }

    public function test_features_page_returns_200_without_auth(): void
    {
        $response = $this->get('/features');
        $response->assertStatus(200);
    }

    public function test_pricing_page_returns_200_without_auth(): void
    {
        $response = $this->get(route('public.pricing'));
        $response->assertStatus(200);
    }

    public function test_blog_page_returns_200_without_auth(): void
    {
        $response = $this->get(route('public.blog'));
        $response->assertStatus(200);
    }

    public function test_contact_page_returns_200_without_auth(): void
    {
        $response = $this->get('/contact');
        $response->assertStatus(200);
    }

    public function test_contact_form_submits_without_auth(): void
    {
        $response = $this->post(route('public.contact.submit'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'message' => 'Hello, this is a test message.',
        ]);
        $response->assertStatus(302);
    }

    public function test_newsletter_subscription(): void
    {
        $response = $this->post(route('public.newsletter'), [
            'email' => 'subscriber@example.com',
        ]);
        $response->assertStatus(302);
    }

    public function test_terms_page_returns_200(): void
    {
        $response = $this->get(route('public.terms'));
        $response->assertStatus(200);
    }

    public function test_welcome_page_returns_200(): void
    {
        $response = $this->get(route('public.welcome'));
        $response->assertStatus(200);
    }
}
