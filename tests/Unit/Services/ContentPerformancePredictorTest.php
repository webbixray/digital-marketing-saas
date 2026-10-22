<?php

namespace Tests\Unit\Services;

use App\Services\ContentPerformancePredictor;
use Tests\TestCase;

class ContentPerformancePredictorTest extends TestCase
{
    private ContentPerformancePredictor $predictor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->predictor = app(ContentPerformancePredictor::class);
    }

    public function test_predict_returns_integer_between_0_and_100(): void
    {
        $score = $this->predictor->predict(
            'This is a great post about our amazing new product launch!',
            'twitter',
            ['product', 'launch'],
            ['🚀', '🎉']
        );

        $this->assertIsInt($score);
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    public function test_predict_gives_higher_score_for_optimal_content(): void
    {
        // Suboptimal: too few hashtags and no emojis for Instagram
        $lowScore = $this->predictor->predict(
            'Short text.',
            'instagram',
            ['one'],
            []
        );

        // Optimal: good length, ideal hashtags, ideal emojis for Instagram
        $highScore = $this->predictor->predict(
            'Exciting news! We just launched our new product line and we cannot wait for you to try it out. Check the link in bio for more details and exclusive early access offers!',
            'instagram',
            ['newproduct', 'launch', 'exclusive', 'earlyaccess', 'shopnow', 'instagood', 'marketing'],
            ['🎉', '🔥', '✨', '💯']
        );

        $this->assertGreaterThan($lowScore, $highScore);
        $this->assertGreaterThanOrEqual(0, $lowScore);
        $this->assertLessThanOrEqual(100, $highScore);
    }

    public function test_get_engagement_label_returns_correct_labels(): void
    {
        $this->assertEquals('high', $this->predictor->getEngagementLabel(85));
        $this->assertEquals('medium', $this->predictor->getEngagementLabel(65));
        $this->assertEquals('low', $this->predictor->getEngagementLabel(45));
        $this->assertEquals('very_low', $this->predictor->getEngagementLabel(15));
    }
}
