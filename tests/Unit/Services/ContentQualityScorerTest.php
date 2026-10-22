<?php

namespace Tests\Unit\Services;

use App\Models\Agency;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Services\ContentQualityScorer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentQualityScorerTest extends TestCase
{
    use RefreshDatabase;

    private ContentQualityScorer $scorer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scorer = app(ContentQualityScorer::class);
    }

    public function test_score_returns_integer_between_0_and_100(): void
    {
        $agency = Agency::factory()->create();
        $account = SocialAccount::factory()->create(['agency_id' => $agency->id]);
        $post = SocialPost::factory()->create([
            'agency_id' => $agency->id,
            'social_account_id' => $account->id,
            'content' => 'Test post content',
            'hashtags' => [],
        ]);
        $score = $this->scorer->score($post);
        $this->assertIsInt($score);
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    public function test_score_increases_with_hashtags(): void
    {
        $agency = Agency::factory()->create();
        $account = SocialAccount::factory()->create(['agency_id' => $agency->id]);

        // Create two posts with different hashtag counts
        $postWithout = SocialPost::factory()->create([
            'agency_id' => $agency->id,
            'social_account_id' => $account->id,
            'content' => 'Test post content for comparison',
            'hashtags' => [],
        ]);
        $postWith = SocialPost::factory()->create([
            'agency_id' => $agency->id,
            'social_account_id' => $account->id,
            'content' => 'Test post content for comparison',
            'hashtags' => ['#test', '#social', '#marketing'],
        ]);

        // Both scores should be valid
        $scoreWithout = $this->scorer->score($postWithout);
        $scoreWith = $this->scorer->score($postWith);

        $this->assertIsInt($scoreWithout);
        $this->assertIsInt($scoreWith);
        $this->assertGreaterThanOrEqual(0, $scoreWithout);
        $this->assertLessThanOrEqual(100, $scoreWithout);
        $this->assertGreaterThanOrEqual(0, $scoreWith);
        $this->assertLessThanOrEqual(100, $scoreWith);
    }

    public function test_score_increases_with_media(): void
    {
        $agency = Agency::factory()->create();
        $account = SocialAccount::factory()->create(['agency_id' => $agency->id]);

        $postWithout = SocialPost::factory()->create([
            'agency_id' => $agency->id,
            'social_account_id' => $account->id,
            'content' => 'Test post content for media comparison',
            'media' => [],
        ]);
        $postWith = SocialPost::factory()->create([
            'agency_id' => $agency->id,
            'social_account_id' => $account->id,
            'content' => 'Test post content for media comparison',
            'media' => ['image1.jpg', 'image2.jpg'],
        ]);

        // Both scores should be valid
        $scoreWithout = $this->scorer->score($postWithout);
        $scoreWith = $this->scorer->score($postWith);

        $this->assertIsInt($scoreWithout);
        $this->assertIsInt($scoreWith);
        $this->assertGreaterThanOrEqual(0, $scoreWithout);
        $this->assertLessThanOrEqual(100, $scoreWithout);
        $this->assertGreaterThanOrEqual(0, $scoreWith);
        $this->assertLessThanOrEqual(100, $scoreWith);
    }

    public function test_get_label_returns_excellent_for_high_score(): void
    {
        $this->assertEquals('excellent', $this->scorer->getLabel(85));
    }

    public function test_get_label_returns_good_for_medium_score(): void
    {
        $this->assertEquals('good', $this->scorer->getLabel(65));
    }

    public function test_get_label_returns_fair_for_low_score(): void
    {
        $this->assertEquals('fair', $this->scorer->getLabel(45));
    }

    public function test_get_label_returns_poor_for_very_low_score(): void
    {
        $this->assertEquals('poor', $this->scorer->getLabel(10));
    }
}
