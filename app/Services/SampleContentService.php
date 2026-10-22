<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\SocialPost;

class SampleContentService
{
    /**
     * Get sample posts for onboarding demo.
     */
    public function getSamplePosts(string $platform = 'instagram'): array
    {
        $samples = [
            'instagram' => [
                [
                    'content' => '🚀 Exciting news! We just launched our new product line. Check it out and let us know what you think! #ProductLaunch #Innovation #NewBeginnings',
                    'platform' => 'instagram',
                    'media' => ['type' => 'image', 'url' => 'https://via.placeholder.com/1080x1080/6366f1/white?text=Launch+Day'],
                ],
                [
                    'content' => '💡 Did you know? 73% of consumers prefer brands that personalize their experience. Here\'s how we can help your brand stand out! #MarketingTips #Personalization',
                    'platform' => 'instagram',
                    'media' => ['type' => 'carousel', 'url' => 'https://via.placeholder.com/1080x1080/10b981/white?text=Marketing+Tips'],
                ],
                [
                    'content' => '✨ Behind the scenes at our latest photoshoot. Great content takes teamwork! #BTS #ContentCreation #TeamWork',
                    'platform' => 'instagram',
                    'media' => ['type' => 'image', 'url' => 'https://via.placeholder.com/1080x1080/f59e0b/white?text=BTS+Photoshoot'],
                ],
            ],
            'twitter' => [
                [
                    'content' => 'Just dropped our latest blog post: "10 Marketing Trends You Can\'t Ignore in 2025" 🚀 Read it here: [link] #Marketing #Trends2025',
                    'platform' => 'twitter',
                ],
                [
                    'content' => 'The secret to great marketing? Consistency + Authenticity. 🎯 What\'s your top marketing tip? Let us know below! 👇',
                    'platform' => 'twitter',
                ],
            ],
            'facebook' => [
                [
                    'content' => '🎉 We\'re thrilled to announce that we\'ve been named a Top Marketing Agency for 2024! Thank you to our amazing clients and team! #Award #MarketingAgency',
                    'platform' => 'facebook',
                    'media' => ['type' => 'image', 'url' => 'https://via.placeholder.com/1200x630/6366f1/white?text=Award+Announcement'],
                ],
            ],
            'linkedin' => [
                [
                    'content' => 'We\'re hiring! 🚀 Looking for passionate marketing professionals to join our growing team. Check out our careers page for open positions. #Hiring #MarketingJobs',
                    'platform' => 'linkedin',
                ],
            ],
        ];

        return $samples[$platform] ?? $samples['instagram'];
    }

    /**
     * Get content ideas based on industry.
     */
    public function getContentIdeas(string $industry = 'general'): array
    {
        $ideas = [
            'general' => [
                'Share a behind-the-scenes look at your team',
                'Post a customer testimonial or success story',
                'Share an industry tip or how-to guide',
                'Ask your audience a question',
                'Share a motivational quote with your brand colors',
            ],
            'ecommerce' => [
                'Showcase a new product with lifestyle imagery',
                'Share a customer unboxing experience',
                'Create a "How It\'s Made" post',
                'Post a limited-time offer or flash sale',
                'Share user-generated content from customers',
            ],
            'saas' => [
                'Share a product tip or hidden feature',
                'Post a customer success story',
                'Create an infographic about your industry',
                'Share company culture and team highlights',
                'Post about upcoming features or updates',
            ],
            'restaurant' => [
                'Share a mouth-watering photo of your signature dish',
                'Post a "Meet the Chef" feature',
                'Share a behind-the-scenes kitchen moment',
                'Create a poll: "What should we add to the menu?"',
                'Post about local ingredients or suppliers',
            ],
        ];

        return $ideas[$industry] ?? $ideas['general'];
    }

    /**
     * Get hashtag suggestions for a platform.
     */
    public function getHashtagSuggestions(string $platform = 'instagram', string $industry = 'marketing'): array
    {
        $hashtags = [
            'instagram' => [
                'marketing' => ['#marketing', '#digitalmarketing', '#socialmedia', '#contentmarketing', '#branding', '#marketingstrategy', '#growthhacking', '#marketingtips'],
                'general' => ['#business', '#entrepreneur', '#success', '#motivation', '#innovation', '#growth'],
            ],
            'twitter' => [
                'marketing' => ['#Marketing', '#SocialMedia', '#ContentMarketing', '#DigitalMarketing', '#GrowthHacking'],
                'general' => ['#Business', '#Startup', '#Entrepreneur', '#Success'],
            ],
            'linkedin' => [
                'marketing' => ['#Marketing', '#DigitalMarketing', '#ContentStrategy', '#B2BMarketing', '#LeadGeneration'],
                'general' => ['#Business', '#Leadership', '#Innovation', '#Growth'],
            ],
        ];

        return $hashtags[$platform][$industry] ?? $hashtags[$platform]['general'] ?? [];
    }

    /**
     * Create sample posts for a new agency.
     */
    public function createSamplePosts(Agency $agency, string $platform = 'instagram'): void
    {
        $samples = $this->getSamplePosts($platform);

        foreach ($samples as $index => $sample) {
            SocialPost::create([
                'agency_id' => $agency->id,
                'platform' => $sample['platform'],
                'content' => $sample['content'],
                'media' => $sample['media'] ?? null,
                'status' => 'draft',
                'quality_score' => rand(70, 95),
                'created_at' => now()->subDays($index),
            ]);
        }
    }
}
