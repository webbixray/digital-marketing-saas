<?php

namespace App\Services\Media;

use App\Models\Agency;
use App\Services\AI\Gateway\AiGateway;
use App\Services\AI\Gateway\AiRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AiVideoGenerationService
{
    /**
     * Available style presets for video generation.
     */
    private const STYLE_PRESETS = [
        'photorealistic' => [
            'name' => 'Photorealistic',
            'description' => 'High-quality photorealistic videos with natural lighting and details',
            'prompt_suffix' => 'photorealistic, highly detailed, natural lighting, 8k resolution, cinematic',
        ],
        'cinematic' => [
            'name' => 'Cinematic',
            'description' => 'Cinematic video style with dramatic lighting and composition',
            'prompt_suffix' => 'cinematic, dramatic lighting, professional composition, film grain, widescreen',
        ],
        'animated' => [
            'name' => 'Animated',
            'description' => 'Animated video style with smooth motion and vibrant colors',
            'prompt_suffix' => 'animated, smooth motion, vibrant colors, cartoon style, family-friendly',
        ],
        'minimalist' => [
            'name' => 'Minimalist',
            'description' => 'Clean, minimal video with simple elements and clean transitions',
            'prompt_suffix' => 'minimalist, clean, simple, elegant, negative space, smooth transitions',
        ],
        'corporate' => [
            'name' => 'Corporate',
            'description' => 'Professional business and corporate video style',
            'prompt_suffix' => 'corporate style, professional, business, clean, modern, infographic',
        ],
        'social-media' => [
            'name' => 'Social Media',
            'description' => 'Engaging videos optimized for social media platforms',
            'prompt_suffix' => 'social media optimized, eye-catching, engaging, vibrant, vertical format, hook-first',
        ],
        'product' => [
            'name' => 'Product',
            'description' => 'Product showcase video with clean backgrounds and smooth rotations',
            'prompt_suffix' => 'product showcase, clean background, 360-degree view, studio lighting, professional',
        ],
        'abstract' => [
            'name' => 'Abstract',
            'description' => 'Abstract artistic video compositions with creative visuals',
            'prompt_suffix' => 'abstract art, creative, unique composition, artistic expression, experimental',
        ],
    ];

    /**
     * Available video durations.
     */
    private const DURATION_PRESETS = [
        '3' => ['seconds' => 3, 'label' => '3 Seconds'],
        '5' => ['seconds' => 5, 'label' => '5 Seconds'],
        '10' => ['seconds' => 10, 'label' => '10 Seconds'],
        '15' => ['seconds' => 15, 'label' => '15 Seconds'],
        '30' => ['seconds' => 30, 'label' => '30 Seconds'],
    ];

    /**
     * Available video resolutions.
     */
    private const RESOLUTION_PRESETS = [
        '1024x1024' => ['width' => 1024, 'height' => 1024, 'label' => 'Square (1024x1024)'],
        '1024x1792' => ['width' => 1024, 'height' => 1792, 'label' => 'Portrait (1024x1792)'],
        '1792x1024' => ['width' => 1792, 'height' => 1024, 'label' => 'Landscape (1792x1024)'],
        '512x512' => ['width' => 512, 'height' => 512, 'label' => 'Small Square (512x512)'],
        '768x768' => ['width' => 768, 'height' => 768, 'label' => 'Medium Square (768x768)'],
    ];

    public function __construct(
        private readonly AiGateway $gateway,
    ) {}

    /**
     * Generate a video using AI based on prompt, style, duration, and resolution.
     */
    /**
     * @return array<string, mixed>
     */
    public function generateVideo(string $prompt, string $style, string $duration, string $resolution): array
    {
        $styleConfig = self::STYLE_PRESETS[$style] ?? self::STYLE_PRESETS['photorealistic'];
        $durationConfig = self::DURATION_PRESETS[$duration] ?? self::DURATION_PRESETS['5'];
        $resolutionConfig = self::RESOLUTION_PRESETS[$resolution] ?? self::RESOLUTION_PRESETS['1024x1024'];

        $enhancedPrompt = $this->buildEnhancedPrompt($prompt, $styleConfig);
        $videoData = $this->generateWithAi($enhancedPrompt, $durationConfig, $resolutionConfig);

        return [
            'success' => true,
            'prompt' => $prompt,
            'enhanced_prompt' => $enhancedPrompt,
            'style' => $style,
            'duration' => $duration,
            'resolution' => $resolution,
            'width' => $resolutionConfig['width'],
            'height' => $resolutionConfig['height'],
            'video_url' => $videoData['url'] ?? null,
            'video_data' => $videoData['data'] ?? null,
            'model' => $videoData['model'] ?? 'sora',
            'duration_seconds' => $durationConfig['seconds'],
        ];
    }

    /**
     * Get available style presets.
     *
     * @return array<int, array<string, string>>
     */
    public function getAvailableStyles(): array
    {
        $styles = [];

        foreach (self::STYLE_PRESETS as $key => $config) {
            $styles[] = [
                'key' => $key,
                'name' => $config['name'],
                'description' => $config['description'],
            ];
        }

        return $styles;
    }

    /**
     * Get available duration presets.
     *
     * @return array<int, array<string, int|string>>
     */
    public function getAvailableDurations(): array
    {
        $durations = [];

        foreach (self::DURATION_PRESETS as $key => $config) {
            $durations[] = [
                'key' => (string) $key,
                'label' => $config['label'],
                'seconds' => $config['seconds'],
            ];
        }

        return $durations;
    }

    /**
     * Get available resolution presets.
     *
     * @return array<int, array<string, int|string>>
     */
    public function getAvailableResolutions(): array
    {
        $resolutions = [];

        foreach (self::RESOLUTION_PRESETS as $key => $config) {
            $resolutions[] = [
                'key' => $key,
                'label' => $config['label'],
                'width' => $config['width'],
                'height' => $config['height'],
            ];
        }

        return $resolutions;
    }

    /**
     * Build enhanced prompt with style suffix.
     */
    /**
     * @param  array<string, mixed>  $styleConfig
     */
    private function buildEnhancedPrompt(string $prompt, array $styleConfig): string
    {
        return trim($prompt).', '.$styleConfig['prompt_suffix'];
    }

    /**
     * Generate video with AI provider.
     */
    private function generateWithAi(string $enhancedPrompt, array $durationConfig, array $resolutionConfig): array
    {
        $request = new AiRequest(
            prompt: "Generate a high-quality video based on this description: {$enhancedPrompt}. Duration: {$durationConfig['seconds']} seconds. Resolution: {$resolutionConfig['width']}x{$resolutionConfig['height']} pixels.",
            model: 'sora',
            task: 'creative',
            maxTokens: 4096,
        )->withProvider('openai_video');

        try {
            $response = $this->gateway->send(
                $request,
                $this->getSystemAgency(),
            );

            return [
                'url' => $response->content,
                'data' => null,
                'model' => 'sora',
            ];
        } catch (\Exception $e) {
            Log::error('AI video generation failed', [
                'error' => $e->getMessage(),
                'prompt' => $enhancedPrompt,
            ]);

            // Return placeholder for development/testing
            return [
                'url' => "https://example.com/placeholder-video.mp4?width={$resolutionConfig['width']}&height={$resolutionConfig['height']}&duration={$durationConfig['seconds']}&text=" . urlencode("Video Placeholder: {$enhancedPrompt}"),
                'data' => null,
                'model' => 'placeholder',
            ];
        }
    }

    /**
     * Get system agency for gateway requests.
     */
    private function getSystemAgency(): Agency
    {
        return Agency::first();
    }
}
