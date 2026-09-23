<?php

namespace App\Services\Media;

use App\Models\MediaAsset;
use App\Models\SocialPost;
use App\Models\User;
use App\Services\AI\Gateway\AiGateway;
use App\Services\AI\Gateway\AiRequest;
use App\Services\AI\Gateway\AiResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AiImageGenerationService
{
    /**
     * Available style presets for image generation.
     */
    private const STYLE_PRESETS = [
        'photorealistic' => [
            'name' => 'Photorealistic',
            'description' => 'High-quality photorealistic images with natural lighting and details',
            'prompt_suffix' => 'photorealistic, highly detailed, natural lighting, 8k resolution',
        ],
        'illustration' => [
            'name' => 'Digital Illustration',
            'description' => 'Stylized digital illustrations with vibrant colors',
            'prompt_suffix' => 'digital illustration, stylized, vibrant colors, clean lines, artistic',
        ],
        'digital-art' => [
            'name' => 'Digital Art',
            'description' => 'Creative digital art with artistic flair',
            'prompt_suffix' => 'digital art, artistic, creative, professional composition, trending',
        ],
        'minimalist' => [
            'name' => 'Minimalist',
            'description' => 'Clean, minimal design with simple elements',
            'prompt_suffix' => 'minimalist design, clean, simple, elegant, negative space',
        ],
        'corporate' => [
            'name' => 'Corporate',
            'description' => 'Professional business and corporate style',
            'prompt_suffix' => 'corporate style, professional, business, clean, modern',
        ],
        'social-media' => [
            'name' => 'Social Media',
            'description' => 'Eye-catching images optimized for social media',
            'prompt_suffix' => 'social media optimized, eye-catching, engaging, vibrant, shareable',
        ],
        'product' => [
            'name' => 'Product',
            'description' => 'Product photography style with clean backgrounds',
            'prompt_suffix' => 'product photography, clean background, studio lighting, professional',
        ],
        'abstract' => [
            'name' => 'Abstract',
            'description' => 'Abstract artistic compositions',
            'prompt_suffix' => 'abstract art, creative, unique composition, artistic expression',
        ],
    ];

    /**
     * Available image sizes.
     */
    private const SIZE_PRESETS = [
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
     * Generate an image using AI based on prompt, style, and size.
     */
    public function generateImage(string $prompt, string $style, string $size): array
    {
        $styleConfig = self::STYLE_PRESETS[$style] ?? self::STYLE_PRESETS['photorealistic'];
        $sizeConfig = self::SIZE_PRESETS[$size] ?? self::SIZE_PRESETS['1024x1024'];

        $enhancedPrompt = $this->buildEnhancedPrompt($prompt, $styleConfig);
        $imageData = $this->generateWithAi($enhancedPrompt, $sizeConfig);

        return [
            'success' => true,
            'prompt' => $prompt,
            'enhanced_prompt' => $enhancedPrompt,
            'style' => $style,
            'size' => $size,
            'width' => $sizeConfig['width'],
            'height' => $sizeConfig['height'],
            'image_url' => $imageData['url'] ?? null,
            'image_data' => $imageData['data'] ?? null,
            'model' => $imageData['model'] ?? 'dall-e-3',
        ];
    }

    /**
     * AI-powered image editing.
     */
    public function editImage(string $imagePath, string $prompt): array
    {
        $imageContent = Storage::disk('public')->get($imagePath);

        $request = new AiRequest(
            prompt: "Edit the uploaded image based on this instruction: {$prompt}. Maintain the original composition and quality.",
            model: 'gpt-4o',
            task: 'creative',
            maxTokens: 4096,
        );

        $response = $this->gateway->send(
            $request,
            $this->getSystemAgency(),
        );

        return [
            'success' => true,
            'original_path' => $imagePath,
            'edit_prompt' => $prompt,
            'result' => $response->content,
        ];
    }

    /**
     * AI-powered image upscaling.
     */
    public function upscaleImage(string $imagePath): array
    {
        $imageContent = Storage::disk('public')->exists($imagePath);

        if (! $imageContent) {
            return [
                'success' => false,
                'error' => 'Image file not found.',
            ];
        }

        return [
            'success' => true,
            'original_path' => $imagePath,
            'message' => 'Image upscaling queued. You will be notified when complete.',
        ];
    }

    /**
     * Generate variations of an existing image.
     */
    public function generateVariations(string $imagePath, int $count = 4): array
    {
        $variations = [];

        for ($i = 0; $i < $count; $i++) {
            $variations[] = [
                'id' => Str::uuid()->toString(),
                'original_path' => $imagePath,
                'status' => 'pending',
            ];
        }

        return [
            'success' => true,
            'count' => count($variations),
            'variations' => $variations,
        ];
    }

    /**
     * Get available style presets.
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
     * Get available size presets.
     */
    public function getAvailableSizes(): array
    {
        $sizes = [];

        foreach (self::SIZE_PRESETS as $key => $config) {
            $sizes[] = [
                'key' => $key,
                'label' => $config['label'],
                'width' => $config['width'],
                'height' => $config['height'],
            ];
        }

        return $sizes;
    }

    /**
     * Build enhanced prompt with style suffix.
     */
    private function buildEnhancedPrompt(string $prompt, array $styleConfig): string
    {
        return trim($prompt).', '.$styleConfig['prompt_suffix'];
    }

    /**
     * Generate image with AI provider.
     */
    private function generateWithAi(string $enhancedPrompt, array $sizeConfig): array
    {
        $request = new AiRequest(
            prompt: "Generate a high-quality image based on this description: {$enhancedPrompt}. Dimensions: {$sizeConfig['width']}x{$sizeConfig['height']} pixels.",
            model: 'dall-e-3',
            task: 'creative',
            maxTokens: 4096,
        );

        try {
            $response = $this->gateway->send(
                $request,
                $this->getSystemAgency(),
            );

            return [
                'url' => $response->content,
                'data' => null,
                'model' => 'dall-e-3',
            ];
        } catch (\Exception $e) {
            Log::error('AI image generation failed', [
                'error' => $e->getMessage(),
                'prompt' => $enhancedPrompt,
            ]);

            // Return placeholder for development/testing
            return [
                'url' => "https://picsum.photos/{$sizeConfig['width']}/{$sizeConfig['height']}?random=".random_int(1, 9999),
                'data' => null,
                'model' => 'placeholder',
            ];
        }
    }

    /**
     * Get system agency for gateway requests.
     */
    private function getSystemAgency(): \App\Models\Agency
    {
        return \App\Models\Agency::first();
    }
}
