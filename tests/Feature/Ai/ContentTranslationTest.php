<?php

namespace Tests\Feature\AI;

use App\Models\Agency;
use App\Models\Campaign;
use App\Models\SocialPost;
use App\Models\User;
use App\Services\AI\ContentTranslationService;
use App\Services\AI\Gateway\AiGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentTranslationTest extends TestCase
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

    public function test_translate_content_returns_translated_result(): void
    {
        $service = new ContentTranslationService(new AiGateway());

        $result = $service->translateContent(
            content: 'Hello world, this is a test.',
            sourceLang: 'en',
            targetLang: 'es',
        );

        $this->assertArrayHasKey('translated', $result);
        $this->assertArrayHasKey('source_lang', $result);
        $this->assertArrayHasKey('target_lang', $result);
        $this->assertArrayHasKey('quality_score', $result);
        $this->assertEquals('en', $result['source_lang']);
        $this->assertEquals('es', $result['target_lang']);
        $this->assertIsFloat($result['quality_score']);
    }

    public function test_translate_social_post_returns_content_and_hashtags(): void
    {
        $service = new ContentTranslationService(new AiGateway());

        $post = [
            'content' => 'Check out our latest product!',
            'hashtags' => ['#product', '#new'],
            'source_lang' => 'en',
        ];

        $result = $service->translateSocialPost($post, 'fr');

        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('hashtags', $result);
        $this->assertArrayHasKey('source_lang', $result);
        $this->assertArrayHasKey('target_lang', $result);
        $this->assertEquals('en', $result['source_lang']);
        $this->assertEquals('fr', $result['target_lang']);
        $this->assertIsArray($result['hashtags']);
    }

    public function test_translate_campaign_dispatches_batch_job(): void
    {
        $campaign = Campaign::factory()->create([
            'agency_id' => $this->agency->id,
        ]);

        $post = SocialPost::factory()->create([
            'agency_id' => $this->agency->id,
            'content' => 'Campaign post content',
        ]);

        $campaign->posts()->attach($post->id);

        $response = $this->actingAs($this->user)->post(route('translate.campaign', $campaign->id), [
            'target_lang' => 'de',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    public function test_batch_translation_processes_all_items(): void
    {
        $service = new ContentTranslationService(new AiGateway());

        $items = [
            ['content' => 'First item', 'context' => 'general'],
            ['content' => 'Second item', 'context' => 'general'],
            ['content' => 'Third item', 'context' => 'general'],
        ];

        $result = $service->translateBatch($items, 'en', 'es');

        $this->assertArrayHasKey('results', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('total_processed', $result);
        $this->assertArrayHasKey('total_errors', $result);
        $this->assertEquals(3, $result['total_processed'] + $result['total_errors']);
    }

    public function test_quality_score_returns_valid_range(): void
    {
        $service = new ContentTranslationService(new AiGateway());

        $original = 'This is a sample text for translation quality testing.';
        $translated = 'Este es un texto de ejemplo para probar la calidad de la traducción.';

        $score = $service->getTranslationQuality($original, $translated, 'en', 'es');

        $this->assertIsFloat($score);
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    public function test_translation_requires_authentication(): void
    {
        $response = $this->post(route('translate.translate'), [
            'content' => 'Test content',
            'source_lang' => 'en',
            'target_lang' => 'es',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_translation_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('translate.translate'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['content', 'target_lang']);
    }

    public function test_translation_validates_language_support(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('translate.translate'), [
            'content' => 'Test content',
            'source_lang' => 'en',
            'target_lang' => 'xx',
        ]);

        $response->assertStatus(422);
    }

    public function test_supported_languages_returns_language_list(): void
    {
        $response = $this->actingAs($this->user)->get(route('translate.languages'));

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'en',
                'es',
                'fr',
            ],
        ]);
    }

    public function test_detect_language_returns_detected_language(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('translate.detect'), [
            'text' => 'This is clearly English text for language detection.',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'code',
                'name',
                'confidence',
            ],
        ]);
    }
}
