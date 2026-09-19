<?php

namespace Database\Seeders;

use App\Models\Platform;
use Illuminate\Database\Seeder;

class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        $platforms = [
            'facebook' => [
                'name' => 'Facebook',
                'display_name' => 'Facebook',
                'description' => 'Facebook Pages and Groups integration',
                'icon' => 'fab fa-facebook',
                'color' => '#1877F2',
                'api_version' => 'v21.0',
                'config' => [
                    'auth_type' => 'oauth2',
                    'scopes' => ['pages_manage_posts', 'pages_read_engagement', 'pages_show_list'],
                    'api_base_url' => 'https://graph.facebook.com',
                ],
                'is_active' => true,
                'is_published' => true,
                'sort_order' => 10,
            ],
            'instagram' => [
                'name' => 'instagram',
                'display_name' => 'Instagram',
                'description' => 'Instagram Business and Creator accounts',
                'icon' => 'fab fa-instagram',
                'color' => '#E4405F',
                'api_version' => 'v21.0',
                'config' => [
                    'auth_type' => 'oauth2',
                    'scopes' => ['instagram_basic', 'instagram_content_publish', 'instagram_manage_comments', 'instagram_manage_insights', 'pages_show_list'],
                    'api_base_url' => 'https://graph.facebook.com',
                ],
                'is_active' => true,
                'is_published' => true,
                'sort_order' => 20,
            ],
            'twitter' => [
                'name' => 'twitter',
                'display_name' => 'X (Twitter)',
                'description' => 'X (formerly Twitter) posts and analytics',
                'icon' => 'fab fa-x-twitter',
                'color' => '#000000',
                'api_version' => 'v2',
                'config' => [
                    'auth_type' => 'oauth1',
                    'scopes' => ['tweet.read', 'tweet.write', 'users.read', 'offline.access'],
                    'api_base_url' => 'https://api.twitter.com',
                ],
                'is_active' => true,
                'is_published' => true,
                'sort_order' => 30,
            ],
            'linkedin' => [
                'name' => 'linkedin',
                'display_name' => 'LinkedIn',
                'description' => 'LinkedIn personal and organization profiles',
                'icon' => 'fab fa-linkedin',
                'color' => '#0A66C2',
                'api_version' => 'v2',
                'config' => [
                    'auth_type' => 'oauth2',
                    'scopes' => ['openid', 'profile', 'email', 'w_member_social', 'w_organization_social', 'r_organization_social'],
                    'api_base_url' => 'https://api.linkedin.com',
                ],
                'is_active' => true,
                'is_published' => true,
                'sort_order' => 40,
            ],
            'tiktok' => [
                'name' => 'tiktok',
                'display_name' => 'TikTok',
                'description' => 'TikTok Business accounts',
                'icon' => 'fab fa-tiktok',
                'color' => '#000000',
                'api_version' => 'v1.3',
                'config' => [
                    'auth_type' => 'oauth2',
                    'scopes' => ['user.info.basic', 'video.publish', 'video.list', 'comment.list'],
                    'api_base_url' => 'https://open.tiktokapis.com',
                ],
                'is_active' => true,
                'is_published' => true,
                'sort_order' => 50,
            ],
            'pinterest' => [
                'name' => 'pinterest',
                'display_name' => 'Pinterest',
                'description' => 'Pinterest business accounts and pins',
                'icon' => 'fab fa-pinterest',
                'color' => '#E60023',
                'api_version' => 'v5',
                'config' => [
                    'auth_type' => 'oauth2',
                    'scopes' => ['boards:read', 'boards:write', 'pins:read', 'pins:write', 'user_accounts:read'],
                    'api_base_url' => 'https://api.pinterest.com',
                ],
                'is_active' => true,
                'is_published' => true,
                'sort_order' => 60,
            ],
            'youtube' => [
                'name' => 'youtube',
                'display_name' => 'YouTube',
                'description' => 'YouTube channel management and analytics',
                'icon' => 'fab fa-youtube',
                'color' => '#FF0000',
                'api_version' => 'v3',
                'config' => [
                    'auth_type' => 'oauth2',
                    'scopes' => ['https://www.googleapis.com/auth/youtube', 'https://www.googleapis.com/auth/youtube.upload', 'https://www.googleapis.com/auth/youtube.readonly', 'https://www.googleapis.com/auth/youtubepartner'],
                    'api_base_url' => 'https://www.googleapis.com/youtube/v3',
                ],
                'is_active' => true,
                'is_published' => true,
                'sort_order' => 70,
            ],
        ];

        foreach ($platforms as $slug => $data) {
            Platform::updateOrCreate(
                ['slug' => $slug],
                array_merge($data, ['slug' => $slug])
            );
        }
    }
}
