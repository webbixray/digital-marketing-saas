<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Agency;
use App\Models\AiContentLog;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\ContentAsset;
use App\Models\ContentTemplate;
use App\Models\Feature;
use App\Models\Form;
use App\Models\FormResponse;
use App\Models\InboxMessage;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\LandingPage;
use App\Models\Plan;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\User;
use App\Models\Webhook;
use App\Models\Workflow;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 Starting Demo Seeder...');

        // Create features
        $this->seedFeatures();
        $this->command->info('  ✅ Features seeded');

        // Create plans
        $this->seedPlans();
        $this->command->info('  ✅ Plans seeded');

        // Create demo agency
        $agency = $this->seedAgency();
        $this->command->info('  ✅ Agency seeded');

        // Create team members
        $users = $this->seedUsers($agency);
        $this->command->info('  ✅ Users seeded');

        // Create social accounts
        $this->seedSocialAccounts($agency);
        $this->command->info('  ✅ Social accounts seeded');

        // Create posts
        $this->seedPosts($agency, $users);
        $this->command->info('  ✅ Posts seeded');

        // Create clients
        $clients = $this->seedClients($agency);
        $this->command->info('  ✅ Clients seeded');

        // Create campaigns
        $this->seedCampaigns($agency, $clients);
        $this->command->info('  ✅ Campaigns seeded');

        // Create content library
        $this->seedContentLibrary($agency);
        $this->command->info('  ✅ Content library seeded');

        // Create landing pages
        $this->seedLandingPages($agency);
        $this->command->info('  ✅ Landing pages seeded');

        // Create forms
        $this->seedForms($agency);
        $this->command->info('  ✅ Forms seeded');

        // Create invoices
        $this->seedInvoices($agency);
        $this->command->info('  ✅ Invoices seeded');

        // Create workflows
        $this->seedWorkflows($agency);
        $this->command->info('  ✅ Workflows seeded');

        // Create webhooks
        $this->seedWebhooks($agency);
        $this->command->info('  ✅ Webhooks seeded');

        // Create activity logs
        $this->seedActivityLogs($agency, $users);
        $this->command->info('  ✅ Activity logs seeded');

        // Create AI content logs
        $this->seedAiContentLogs($agency);
        $this->command->info('  ✅ AI content logs seeded');

        // Create inbox messages
        $this->seedInboxMessages($agency);
        $this->command->info('  ✅ Inbox messages seeded');

        $this->command->info('');
        $this->command->info('🎉 Demo data seeded successfully!');
        $this->command->info('');
        $this->command->info('📊 Summary:');
        $this->command->info('  - 1 Agency (Pro Plan)');
        $this->command->info('  - 4 Team Members');
        $this->command->info('  - 6 Social Accounts');
        $this->command->info('  - 25 Social Posts');
        $this->command->info('  - 8 Clients');
        $this->command->info('  - 5 Campaigns');
        $this->command->info('  - 10 Content Assets');
        $this->command->info('  - 3 Landing Pages');
        $this->command->info('  - 2 Forms');
        $this->command->info('  - 5 Invoices');
        $this->command->info('  - 3 Workflows');
        $this->command->info('  - 2 Webhooks');
        $this->command->info('  - 30 Activity Logs');
        $this->command->info('  - 15 AI Content Logs');
        $this->command->info('  - 10 Inbox Messages');
        $this->command->info('');
        $this->command->info('🔑 Login credentials:');
        $this->command->info('  - owner@agency.com (Owner)');
        $this->command->info('  - admin@agency.com (Admin)');
        $this->command->info('  - manager@agency.com (Manager)');
        $this->command->info('  - member@agency.com (Member)');
        $this->command->info('  - Default password: [see .env or reset]');
    }

    protected function seedFeatures(): void
    {
        $features = [
            'analytics' => 'Analytics Dashboard',
            'scheduling' => 'Content Scheduling',
            'content_library' => 'Content Library',
            'campaign_manager' => 'Campaign Manager',
            'client_portal' => 'Client Portal',
            'performance_predictor' => 'AI Performance Prediction',
            'workflow_engine' => 'Workflow Automation',
            'social_inbox' => 'Social Inbox',
            'custom_branding' => 'Custom Branding',
            'api_access' => 'API Access',
            'email_marketing' => 'Email Marketing',
            'landing_pages' => 'Landing Pages',
            'form_builder' => 'Form Builder',
            'priority_support' => 'Priority Support',
            'dedicated_account_manager' => 'Dedicated Account Manager',
        ];

        foreach ($features as $code => $name) {
            Feature::firstOrCreate(['code' => $code], [
                'name' => $name,
                'description' => "Access to {$name} feature.",
                'is_active' => true,
            ]);
        }
    }

    protected function seedPlans(): void
    {
        $plans = [
            [
                'name' => 'Free', 'slug' => 'free', 'price' => 0, 'interval' => 'month',
                'users' => 1, 'social_accounts' => 1, 'posts_per_month' => 30,
                'campaigns' => 1, 'clients' => 0, 'ai_requests_per_month' => 50,
                'ai_generations_per_month' => 20, 'landing_pages' => 0, 'forms' => 0,
                'features' => [], 'is_default' => true, 'sort_order' => 1,
            ],
            [
                'name' => 'Starter', 'slug' => 'starter', 'price' => 29, 'interval' => 'month',
                'users' => 3, 'social_accounts' => 3, 'posts_per_month' => 100,
                'campaigns' => 3, 'clients' => 5, 'ai_requests_per_month' => 200,
                'ai_generations_per_month' => 100, 'landing_pages' => 2, 'forms' => 2,
                'features' => ['analytics', 'scheduling', 'content_library'],
                'is_default' => false, 'sort_order' => 2,
            ],
            [
                'name' => 'Pro', 'slug' => 'pro', 'price' => 79, 'interval' => 'month',
                'users' => 10, 'social_accounts' => 10, 'posts_per_month' => 500,
                'campaigns' => 10, 'clients' => 25, 'ai_requests_per_month' => 1000,
                'ai_generations_per_month' => 500, 'landing_pages' => 10, 'forms' => 10,
                'features' => ['analytics', 'scheduling', 'content_library', 'campaign_manager', 'client_portal', 'analytics_dashboard', 'performance_predictor'],
                'is_default' => false, 'sort_order' => 3,
            ],
            [
                'name' => 'Enterprise', 'slug' => 'enterprise', 'price' => 199, 'interval' => 'month',
                'users' => -1, 'social_accounts' => -1, 'posts_per_month' => -1,
                'campaigns' => -1, 'clients' => -1, 'ai_requests_per_month' => -1,
                'ai_generations_per_month' => -1, 'landing_pages' => -1, 'forms' => -1,
                'features' => array_keys(Feature::all()->keyBy('code')->toArray()),
                'is_default' => false, 'sort_order' => 4,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::firstOrCreate(['slug' => $plan['slug']], $plan);
        }
    }

    protected function seedAgency(): Agency
    {
        return Agency::create([
            'slug' => 'demo-agency-'.Str::random(6),
            'name' => 'Digital Marketing Pro',
            'email' => 'contact@digitalmarketingpro.com',
            'website' => 'https://digitalmarketingpro.com',
            'description' => 'A full-service digital marketing agency specializing in social media management, content creation, and brand growth strategies.',
            'timezone' => 'America/New_York',
            'currency' => 'USD',
            'phone' => '+1 (555) 123-4567',
            'address' => '123 Marketing Street, Suite 100, New York, NY 10001',
            'status' => 'active',
            'subscription_plan' => 'pro',
            'subscription_start' => Carbon::now()->subMonths(6),
            'subscription_status' => 'active',
            'posts_count' => 25,
            'ai_requests_count' => 15,
            'ai_generations_count' => 15,
            'campaigns_count' => 5,
            'clients_count' => 8,
            'users_count' => 4,
            'social_accounts_count' => 6,
            'landing_pages_count' => 3,
            'forms_count' => 2,
        ]);
    }

    protected function seedUsers(Agency $agency): array
    {
        $users = [];

        // Owner
        $users['owner'] = User::create([
            'name' => 'Sarah Johnson',
            'email' => 'owner@agency.com',
            'password' => Hash::make(env('DEMO_PASSWORD', Str::random(16))),
            'agency_id' => $agency->id,
            'role' => 'owner',
            'title' => 'CEO & Founder',
            'phone' => '+1 (555) 111-2222',
            'is_active' => true,
            'is_approved' => true,
            'last_active_at' => Carbon::now(),
        ]);
        $users['owner']->assignRole('owner');

        // Admin
        $users['admin'] = User::create([
            'name' => 'Michael Chen',
            'email' => 'admin@agency.com',
            'password' => Hash::make(env('DEMO_PASSWORD', Str::random(16))),
            'agency_id' => $agency->id,
            'role' => 'admin',
            'title' => 'Operations Manager',
            'phone' => '+1 (555) 222-3333',
            'is_active' => true,
            'is_approved' => true,
            'last_active_at' => Carbon::now()->subHours(2),
        ]);
        $users['admin']->assignRole('admin');

        // Manager
        $users['manager'] = User::create([
            'name' => 'Emily Rodriguez',
            'email' => 'manager@agency.com',
            'password' => Hash::make(env('DEMO_PASSWORD', Str::random(16))),
            'agency_id' => $agency->id,
            'role' => 'manager',
            'title' => 'Social Media Manager',
            'phone' => '+1 (555) 333-4444',
            'is_active' => true,
            'is_approved' => true,
            'last_active_at' => Carbon::now()->subHours(5),
        ]);
        $users['manager']->assignRole('manager');

        // Staff
        $users['member'] = User::create([
            'name' => 'David Kim',
            'email' => 'member@agency.com',
            'password' => Hash::make(env('DEMO_PASSWORD', Str::random(16))),
            'agency_id' => $agency->id,
            'role' => 'member',
            'title' => 'Content Creator',
            'phone' => '+1 (555) 444-5555',
            'is_active' => true,
            'is_approved' => true,
            'last_active_at' => Carbon::now()->subDays(1),
        ]);
        $users['member']->assignRole('member');

        return $users;
    }

    protected function seedSocialAccounts(Agency $agency): void
    {
        $accounts = [
            ['platform' => 'facebook', 'platform_display_name' => 'Digital Marketing Pro', 'platform_username' => 'digitalmarketingpro', 'platform_account_type' => 'page'],
            ['platform' => 'instagram', 'platform_display_name' => '@digitalmarketingpro', 'platform_username' => 'digitalmarketingpro', 'platform_account_type' => 'business'],
            ['platform' => 'twitter', 'platform_display_name' => '@DigiMarketingPro', 'platform_username' => 'DigiMarketingPro', 'platform_account_type' => 'user'],
            ['platform' => 'linkedin', 'platform_display_name' => 'Digital Marketing Pro', 'platform_username' => 'digital-marketing-pro', 'platform_account_type' => 'company'],
            ['platform' => 'tiktok', 'platform_display_name' => '@digitalmarketingpro', 'platform_username' => 'digitalmarketingpro', 'platform_account_type' => 'business'],
            ['platform' => 'pinterest', 'platform_display_name' => 'Digital Marketing Pro', 'platform_username' => 'digimarketingpro', 'platform_account_type' => 'business'],
        ];

        foreach ($accounts as $account) {
            SocialAccount::create(array_merge($account, [
                'agency_id' => $agency->id,
                'platform_account_id' => Str::uuid(),
                'access_token' => encrypt('demo_'.Str::random(64)),
                'is_active' => true,
                'is_verified' => true,
            ]));
        }
    }

    protected function seedPosts(Agency $agency, array $users): void
    {
        $platforms = ['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'pinterest'];
        $statuses = ['published', 'published', 'published', 'scheduled', 'draft', 'failed'];
        $sampleContent = [
            '🚀 Exciting news! We just launched our new product line. Check it out and let us know what you think! #ProductLaunch #Innovation',
            '💡 Did you know? 73% of consumers prefer brands that personalize their experience. Here\'s how we can help your brand stand out! #MarketingTips #Personalization',
            '🎉 We\'re thrilled to announce that we\'ve been named a Top Marketing Agency for 2024! Thank you to our amazing clients and team! #Award #MarketingAgency',
            '📊 New Blog Post: "10 Social Media Trends You Can\'t Ignore in 2024" - Link in bio! #SocialMedia #Trends2024',
            '✨ Behind the scenes at our latest photoshoot. Great content takes teamwork! #BTS #ContentCreation',
            '🔥 Flash Sale Alert! Get 30% off all our marketing packages this week only. DM us for details! #FlashSale #MarketingDeals',
            '📱 Instagram Reels are getting 22% more engagement than regular posts. Are you using them? #InstagramTips #Reels',
            '🎯 The secret to successful marketing? Consistency + Authenticity. Here\'s our approach... #MarketingStrategy #BrandGrowth',
            '🌟 Client Spotlight: We helped @ClientBrand increase their social media engagement by 300% in just 3 months! #ClientSuccess #CaseStudy',
            '📈 Pro tip: Post when your audience is online. Use analytics to find your optimal posting times. #SocialMediaTips #Analytics',
        ];

        $accounts = SocialAccount::where('agency_id', $agency->id)->get();

        for ($i = 0; $i < 25; $i++) {
            $status = $statuses[array_rand($statuses)];
            $platform = $platforms[array_rand($platforms)];
            $account = $accounts->where('platform', $platform)->first();
            $content = $sampleContent[$i % count($sampleContent)];

            $post = SocialPost::create([
                'agency_id' => $agency->id,
                'social_account_id' => $account?->id,
                'platform' => $platform,
                'content' => $content,
                'hashtags' => ['#marketing', '#socialmedia', '#digital', '#growth'],
                'status' => $status,
                'scheduled_at' => $status === 'scheduled' ? Carbon::now()->addDays(rand(1, 14)) : null,
                'published_at' => $status === 'published' ? Carbon::now()->subDays(rand(1, 30)) : null,
                'failed_at' => $status === 'failed' ? Carbon::now()->subDays(rand(1, 7)) : null,
                'error_message' => $status === 'failed' ? 'API rate limit exceeded. Will retry automatically.' : null,
                'views_count' => $status === 'published' ? rand(500, 50000) : 0,
                'likes_count' => $status === 'published' ? rand(10, 5000) : 0,
                'comments_count' => $status === 'published' ? rand(0, 500) : 0,
                'shares_count' => $status === 'published' ? rand(0, 200) : 0,
                'clicks_count' => $status === 'published' ? rand(0, 1000) : 0,
                'quality_score' => rand(40, 95),
                'retry_count' => $status === 'failed' ? rand(1, 2) : 0,
            ]);

            // Attach to random campaign sometimes
            if ($status === 'published' && rand(1, 3) === 1) {
                $campaign = Campaign::where('agency_id', $agency->id)->inRandomOrder()->first();
                if ($campaign) {
                    $post->campaigns()->attach($campaign->id);
                }
            }
        }
    }

    protected function seedClients(Agency $agency): array
    {
        $clients = [
            ['name' => 'TechStart Inc.', 'email' => 'hello@techstart.com', 'company' => 'TechStart Inc.', 'industry' => 'Technology', 'status' => 'active'],
            ['name' => 'GreenLeaf Organics', 'email' => 'info@greenleaforganics.com', 'company' => 'GreenLeaf Organics', 'industry' => 'Food & Beverage', 'status' => 'active'],
            ['name' => 'FitLife Studios', 'email' => 'contact@fitlifestudios.com', 'company' => 'FitLife Studios', 'industry' => 'Health & Fitness', 'status' => 'active'],
            ['name' => 'Urban Fashion Co.', 'email' => 'style@urbanfashion.co', 'company' => 'Urban Fashion Co.', 'industry' => 'Fashion', 'status' => 'active'],
            ['name' => 'CloudSoft Solutions', 'email' => 'sales@cloudsoft.io', 'company' => 'CloudSoft Solutions', 'industry' => 'Software', 'status' => 'active'],
            ['name' => 'EduLearn Academy', 'email' => 'learn@edulearn.com', 'company' => 'EduLearn Academy', 'industry' => 'Education', 'status' => 'lead'],
            ['name' => 'TravelWise Agency', 'email' => 'book@travelwise.com', 'company' => 'TravelWise Agency', 'industry' => 'Travel', 'status' => 'lead'],
            ['name' => 'HomeStyle Interiors', 'email' => 'design@homestyle.com', 'company' => 'HomeStyle Interiors', 'industry' => 'Real Estate', 'status' => 'inactive'],
        ];

        $created = [];
        foreach ($clients as $client) {
            $created[] = Client::create(array_merge($client, [
                'agency_id' => $agency->id,
                'phone' => '+1 (555) '.rand(100, 999).'-'.rand(1000, 9999),
                'notes' => 'Demo client for '.$client['industry'].' industry.',
                'last_contact_at' => Carbon::now()->subDays(rand(1, 30)),
            ]));
        }

        return $created;
    }

    protected function seedCampaigns(Agency $agency, array $clients): void
    {
        $campaigns = [
            ['name' => 'Summer Sale 2024', 'type' => 'seasonal', 'status' => 'active', 'description' => 'Promotional campaign for summer products and services.'],
            ['name' => 'Brand Awareness Q3', 'type' => 'awareness', 'status' => 'active', 'description' => 'Increase brand visibility across all social platforms.'],
            ['name' => 'Product Launch - New App', 'type' => 'product_launch', 'status' => 'completed', 'description' => 'Launch campaign for our new mobile application.'],
            ['name' => 'Holiday Campaign', 'type' => 'seasonal', 'status' => 'draft', 'description' => 'Upcoming holiday season promotional campaign.'],
            ['name' => 'Lead Generation B2B', 'type' => 'conversion', 'status' => 'active', 'description' => 'B2B lead generation campaign targeting enterprise clients.'],
        ];

        foreach ($campaigns as $i => $campaign) {
            Campaign::create(array_merge($campaign, [
                'agency_id' => $agency->id,
                'slug' => Str::slug($campaign['name']).'-'.Str::random(6),
                'client_id' => $clients[$i % count($clients)]?->id,
                'objective' => 'Increase engagement and conversions',
                'target_audience' => '25-45 year old professionals',
                'start_date' => Carbon::now()->subDays(rand(1, 30)),
                'end_date' => Carbon::now()->addDays(rand(30, 90)),
                'tags' => ['demo', 'campaign', strtolower($campaign['type'])],
                'posts_count' => rand(3, 10),
                'views_count' => rand(1000, 50000),
                'likes_count' => rand(100, 5000),
                'comments_count' => rand(10, 500),
                'shares_count' => rand(5, 200),
                'clicks_count' => rand(50, 2000),
            ]));
        }
    }

    protected function seedContentLibrary(Agency $agency): void
    {
        $assets = [
            ['name' => 'Brand Guidelines 2024', 'type' => 'document', 'content' => 'Complete brand guidelines including colors, fonts, and logo usage.'],
            ['name' => 'Social Media Templates', 'type' => 'image', 'content' => 'Collection of Instagram and Facebook post templates.'],
            ['name' => 'Email Newsletter Template', 'type' => 'text', 'content' => 'Monthly newsletter template with sections for updates, tips, and promotions.'],
            ['name' => 'Product Photography', 'type' => 'image', 'content' => 'High-resolution product photos for e-commerce and social media.'],
            ['name' => 'Video Intro Template', 'type' => 'video', 'content' => '15-second animated intro for video content.'],
            ['name' => 'Blog Post Ideas', 'type' => 'text', 'content' => 'List of 50 blog post ideas for the next quarter.'],
            ['name' => 'Hashtag Research', 'type' => 'document', 'content' => 'Research document with top performing hashtags by industry.'],
            ['name' => 'Competitor Analysis', 'type' => 'document', 'content' => 'Analysis of top 10 competitors and their social media strategies.'],
            ['name' => 'Content Calendar Template', 'type' => 'document', 'content' => 'Monthly content calendar template with posting schedule.'],
            ['name' => 'Influencer Outreach Template', 'type' => 'text', 'content' => 'Email template for reaching out to potential influencer partners.'],
        ];

        foreach ($assets as $asset) {
            ContentAsset::create(array_merge($asset, [
                'agency_id' => $agency->id,
                'slug' => Str::slug($asset['name']).'-'.Str::random(6),
                'tags' => ['demo', 'content', strtolower($asset['type'])],
                'status' => 'active',
                'usage_count' => rand(1, 20),
            ]));
        }

        // Content templates
        $templates = [
            ['name' => 'Product Promotion', 'platform' => 'instagram', 'template_content' => '🚀 Introducing {product_name}! {description} Shop now at {link} #NewProduct #ShopNow'],
            ['name' => 'Weekly Tips', 'platform' => 'twitter', 'template_content' => '💡 Tip of the Week: {tip_content} What do you think? Let us know! #ProTips #Marketing'],
            ['name' => 'Client Testimonial', 'platform' => 'facebook', 'template_content' => '⭐ "{testimonial}" - {client_name} See how we helped {client_name} achieve {result}! #ClientSuccess'],
        ];

        foreach ($templates as $template) {
            ContentTemplate::create(array_merge($template, [
                'agency_id' => $agency->id,
                'slug' => Str::slug($template['name']).'-'.Str::random(6),
                'variables' => ['product_name', 'description', 'link'],
                'hashtags' => ['#marketing', '#socialmedia'],
                'status' => 'active',
                'usage_count' => rand(5, 50),
            ]));
        }
    }

    protected function seedLandingPages(Agency $agency): void
    {
        $pages = [
            [
                'name' => 'Free Marketing Audit', 'headline' => 'Get Your Free Marketing Audit', 'content' => '<p>Discover what\'s working and what\'s not in your current marketing strategy. Our experts will analyze your social media presence and provide actionable recommendations.</p>',
                'cta_text' => 'Get My Free Audit', 'cta_url' => '/contact',
            ],
            [
                'name' => 'Social Media Masterclass', 'headline' => 'Master Social Media Marketing in 30 Days', 'content' => '<p>Join our intensive masterclass and learn the strategies used by top brands to grow their social media following and engagement.</p>',
                'cta_text' => 'Enroll Now', 'cta_url' => '/masterclass',
            ],
            [
                'name' => 'Case Studies', 'headline' => 'See How We Helped Brands Grow', 'content' => '<p>Explore our portfolio of successful campaigns and see the results we\'ve delivered for clients across various industries.</p>',
                'cta_text' => 'View Case Studies', 'cta_url' => '/case-studies',
            ],
        ];

        foreach ($pages as $page) {
            LandingPage::create(array_merge($page, [
                'agency_id' => $agency->id,
                'slug' => Str::slug($page['name']),
                'is_published' => true,
                'published_at' => Carbon::now()->subDays(rand(1, 30)),
                'views_count' => rand(100, 5000),
                'clicks_count' => rand(10, 500),
                'conversions_count' => rand(1, 50),
                'conversion_rate' => rand(1, 15),
            ]));
        }
    }

    protected function seedForms(Agency $agency): void
    {
        $contactForm = Form::create([
            'agency_id' => $agency->id,
            'name' => 'Contact Us',
            'slug' => 'contact-us',
            'fields' => [
                ['name' => 'name', 'type' => 'text', 'label' => 'Full Name', 'required' => true],
                ['name' => 'email', 'type' => 'email', 'label' => 'Email Address', 'required' => true],
                ['name' => 'company', 'type' => 'text', 'label' => 'Company', 'required' => false],
                ['name' => 'message', 'type' => 'textarea', 'label' => 'Message', 'required' => true],
            ],
            'success_message' => 'Thank you for contacting us! We\'ll get back to you within 24 hours.',
            'is_published' => true,
            'published_at' => Carbon::now()->subDays(30),
            'submissions_count' => 15,
        ]);

        // Form responses
        for ($i = 0; $i < 15; $i++) {
            FormResponse::create([
                'form_id' => $contactForm->id,
                'data' => [
                    'name' => 'Demo User '.($i + 1),
                    'email' => 'user'.($i + 1).'@example.com',
                    'company' => 'Demo Company '.($i + 1),
                    'message' => 'This is a demo form submission message number '.($i + 1).'.',
                ],
                'ip_address' => '192.168.1.'.rand(1, 255),
                'user_agent' => 'Mozilla/5.0 Demo Browser',
                'submitted_at' => Carbon::now()->subDays(rand(1, 30)),
            ]);
        }

        Form::create([
            'agency_id' => $agency->id,
            'name' => 'Newsletter Signup',
            'slug' => 'newsletter-signup',
            'fields' => [
                ['name' => 'email', 'type' => 'email', 'label' => 'Email Address', 'required' => true],
                ['name' => 'name', 'type' => 'text', 'label' => 'First Name', 'required' => false],
            ],
            'success_message' => 'Welcome to our newsletter! Check your email for a confirmation.',
            'is_published' => true,
            'published_at' => Carbon::now()->subDays(60),
            'submissions_count' => 42,
        ]);
    }

    protected function seedInvoices(Agency $agency): void
    {
        $invoices = [
            ['number' => 'INV-2024-001', 'status' => 'paid', 'total' => 299.00, 'issue_date' => '2024-01-15', 'due_date' => '2024-02-15'],
            ['number' => 'INV-2024-002', 'status' => 'paid', 'total' => 450.00, 'issue_date' => '2024-02-15', 'due_date' => '2024-03-15'],
            ['number' => 'INV-2024-003', 'status' => 'pending', 'total' => 79.00, 'issue_date' => '2024-03-15', 'due_date' => '2024-04-15'],
            ['number' => 'INV-2024-004', 'status' => 'overdue', 'total' => 1200.00, 'issue_date' => '2024-01-01', 'due_date' => '2024-02-01'],
            ['number' => 'INV-2024-005', 'status' => 'draft', 'total' => 350.00, 'issue_date' => '2024-04-01', 'due_date' => '2024-05-01'],
        ];

        foreach ($invoices as $inv) {
            $invoice = Invoice::create([
                'agency_id' => $agency->id,
                'invoice_number' => $inv['number'],
                'status' => $inv['status'],
                'type' => 'invoice',
                'currency' => 'USD',
                'subtotal' => $inv['total'],
                'tax' => 0,
                'discount' => 0,
                'total' => $inv['total'],
                'issue_date' => $inv['issue_date'],
                'due_date' => $inv['due_date'],
                'paid_date' => $inv['status'] === 'paid' ? $inv['due_date'] : null,
                'payment_method' => $inv['status'] === 'paid' ? 'stripe' : null,
                'transaction_id' => $inv['status'] === 'paid' ? 'txn_'.Str::random(20) : null,
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => 'Social Media Management - Monthly',
                'type' => 'line_item',
                'quantity' => 1,
                'unit_price' => $inv['total'],
                'total' => $inv['total'],
            ]);
        }
    }

    protected function seedWorkflows(Agency $agency): void
    {
        $workflows = [
            [
                'name' => 'Auto-Reply to Comments', 'trigger_type' => 'comment_received',
                'actions' => [['type' => 'auto_reply', 'config' => ['message' => 'Thanks for reaching out! We\'ll get back to you soon.']]],
            ],
            [
                'name' => 'Post Success Notification', 'trigger_type' => 'post_published',
                'actions' => [['type' => 'send_notification', 'config' => ['message' => 'Post published successfully!']]],
            ],
            [
                'name' => 'Failed Post Retry', 'trigger_type' => 'post_failed',
                'actions' => [['type' => 'sleep', 'config' => ['seconds' => 300]], ['type' => 'send_notification', 'config' => ['message' => 'Post failed - retrying in 5 minutes']]],
            ],
        ];

        foreach ($workflows as $wf) {
            Workflow::create([
                'agency_id' => $agency->id,
                'name' => $wf['name'],
                'slug' => Str::slug($wf['name']).'-'.Str::random(6),
                'status' => 'active',
                'trigger_type' => $wf['trigger_type'],
                'actions' => $wf['actions'],
                'execution_count' => rand(5, 50),
                'last_executed_at' => Carbon::now()->subDays(rand(1, 7)),
            ]);
        }
    }

    protected function seedWebhooks(Agency $agency): void
    {
        Webhook::create([
            'agency_id' => $agency->id,
            'name' => 'Slack Notifications',
            'url' => 'https://hooks.slack.com/services/demo/webhook',
            'events' => ['post.published', 'post.failed', 'campaign.completed'],
            'is_active' => true,
            'total_calls' => 45,
            'failed_calls' => 2,
            'last_triggered_at' => Carbon::now()->subHours(2),
        ]);

        Webhook::create([
            'agency_id' => $agency->id,
            'name' => 'Zapier Integration',
            'url' => 'https://hooks.zapier.com/hooks/catch/demo/',
            'events' => ['client.created', 'invoice.paid'],
            'is_active' => true,
            'total_calls' => 12,
            'failed_calls' => 0,
            'last_triggered_at' => Carbon::now()->subDays(1),
        ]);
    }

    protected function seedActivityLogs(Agency $agency, array $users): void
    {
        $actions = [
            ['action' => 'post.published', 'description' => 'Post published to Instagram'],
            ['action' => 'post.scheduled', 'description' => 'Post scheduled for tomorrow'],
            ['action' => 'campaign.created', 'description' => 'New campaign "Summer Sale 2024" created'],
            ['action' => 'client.created', 'description' => 'New client "TechStart Inc." added'],
            ['action' => 'invoice.paid', 'description' => 'Invoice INV-2024-001 marked as paid'],
            ['action' => 'user.invited', 'description' => 'New team member invited'],
            ['action' => 'ai.generated', 'description' => 'AI content generated for Instagram post'],
            ['action' => 'workflow.executed', 'description' => 'Workflow "Auto-Reply" executed'],
        ];

        for ($i = 0; $i < 30; $i++) {
            $action = $actions[array_rand($actions)];
            $user = $users[array_rand($users)];

            ActivityLog::create([
                'agency_id' => $agency->id,
                'user_id' => $user->id,
                'action' => $action['action'],
                'description' => $action['description'],
                'subject_type' => null,
                'subject_id' => null,
                'metadata' => null,
                'created_at' => Carbon::now()->subDays(rand(1, 30)),
            ]);
        }
    }

    protected function seedAiContentLogs(Agency $agency): void
    {
        $actions = ['generate', 'rewrite', 'summarize', 'translate', 'ideate'];
        $contentTypes = ['post', 'caption', 'hashtag', 'headline', 'email'];
        $providers = ['openai', 'anthropic', 'google'];
        $models = ['gpt-4o', 'claude-3-5-sonnet-20241022', 'gemini-1.5-pro'];

        for ($i = 0; $i < 15; $i++) {
            $provider = $providers[array_rand($providers)];
            $model = $models[array_rand($models)];

            AiContentLog::create([
                'agency_id' => $agency->id,
                'provider' => $provider,
                'model' => $model,
                'action' => $actions[array_rand($actions)],
                'content_type' => $contentTypes[array_rand($contentTypes)],
                'prompt' => 'Demo prompt for content generation #'.($i + 1),
                'response' => 'Demo AI-generated response for content #'.($i + 1),
                'total_tokens' => rand(100, 2000),
                'prompt_tokens' => rand(50, 500),
                'completion_tokens' => rand(50, 1500),
                'cost_usd' => rand(10, 500) / 10000,
                'status' => 'success',
                'created_at' => Carbon::now()->subDays(rand(1, 30)),
            ]);
        }
    }

    protected function seedInboxMessages(Agency $agency): void
    {
        $messages = [
            ['platform' => 'facebook', 'author_name' => 'John Smith', 'content' => 'Love your latest post! When will you be launching the new product?', 'message_type' => 'comment'],
            ['platform' => 'instagram', 'author_name' => 'Sarah Williams', 'content' => 'Your content is amazing! How can I collaborate with you?', 'message_type' => 'direct_message'],
            ['platform' => 'twitter', 'author_name' => 'Mike Johnson', 'content' => '@DigiMarketingPro Great work on the campaign! What were the results?', 'message_type' => 'mention'],
            ['platform' => 'facebook', 'author_name' => 'Emily Davis', 'content' => 'I\'m interested in your services. Can we schedule a call?', 'message_type' => 'comment'],
            ['platform' => 'instagram', 'author_name' => 'Alex Brown', 'content' => 'Just placed an order through your link! Thanks for the recommendation.', 'message_type' => 'comment'],
            ['platform' => 'twitter', 'author_name' => 'Jessica Lee', 'content' => 'Your thread on marketing trends was incredibly helpful! 🙏', 'message_type' => 'mention'],
            ['platform' => 'facebook', 'author_name' => 'Robert Wilson', 'content' => 'Do you offer services for small businesses?', 'message_type' => 'comment'],
            ['platform' => 'instagram', 'author_name' => 'Amanda Taylor', 'content' => 'The reels you created for us got 100K views! Thank you!', 'message_type' => 'direct_message'],
            ['platform' => 'linkedin', 'author_name' => 'Chris Martinez', 'content' => 'Impressive case study. Would love to discuss a partnership.', 'message_type' => 'comment'],
            ['platform' => 'twitter', 'author_name' => 'David Anderson', 'content' => 'Just shared your latest blog post with my team. Great insights!', 'message_type' => 'mention'],
        ];

        $accounts = SocialAccount::where('agency_id', $agency->id)->get();

        foreach ($messages as $msg) {
            $account = $accounts->where('platform', $msg['platform'])->first();

            InboxMessage::create([
                'agency_id' => $agency->id,
                'social_account_id' => $account?->id,
                'platform' => $msg['platform'],
                'message_id' => Str::uuid(),
                'message_type' => $msg['message_type'],
                'author_name' => $msg['author_name'],
                'author_username' => strtolower(str_replace(' ', '', $msg['author_name'])),
                'content' => $msg['content'],
                'status' => ['unread', 'read', 'replied'][array_rand(['unread', 'read', 'replied'])],
                'received_at' => Carbon::now()->subDays(rand(1, 14)),
                'read_at' => Carbon::now()->subDays(rand(1, 7)),
            ]);
        }
    }
}
