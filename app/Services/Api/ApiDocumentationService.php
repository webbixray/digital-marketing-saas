<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\Route;

class ApiDocumentationService
{
    /**
     * Generate the OpenAPI 3.0 specification for the Digital Marketing SaaS API.
     */
    public function generateOpenApiSpec(): array
    {
        return [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'Digital Marketing SaaS API',
                'description' => 'RESTful API for managing social media posts, campaigns, analytics, billing, and integrations.',
                'version' => '1.0.0',
                'contact' => [
                    'name' => 'API Support',
                    'email' => 'support@digitalmarketingsaas.com',
                ],
                'license' => [
                    'name' => 'MIT',
                ],
            ],
            'servers' => [
                [
                    'url' => '/api/v1',
                    'description' => 'API v1',
                ],
            ],
            'paths' => $this->getPaths(),
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'API Token',
                    ],
                ],
                'schemas' => $this->getSchemas(),
                'responses' => $this->getErrorResponses(),
            ],
            'tags' => $this->getTags(),
            'security' => [
                ['bearerAuth' => []],
            ],
        ];
    }

    /**
     * Get all tags for grouping endpoints.
     */
    private function getTags(): array
    {
        return [
            ['name' => 'Authentication', 'description' => 'User authentication and token management'],
            ['name' => 'Social Accounts', 'description' => 'Manage connected social media accounts'],
            ['name' => 'Posts', 'description' => 'Create, schedule, and manage social media posts'],
            ['name' => 'Campaigns', 'description' => 'Marketing campaign management and optimization'],
            ['name' => 'Analytics', 'description' => 'Cross-platform analytics and insights'],
            ['name' => 'Reports', 'description' => 'Enterprise reporting and scheduled exports'],
            ['name' => 'Billing', 'description' => 'Invoices, credits, and subscription management'],
            ['name' => 'Integrations', 'description' => 'AI, agents, webhooks, and third-party integrations'],
            ['name' => 'Agency', 'description' => 'Agency settings, team, and RBAC'],
        ];
    }

    /**
     * Get all API paths.
     */
    private function getPaths(): array
    {
        return array_merge(
            $this->getSocialAccountPaths(),
            $this->getPostPaths(),
            $this->getCampaignPaths(),
            $this->getAnalyticsPaths(),
            $this->getReportPaths(),
            $this->getBillingPaths(),
            $this->getIntegrationPaths(),
            $this->getAgencyPaths(),
            $this->getUtilityPaths()
        );
    }

    private function getSocialAccountPaths(): array
    {
        return [
            '/accounts' => [
                'get' => [
                    'tags' => ['Social Accounts'],
                    'summary' => 'List all social accounts',
                    'description' => 'Returns a paginated list of all connected social media accounts.',
                    'responses' => [
                        '200' => [
                            'description' => 'List of social accounts',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/SocialAccount']],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        '401' => ['$ref' => '#/components/responses/Unauthorized'],
                        '403' => ['$ref' => '#/components/responses/Forbidden'],
                    ],
                ],
                'post' => [
                    'tags' => ['Social Accounts'],
                    'summary' => 'Create a social account',
                    'description' => 'Connect a new social media account.',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['platform', 'access_token'],
                                    'properties' => [
                                        'platform' => ['type' => 'string', 'enum' => ['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'youtube', 'pinterest']],
                                        'access_token' => ['type' => 'string'],
                                        'refresh_token' => ['type' => 'string'],
                                        'account_name' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => [
                            'description' => 'Social account created',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/SocialAccount'],
                                ],
                            ],
                        ],
                        '422' => ['$ref' => '#/components/responses/ValidationError'],
                    ],
                ],
            ],
            '/accounts/{account}' => [
                'get' => [
                    'tags' => ['Social Accounts'],
                    'summary' => 'Get a social account',
                    'parameters' => [
                        ['name' => 'account', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Social account details',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/SocialAccount'],
                                ],
                            ],
                        ],
                        '404' => ['$ref' => '#/components/responses/NotFound'],
                    ],
                ],
                'put' => [
                    'tags' => ['Social Accounts'],
                    'summary' => 'Update a social account',
                    'parameters' => [
                        ['name' => 'account', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'requestBody' => [
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'access_token' => ['type' => 'string'],
                                        'account_name' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Updated social account',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/SocialAccount'],
                                ],
                            ],
                        ],
                    ],
                ],
                'delete' => [
                    'tags' => ['Social Accounts'],
                    'summary' => 'Delete a social account',
                    'parameters' => [
                        ['name' => 'account', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '204' => ['description' => 'Social account deleted'],
                    ],
                ],
            ],
        ];
    }

    private function getPostPaths(): array
    {
        return [
            '/posts' => [
                'get' => [
                    'tags' => ['Posts'],
                    'summary' => 'List all posts',
                    'description' => 'Returns a paginated list of social media posts.',
                    'parameters' => [
                        ['name' => 'status', 'in' => 'query', 'schema' => ['type' => 'string', 'enum' => ['draft', 'scheduled', 'published', 'failed']]],
                        ['name' => 'platform', 'in' => 'query', 'schema' => ['type' => 'string']],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'List of posts',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/SocialPost']],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'post' => [
                    'tags' => ['Posts'],
                    'summary' => 'Create a post',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['content', 'platform'],
                                    'properties' => [
                                        'content' => ['type' => 'string'],
                                        'platform' => ['type' => 'string'],
                                        'account_id' => ['type' => 'integer'],
                                        'scheduled_at' => ['type' => 'string', 'format' => 'date-time'],
                                        'media' => ['type' => 'array', 'items' => ['type' => 'string']],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => [
                            'description' => 'Post created',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/SocialPost'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/posts/{post}' => [
                'get' => [
                    'tags' => ['Posts'],
                    'summary' => 'Get a post',
                    'parameters' => [
                        ['name' => 'post', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Post details',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/SocialPost'],
                                ],
                            ],
                        ],
                    ],
                ],
                'put' => [
                    'tags' => ['Posts'],
                    'summary' => 'Update a post',
                    'parameters' => [
                        ['name' => 'post', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'requestBody' => [
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'content' => ['type' => 'string'],
                                        'scheduled_at' => ['type' => 'string', 'format' => 'date-time'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Updated post',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/SocialPost'],
                                ],
                            ],
                        ],
                    ],
                ],
                'delete' => [
                    'tags' => ['Posts'],
                    'summary' => 'Delete a post',
                    'parameters' => [
                        ['name' => 'post', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '204' => ['description' => 'Post deleted'],
                    ],
                ],
            ],
            '/posts/{post}/agent-schedule' => [
                'post' => [
                    'tags' => ['Posts', 'Integrations'],
                    'summary' => 'Schedule post with AI agent',
                    'description' => 'Uses an AI agent to determine the optimal schedule for a post.',
                    'parameters' => [
                        ['name' => 'post', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Scheduled successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'scheduled_at' => ['type' => 'string', 'format' => 'date-time'],
                                            'confidence' => ['type' => 'number'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        '429' => ['$ref' => '#/components/responses/TooManyRequests'],
                    ],
                ],
            ],
            '/posts/{post}/agent-analyze' => [
                'get' => [
                    'tags' => ['Posts', 'Integrations'],
                    'summary' => 'Analyze post with AI agent',
                    'parameters' => [
                        ['name' => 'post', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Analysis results',
                        ],
                    ],
                ],
            ],
            '/posts/{post}/agent-reply-suggestions' => [
                'post' => [
                    'tags' => ['Posts', 'Integrations'],
                    'summary' => 'Generate AI reply suggestions',
                    'parameters' => [
                        ['name' => 'post', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Reply suggestions',
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getCampaignPaths(): array
    {
        return [
            '/campaigns' => [
                'get' => [
                    'tags' => ['Campaigns'],
                    'summary' => 'List all campaigns',
                    'responses' => [
                        '200' => [
                            'description' => 'List of campaigns',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/Campaign']],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'post' => [
                    'tags' => ['Campaigns'],
                    'summary' => 'Create a campaign',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['name', 'objective'],
                                    'properties' => [
                                        'name' => ['type' => 'string'],
                                        'objective' => ['type' => 'string', 'enum' => ['awareness', 'engagement', 'traffic', 'conversions']],
                                        'budget' => ['type' => 'number'],
                                        'start_date' => ['type' => 'string', 'format' => 'date'],
                                        'end_date' => ['type' => 'string', 'format' => 'date'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => [
                            'description' => 'Campaign created',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/Campaign'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/campaigns/{campaign}' => [
                'get' => [
                    'tags' => ['Campaigns'],
                    'summary' => 'Get a campaign',
                    'parameters' => [
                        ['name' => 'campaign', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Campaign details',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/Campaign'],
                                ],
                            ],
                        ],
                    ],
                ],
                'put' => [
                    'tags' => ['Campaigns'],
                    'summary' => 'Update a campaign',
                    'parameters' => [
                        ['name' => 'campaign', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Updated campaign',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/Campaign'],
                                ],
                            ],
                        ],
                    ],
                ],
                'delete' => [
                    'tags' => ['Campaigns'],
                    'summary' => 'Delete a campaign',
                    'parameters' => [
                        ['name' => 'campaign', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '204' => ['description' => 'Campaign deleted'],
                    ],
                ],
            ],
            '/campaigns/{campaign}/agent-optimize' => [
                'post' => [
                    'tags' => ['Campaigns', 'Integrations'],
                    'summary' => 'Optimize campaign with AI',
                    'parameters' => [
                        ['name' => 'campaign', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Optimization results'],
                    ],
                ],
            ],
            '/campaigns/{campaign}/agent-ab-test' => [
                'post' => [
                    'tags' => ['Campaigns', 'Integrations'],
                    'summary' => 'Generate A/B test variants with AI',
                    'parameters' => [
                        ['name' => 'campaign', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '200' => ['description' => 'A/B test variants'],
                    ],
                ],
            ],
            '/campaigns/{campaign}/agent-insights' => [
                'get' => [
                    'tags' => ['Campaigns', 'Integrations'],
                    'summary' => 'Get AI-generated campaign insights',
                    'parameters' => [
                        ['name' => 'campaign', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Campaign insights'],
                    ],
                ],
            ],
        ];
    }

    private function getAnalyticsPaths(): array
    {
        return [
            '/analytics/cross-platform' => [
                'get' => [
                    'tags' => ['Analytics'],
                    'summary' => 'Cross-platform analytics',
                    'description' => 'Aggregated metrics across all connected platforms.',
                    'parameters' => [
                        ['name' => 'date_from', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date']],
                        ['name' => 'date_to', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date']],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Cross-platform analytics data',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'total_impressions' => ['type' => 'integer'],
                                            'total_engagement' => ['type' => 'integer'],
                                            'total_followers' => ['type' => 'integer'],
                                            'platforms' => ['type' => 'array'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/analytics/platform/{platform}' => [
                'get' => [
                    'tags' => ['Analytics'],
                    'summary' => 'Platform-specific analytics',
                    'parameters' => [
                        ['name' => 'platform', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string', 'enum' => ['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'youtube', 'pinterest']]],
                        ['name' => 'date_from', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date']],
                        ['name' => 'date_to', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date']],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Platform analytics data'],
                    ],
                ],
            ],
            '/analytics/growth' => [
                'get' => [
                    'tags' => ['Analytics'],
                    'summary' => 'Growth metrics',
                    'responses' => [
                        '200' => ['description' => 'Growth metrics data'],
                    ],
                ],
            ],
            '/analytics/optimal-times' => [
                'get' => [
                    'tags' => ['Analytics'],
                    'summary' => 'Optimal posting times',
                    'description' => 'AI-calculated best times to post for maximum engagement.',
                    'responses' => [
                        '200' => ['description' => 'Optimal posting times data'],
                    ],
                ],
            ],
            '/analytics/best-platform' => [
                'get' => [
                    'tags' => ['Analytics'],
                    'summary' => 'Best performing platform',
                    'responses' => [
                        '200' => ['description' => 'Best platform analysis'],
                    ],
                ],
            ],
        ];
    }

    private function getReportPaths(): array
    {
        return [
            '/reports' => [
                'get' => [
                    'tags' => ['Reports'],
                    'summary' => 'List all reports',
                    'responses' => [
                        '200' => [
                            'description' => 'List of reports',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/Report']],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'post' => [
                    'tags' => ['Reports'],
                    'summary' => 'Create a report',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['name', 'type'],
                                    'properties' => [
                                        'name' => ['type' => 'string'],
                                        'type' => ['type' => 'string', 'enum' => ['performance', 'engagement', 'growth', 'custom']],
                                        'date_range' => ['type' => 'string'],
                                        'platforms' => ['type' => 'array', 'items' => ['type' => 'string']],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => [
                            'description' => 'Report created',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/Report'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/reports/{report}' => [
                'get' => [
                    'tags' => ['Reports'],
                    'summary' => 'Get a report',
                    'parameters' => [
                        ['name' => 'report', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Report details',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/Report'],
                                ],
                            ],
                        ],
                    ],
                ],
                'put' => [
                    'tags' => ['Reports'],
                    'summary' => 'Update a report',
                    'parameters' => [
                        ['name' => 'report', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Updated report',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/Report'],
                                ],
                            ],
                        ],
                    ],
                ],
                'delete' => [
                    'tags' => ['Reports'],
                    'summary' => 'Delete a report',
                    'parameters' => [
                        ['name' => 'report', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '204' => ['description' => 'Report deleted'],
                    ],
                ],
            ],
            '/reports/types' => [
                'get' => [
                    'tags' => ['Reports'],
                    'summary' => 'List report types',
                    'responses' => [
                        '200' => ['description' => 'Available report types'],
                    ],
                ],
            ],
            '/reports/scheduled' => [
                'get' => [
                    'tags' => ['Reports'],
                    'summary' => 'List scheduled reports',
                    'responses' => [
                        '200' => ['description' => 'Scheduled reports list'],
                    ],
                ],
            ],
            '/reports/export' => [
                'post' => [
                    'tags' => ['Reports'],
                    'summary' => 'Export a report',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['report_id', 'format'],
                                    'properties' => [
                                        'report_id' => ['type' => 'integer'],
                                        'format' => ['type' => 'string', 'enum' => ['pdf', 'csv', 'xlsx']],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Export initiated'],
                    ],
                ],
            ],
            '/reports/schedule' => [
                'post' => [
                    'tags' => ['Reports'],
                    'summary' => 'Schedule a report',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['report_id', 'frequency'],
                                    'properties' => [
                                        'report_id' => ['type' => 'integer'],
                                        'frequency' => ['type' => 'string', 'enum' => ['daily', 'weekly', 'monthly']],
                                        'email' => ['type' => 'string', 'format' => 'email'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Report scheduled'],
                    ],
                ],
            ],
            '/reports/agent-generate' => [
                'post' => [
                    'tags' => ['Reports', 'Integrations'],
                    'summary' => 'Generate report with AI agent',
                    'responses' => [
                        '200' => ['description' => 'AI-generated report'],
                    ],
                ],
            ],
            '/reports/{report}/agent-recommendations' => [
                'get' => [
                    'tags' => ['Reports', 'Integrations'],
                    'summary' => 'Get AI report recommendations',
                    'parameters' => [
                        ['name' => 'report', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '200' => ['description' => 'AI recommendations'],
                    ],
                ],
            ],
            '/reports/agent-schedule' => [
                'post' => [
                    'tags' => ['Reports', 'Integrations'],
                    'summary' => 'Schedule report with AI agent',
                    'responses' => [
                        '200' => ['description' => 'AI-scheduled report'],
                    ],
                ],
            ],
        ];
    }

    private function getBillingPaths(): array
    {
        return [
            '/invoices' => [
                'get' => [
                    'tags' => ['Billing'],
                    'summary' => 'List all invoices',
                    'responses' => [
                        '200' => [
                            'description' => 'List of invoices',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/Invoice']],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'post' => [
                    'tags' => ['Billing'],
                    'summary' => 'Create an invoice',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['client_id', 'amount', 'due_date'],
                                    'properties' => [
                                        'client_id' => ['type' => 'integer'],
                                        'amount' => ['type' => 'number'],
                                        'due_date' => ['type' => 'string', 'format' => 'date'],
                                        'items' => ['type' => 'array'],
                                        'notes' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => [
                            'description' => 'Invoice created',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/Invoice'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/invoices/{invoice}' => [
                'get' => [
                    'tags' => ['Billing'],
                    'summary' => 'Get an invoice',
                    'parameters' => [
                        ['name' => 'invoice', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Invoice details',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/Invoice'],
                                ],
                            ],
                        ],
                    ],
                ],
                'put' => [
                    'tags' => ['Billing'],
                    'summary' => 'Update an invoice',
                    'parameters' => [
                        ['name' => 'invoice', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Updated invoice',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/Invoice'],
                                ],
                            ],
                        ],
                    ],
                ],
                'delete' => [
                    'tags' => ['Billing'],
                    'summary' => 'Delete an invoice',
                    'parameters' => [
                        ['name' => 'invoice', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '204' => ['description' => 'Invoice deleted'],
                    ],
                ],
            ],
            '/ai/credits/balance' => [
                'get' => [
                    'tags' => ['Billing'],
                    'summary' => 'Get AI credit balance',
                    'responses' => [
                        '200' => [
                            'description' => 'Credit balance',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'balance' => ['type' => 'integer'],
                                            'used_this_month' => ['type' => 'integer'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/ai/credits/purchase' => [
                'post' => [
                    'tags' => ['Billing'],
                    'summary' => 'Purchase AI credits',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['amount'],
                                    'properties' => [
                                        'amount' => ['type' => 'integer', 'description' => 'Number of credits to purchase'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Purchase successful'],
                    ],
                ],
            ],
            '/ai/credits/success' => [
                'get' => [
                    'tags' => ['Billing'],
                    'summary' => 'AI credit purchase success callback',
                    'responses' => [
                        '200' => ['description' => 'Success page'],
                    ],
                ],
            ],
            '/referrals/stats' => [
                'get' => [
                    'tags' => ['Billing'],
                    'summary' => 'Get referral statistics',
                    'responses' => [
                        '200' => ['description' => 'Referral stats'],
                    ],
                ],
            ],
        ];
    }

    private function getIntegrationPaths(): array
    {
        return [
            '/ai/generate' => [
                'post' => [
                    'tags' => ['Integrations'],
                    'summary' => 'Generate AI content',
                    'description' => 'Generate marketing content using AI.',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['prompt', 'type'],
                                    'properties' => [
                                        'prompt' => ['type' => 'string'],
                                        'type' => ['type' => 'string', 'enum' => ['caption', 'headline', 'hashtags', 'ad_copy']],
                                        'tone' => ['type' => 'string'],
                                        'platform' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Generated content',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'content' => ['type' => 'string'],
                                            'tokens_used' => ['type' => 'integer'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        '429' => ['$ref' => '#/components/responses/TooManyRequests'],
                    ],
                ],
            ],
            '/agents' => [
                'get' => [
                    'tags' => ['Integrations'],
                    'summary' => 'List AI agents',
                    'responses' => [
                        '200' => [
                            'description' => 'List of agents',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/Agent']],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/agents/stats' => [
                'get' => [
                    'tags' => ['Integrations'],
                    'summary' => 'Get agent statistics',
                    'responses' => [
                        '200' => ['description' => 'Agent statistics'],
                    ],
                ],
            ],
            '/agents/health' => [
                'get' => [
                    'tags' => ['Integrations'],
                    'summary' => 'Get agent health status',
                    'responses' => [
                        '200' => ['description' => 'Agent health status'],
                    ],
                ],
            ],
            '/agents/{name}' => [
                'get' => [
                    'tags' => ['Integrations'],
                    'summary' => 'Get a specific agent',
                    'parameters' => [
                        ['name' => 'name', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Agent details',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/Agent'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/agents/{name}/dispatch' => [
                'post' => [
                    'tags' => ['Integrations'],
                    'summary' => 'Dispatch an agent',
                    'parameters' => [
                        ['name' => 'name', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']],
                    ],
                    'requestBody' => [
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'task' => ['type' => 'string'],
                                        'context' => ['type' => 'object'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '202' => ['description' => 'Agent dispatched'],
                    ],
                ],
            ],
            '/agents/{name}/history' => [
                'get' => [
                    'tags' => ['Integrations'],
                    'summary' => 'Get agent execution history',
                    'parameters' => [
                        ['name' => 'name', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Agent history'],
                    ],
                ],
            ],
            '/agent-workflows' => [
                'get' => [
                    'tags' => ['Integrations'],
                    'summary' => 'List agent workflows',
                    'responses' => [
                        '200' => ['description' => 'List of workflows'],
                    ],
                ],
            ],
            '/agent-workflows/{name}/run' => [
                'post' => [
                    'tags' => ['Integrations'],
                    'summary' => 'Run an agent workflow',
                    'parameters' => [
                        ['name' => 'name', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']],
                    ],
                    'responses' => [
                        '202' => ['description' => 'Workflow started'],
                    ],
                ],
            ],
            '/agent-workflows/{name}/status/{executionId}' => [
                'get' => [
                    'tags' => ['Integrations'],
                    'summary' => 'Get workflow execution status',
                    'parameters' => [
                        ['name' => 'name', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']],
                        ['name' => 'executionId', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Workflow status'],
                    ],
                ],
                'delete' => [
                    'tags' => ['Integrations'],
                    'summary' => 'Cancel workflow execution',
                    'parameters' => [
                        ['name' => 'name', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']],
                        ['name' => 'executionId', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']],
                    ],
                    'responses' => [
                        '204' => ['description' => 'Workflow cancelled'],
                    ],
                ],
            ],
            '/workflows' => [
                'get' => [
                    'tags' => ['Integrations'],
                    'summary' => 'List all workflows',
                    'responses' => [
                        '200' => [
                            'description' => 'List of workflows',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/Workflow']],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'post' => [
                    'tags' => ['Integrations'],
                    'summary' => 'Create a workflow',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['name', 'trigger'],
                                    'properties' => [
                                        'name' => ['type' => 'string'],
                                        'trigger' => ['type' => 'string'],
                                        'actions' => ['type' => 'array'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => [
                            'description' => 'Workflow created',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/Workflow'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/workflows/{workflow}' => [
                'get' => [
                    'tags' => ['Integrations'],
                    'summary' => 'Get a workflow',
                    'parameters' => [
                        ['name' => 'workflow', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Workflow details',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/Workflow'],
                                ],
                            ],
                        ],
                    ],
                ],
                'put' => [
                    'tags' => ['Integrations'],
                    'summary' => 'Update a workflow',
                    'parameters' => [
                        ['name' => 'workflow', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Updated workflow',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/Workflow'],
                                ],
                            ],
                        ],
                    ],
                ],
                'delete' => [
                    'tags' => ['Integrations'],
                    'summary' => 'Delete a workflow',
                    'parameters' => [
                        ['name' => 'workflow', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '204' => ['description' => 'Workflow deleted'],
                    ],
                ],
            ],
            '/workflows/{workflow}/webhook/{secret}' => [
                'post' => [
                    'tags' => ['Integrations'],
                    'summary' => 'Trigger workflow via webhook',
                    'description' => 'Public endpoint for triggering workflows via webhook.',
                    'security' => [],
                    'parameters' => [
                        ['name' => 'workflow', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                        ['name' => 'secret', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']],
                    ],
                    'requestBody' => [
                        'content' => [
                            'application/json' => [
                                'schema' => ['type' => 'object'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Webhook processed'],
                        '401' => ['$ref' => '#/components/responses/Unauthorized'],
                    ],
                ],
            ],
        ];
    }

    private function getAgencyPaths(): array
    {
        return [
            '/agency/settings' => [
                'get' => [
                    'tags' => ['Agency'],
                    'summary' => 'Get agency settings',
                    'responses' => [
                        '200' => ['description' => 'Agency settings'],
                    ],
                ],
                'put' => [
                    'tags' => ['Agency'],
                    'summary' => 'Update agency settings',
                    'requestBody' => [
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'name' => ['type' => 'string'],
                                        'timezone' => ['type' => 'string'],
                                        'currency' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Updated settings'],
                    ],
                ],
            ],
            '/agency/team' => [
                'get' => [
                    'tags' => ['Agency'],
                    'summary' => 'Get agency team members',
                    'responses' => [
                        '200' => ['description' => 'Team members list'],
                    ],
                ],
            ],
            '/agency/billing' => [
                'get' => [
                    'tags' => ['Agency'],
                    'summary' => 'Get agency billing info',
                    'responses' => [
                        '200' => ['description' => 'Billing information'],
                    ],
                ],
            ],
            '/rbac/roles' => [
                'get' => [
                    'tags' => ['Agency'],
                    'summary' => 'List roles',
                    'responses' => [
                        '200' => ['description' => 'List of roles'],
                    ],
                ],
                'post' => [
                    'tags' => ['Agency'],
                    'summary' => 'Create a role',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['name'],
                                    'properties' => [
                                        'name' => ['type' => 'string'],
                                        'permissions' => ['type' => 'array', 'items' => ['type' => 'string']],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => ['description' => 'Role created'],
                    ],
                ],
            ],
            '/rbac/roles/{role}' => [
                'get' => [
                    'tags' => ['Agency'],
                    'summary' => 'Get a role',
                    'parameters' => [
                        ['name' => 'role', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Role details'],
                    ],
                ],
                'put' => [
                    'tags' => ['Agency'],
                    'summary' => 'Update a role',
                    'parameters' => [
                        ['name' => 'role', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Updated role'],
                    ],
                ],
                'delete' => [
                    'tags' => ['Agency'],
                    'summary' => 'Delete a role',
                    'parameters' => [
                        ['name' => 'role', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '204' => ['description' => 'Role deleted'],
                    ],
                ],
            ],
            '/rbac/roles/assign' => [
                'post' => [
                    'tags' => ['Agency'],
                    'summary' => 'Assign role to user',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['user_id', 'role_id'],
                                    'properties' => [
                                        'user_id' => ['type' => 'integer'],
                                        'role_id' => ['type' => 'integer'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Role assigned'],
                    ],
                ],
            ],
            '/rbac/roles/remove' => [
                'post' => [
                    'tags' => ['Agency'],
                    'summary' => 'Remove role from user',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['user_id', 'role_id'],
                                    'properties' => [
                                        'user_id' => ['type' => 'integer'],
                                        'role_id' => ['type' => 'integer'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Role removed'],
                    ],
                ],
            ],
            '/rbac/roles/{roleId}/permissions' => [
                'get' => [
                    'tags' => ['Agency'],
                    'summary' => 'Get role permissions',
                    'parameters' => [
                        ['name' => 'roleId', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Role permissions'],
                    ],
                ],
            ],
            '/rbac/users/{userId}/permissions' => [
                'get' => [
                    'tags' => ['Agency'],
                    'summary' => 'Get user permissions',
                    'parameters' => [
                        ['name' => 'userId', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '200' => ['description' => 'User permissions'],
                    ],
                ],
            ],
            '/rbac/audit-trail' => [
                'get' => [
                    'tags' => ['Agency'],
                    'summary' => 'Get audit trail',
                    'responses' => [
                        '200' => ['description' => 'Audit trail entries'],
                    ],
                ],
            ],
        ];
    }

    private function getUtilityPaths(): array
    {
        return [
            '/dashboard' => [
                'get' => [
                    'tags' => ['Analytics'],
                    'summary' => 'Get dashboard data',
                    'responses' => [
                        '200' => ['description' => 'Dashboard data'],
                    ],
                ],
            ],
            '/dashboard/insights' => [
                'get' => [
                    'tags' => ['Analytics'],
                    'summary' => 'Get dashboard insights',
                    'responses' => [
                        '200' => ['description' => 'Dashboard insights'],
                    ],
                ],
            ],
            '/clients' => [
                'get' => [
                    'tags' => ['Agency'],
                    'summary' => 'List all clients',
                    'responses' => [
                        '200' => [
                            'description' => 'List of clients',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/Client']],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'post' => [
                    'tags' => ['Agency'],
                    'summary' => 'Create a client',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['name', 'email'],
                                    'properties' => [
                                        'name' => ['type' => 'string'],
                                        'email' => ['type' => 'string', 'format' => 'email'],
                                        'company' => ['type' => 'string'],
                                        'phone' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => [
                            'description' => 'Client created',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/Client'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/clients/{client}' => [
                'get' => [
                    'tags' => ['Agency'],
                    'summary' => 'Get a client',
                    'parameters' => [
                        ['name' => 'client', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Client details',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/Client'],
                                ],
                            ],
                        ],
                    ],
                ],
                'put' => [
                    'tags' => ['Agency'],
                    'summary' => 'Update a client',
                    'parameters' => [
                        ['name' => 'client', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Updated client',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/Client'],
                                ],
                            ],
                        ],
                    ],
                ],
                'delete' => [
                    'tags' => ['Agency'],
                    'summary' => 'Delete a client',
                    'parameters' => [
                        ['name' => 'client', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '204' => ['description' => 'Client deleted'],
                    ],
                ],
            ],
            '/quota' => [
                'get' => [
                    'tags' => ['Billing'],
                    'summary' => 'Get quota status',
                    'responses' => [
                        '200' => ['description' => 'Quota status'],
                    ],
                ],
            ],
            '/quota/check' => [
                'post' => [
                    'tags' => ['Billing'],
                    'summary' => 'Check quota availability',
                    'requestBody' => [
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'resource' => ['type' => 'string'],
                                        'amount' => ['type' => 'integer'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Quota check result'],
                    ],
                ],
            ],
            '/client-reports' => [
                'get' => [
                    'tags' => ['Reports'],
                    'summary' => 'List client reports',
                    'responses' => [
                        '200' => ['description' => 'List of client reports'],
                    ],
                ],
                'post' => [
                    'tags' => ['Reports'],
                    'summary' => 'Generate client report',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['client_id', 'type'],
                                    'properties' => [
                                        'client_id' => ['type' => 'integer'],
                                        'type' => ['type' => 'string'],
                                        'date_range' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => ['description' => 'Report generated'],
                    ],
                ],
            ],
            '/client-reports/{report}' => [
                'get' => [
                    'tags' => ['Reports'],
                    'summary' => 'Get a client report',
                    'parameters' => [
                        ['name' => 'report', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Client report details'],
                    ],
                ],
                'post' => [
                    'tags' => ['Reports'],
                    'summary' => 'Publish a client report',
                    'parameters' => [
                        ['name' => 'report', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Report published'],
                    ],
                ],
                'delete' => [
                    'tags' => ['Reports'],
                    'summary' => 'Delete a client report',
                    'parameters' => [
                        ['name' => 'report', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '204' => ['description' => 'Report deleted'],
                    ],
                ],
            ],
            '/metrics' => [
                'get' => [
                    'tags' => ['Analytics'],
                    'summary' => 'Get system metrics',
                    'description' => 'Admin-only endpoint for system metrics.',
                    'responses' => [
                        '200' => ['description' => 'System metrics'],
                    ],
                ],
            ],
            '/health' => [
                'get' => [
                    'tags' => ['Authentication'],
                    'summary' => 'Health check',
                    'description' => 'Public health check endpoint.',
                    'security' => [],
                    'responses' => [
                        '200' => [
                            'description' => 'Service is healthy',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'status' => ['type' => 'string', 'example' => 'ok'],
                                            'version' => ['type' => 'string', 'example' => 'v1'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get reusable schemas.
     */
    private function getSchemas(): array
    {
        return [
            'SocialAccount' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'platform' => ['type' => 'string'],
                    'account_name' => ['type' => 'string'],
                    'account_id' => ['type' => 'string'],
                    'status' => ['type' => 'string', 'enum' => ['active', 'expired', 'revoked']],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'SocialPost' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'content' => ['type' => 'string'],
                    'platform' => ['type' => 'string'],
                    'account_id' => ['type' => 'integer'],
                    'status' => ['type' => 'string', 'enum' => ['draft', 'scheduled', 'published', 'failed']],
                    'scheduled_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                    'published_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                    'media' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'Campaign' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'objective' => ['type' => 'string'],
                    'budget' => ['type' => 'number'],
                    'spent' => ['type' => 'number'],
                    'status' => ['type' => 'string', 'enum' => ['active', 'paused', 'completed']],
                    'start_date' => ['type' => 'string', 'format' => 'date'],
                    'end_date' => ['type' => 'string', 'format' => 'date'],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'Client' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'email' => ['type' => 'string'],
                    'company' => ['type' => 'string'],
                    'phone' => ['type' => 'string'],
                    'status' => ['type' => 'string', 'enum' => ['active', 'inactive']],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'Invoice' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'client_id' => ['type' => 'integer'],
                    'amount' => ['type' => 'number'],
                    'currency' => ['type' => 'string'],
                    'status' => ['type' => 'string', 'enum' => ['draft', 'sent', 'paid', 'overdue']],
                    'due_date' => ['type' => 'string', 'format' => 'date'],
                    'items' => ['type' => 'array'],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'Report' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'type' => ['type' => 'string'],
                    'status' => ['type' => 'string', 'enum' => ['pending', 'generating', 'completed', 'failed']],
                    'date_range' => ['type' => 'string'],
                    'platforms' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'file_url' => ['type' => 'string', 'nullable' => true],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'Workflow' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'trigger' => ['type' => 'string'],
                    'actions' => ['type' => 'array'],
                    'status' => ['type' => 'string', 'enum' => ['active', 'inactive']],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'Agent' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'type' => ['type' => 'string'],
                    'status' => ['type' => 'string', 'enum' => ['active', 'inactive', 'error']],
                    'last_run_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'Role' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'permissions' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'Error' => [
                'type' => 'object',
                'properties' => [
                    'message' => ['type' => 'string'],
                    'errors' => [
                        'type' => 'object',
                        'additionalProperties' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get reusable error response definitions.
     */
    private function getErrorResponses(): array
    {
        return [
            'Unauthorized' => [
                'description' => 'Authentication required',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/Error'],
                        'example' => [
                            'message' => 'Unauthenticated.',
                        ],
                    ],
                ],
            ],
            'Forbidden' => [
                'description' => 'Insufficient permissions',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/Error'],
                        'example' => [
                            'message' => 'This action is unauthorized.',
                        ],
                    ],
                ],
            ],
            'NotFound' => [
                'description' => 'Resource not found',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/Error'],
                        'example' => [
                            'message' => 'Resource not found.',
                        ],
                    ],
                ],
            ],
            'ValidationError' => [
                'description' => 'Validation failed',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/Error'],
                        'example' => [
                            'message' => 'The given data was invalid.',
                            'errors' => [
                                'email' => ['The email field is required.'],
                            ],
                        ],
                    ],
                ],
            ],
            'TooManyRequests' => [
                'description' => 'Rate limit exceeded',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/Error'],
                        'example' => [
                            'message' => 'Too many requests. Please try again later.',
                        ],
                    ],
                ],
                'headers' => [
                    'Retry-After' => [
                        'description' => 'Seconds to wait before retrying',
                        'schema' => ['type' => 'integer'],
                    ],
                    'X-RateLimit-Limit' => [
                        'description' => 'Request limit per window',
                        'schema' => ['type' => 'integer'],
                    ],
                    'X-RateLimit-Remaining' => [
                        'description' => 'Remaining requests in current window',
                        'schema' => ['type' => 'integer'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Generate a Postman collection from the OpenAPI spec.
     */
    public function generatePostmanCollection(): array
    {
        $spec = $this->generateOpenApiSpec();

        $collection = [
            'info' => [
                'name' => $spec['info']['title'],
                'description' => $spec['info']['description'],
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'auth' => [
                'type' => 'bearer',
                'bearer' => [
                    [
                        'key' => 'token',
                        'value' => '{{api_token}}',
                        'type' => 'string',
                    ],
                ],
            ],
            'variable' => [
                [
                    'key' => 'base_url',
                    'value' => '{{APP_URL}}/api/v1',
                ],
                [
                    'key' => 'api_token',
                    'value' => '',
                ],
            ],
            'item' => [],
        ];

        $groupedItems = [];
        foreach ($spec['paths'] as $path => $methods) {
            foreach ($methods as $method => $details) {
                $tag = $details['tags'][0] ?? 'Other';
                if (!isset($groupedItems[$tag])) {
                    $groupedItems[$tag] = [];
                }
                $groupedItems[$tag][] = [
                    'name' => $details['summary'] ?? "{$method} {$path}",
                    'request' => [
                        'method' => strtoupper($method),
                        'header' => [
                            [
                                'key' => 'Content-Type',
                                'value' => 'application/json',
                            ],
                            [
                                'key' => 'Accept',
                                'value' => 'application/json',
                            ],
                        ],
                        'url' => [
                            'raw' => '{{base_url}}' . $path,
                            'host' => ['{{base_url}}'],
                            'path' => explode('/', ltrim($path, '/')),
                        ],
                        'description' => $details['description'] ?? null,
                    ],
                    'response' => [],
                ];
            }
        }

        foreach ($groupedItems as $tag => $items) {
            $collection['item'][] = [
                'name' => $tag,
                'item' => $items,
            ];
        }

        return $collection;
    }
}
