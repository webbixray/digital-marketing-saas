<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\Campaign;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OnboardingController extends Controller
{
    /**
     * Get the current step from session or default to 1.
     */
    private function getCurrentStep(): int
    {
        return session('onboarding_step', 1);
    }

    /**
     * Set the current onboarding step.
     */
    private function setStep(int $step): void
    {
        session(['onboarding_step' => $step]);
    }

    /**
     * Check if onboarding is complete.
     */
    private function isComplete(): bool
    {
        return Auth::user()->agency->onboarding_completed ?? false;
    }

    /**
     * Step 1: Create/Update Agency details.
     */
    public function step1_createAgency(Request $request)
    {
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'agency_name' => 'required|string|max:255',
                'website' => 'nullable|url|max:255',
                'timezone' => 'required|string|in:'.implode(',', timezone_identifiers_list()),
            ]);

            $agency = Auth::user()->agency;
            $agency->update([
                'name' => $validated['agency_name'],
                'website' => $validated['website'],
                'timezone' => $validated['timezone'],
            ]);

            $this->setStep(2);

            return redirect()->route('onboarding.step2')
                ->with('success', 'Agency details saved!');
        }

        return view('onboarding.step1_agency', [
            'agency' => Auth::user()->agency,
        ]);
    }

    /**
     * Step 2: Connect social accounts.
     */
    public function step2_connectSocial(Request $request)
    {
        $agency = Auth::user()->agency;

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'platforms' => 'nullable|array',
                'platforms.*' => 'in:facebook,instagram,twitter,linkedin,tiktok,pinterest',
            ]);

            $platforms = $validated['platforms'] ?? [];

            // Create placeholder social accounts for selected platforms
            foreach ($platforms as $platform) {
                $exists = SocialAccount::where('agency_id', $agency->id)
                    ->where('platform', $platform)
                    ->exists();

                if (! $exists) {
                    SocialAccount::create([
                        'agency_id' => $agency->id,
                        'platform' => $platform,
                        'platform_username' => '@'.Str::slug($agency->name).'_'.$platform,
                        'is_active' => true,
                        'access_token' => encrypt('pending_'.Str::random(32)),
                    ]);
                }
            }

            $this->setStep(3);

            return redirect()->route('onboarding.step3')
                ->with('success', 'Social accounts connected!');
        }

        $connectedAccounts = $agency->socialAccounts()->get()->keyBy('platform');

        return view('onboarding.step2_social', [
            'connectedAccounts' => $connectedAccounts,
            'platforms' => SocialAccount::SUPPORTED_PLATFORMS,
        ]);
    }

    /**
     * Step 3: Invite team members.
     */
    public function step3_inviteTeam(Request $request)
    {
        $agency = Auth::user()->agency;

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'invites' => 'nullable|array',
                'invites.*.name' => 'required_with:invites|string|max:255',
                'invites.*.email' => 'required_with:invites|email|unique:users,email',
                'invites.*.role' => 'required_with:invites|in:admin,member,editor',
            ]);

            $invites = $validated['invites'] ?? [];

            foreach ($invites as $invite) {
                if (empty($invite['email'])) {
                    continue;
                }

                $invite = new User([
                    'name' => $invite['name'],
                    'email' => $invite['email'],
                    'password' => Hash::make(Str::random(16)),
                ]);
                $invite->agency_id = $agency->id;
                $invite->role = $invite['role'];
                $invite->is_active = true;
                $invite->is_approved = true;
                $invite->save();
            }

            $this->setStep(4);

            return redirect()->route('onboarding.step4')
                ->with('success', 'Team members invited!');
        }

        $teamMembers = $agency->users()->where('id', '!=', Auth::id())->get();

        return view('onboarding.step3_team', [
            'teamMembers' => $teamMembers,
        ]);
    }

    /**
     * Step 4: Create first campaign.
     */
    public function step4_createCampaign(Request $request)
    {
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|in:'.implode(',', array_keys(Campaign::CAMPAIGN_TYPES)),
                'description' => 'nullable|string|max:1000',
                'objective' => 'nullable|string|max:255',
                'start_date' => 'nullable|date|after_or_equal:today',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);

            $agency = Auth::user()->agency;

            Campaign::create([
                'agency_id' => $agency->id,
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']).'-'.uniqid(),
                'type' => $validated['type'],
                'status' => 'draft',
                'description' => $validated['description'] ?? null,
                'objective' => $validated['objective'] ?? null,
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
            ]);

            $this->setStep(5);

            return redirect()->route('onboarding.step5')
                ->with('success', 'Campaign created!');
        }

        return view('onboarding.step4_campaign', [
            'campaignTypes' => Campaign::CAMPAIGN_TYPES,
        ]);
    }

    /**
     * Step 5: Activate AI agents.
     */
    public function step5_activateAI(Request $request)
    {
        $agency = Auth::user()->agency;

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'ai_content_generation' => 'boolean',
                'ai_post_optimization' => 'boolean',
                'ai_analytics' => 'boolean',
                'ai_scheduling' => 'boolean',
            ]);

            // Store AI preferences in agency settings
            $settings = $agency->custom_settings ?? [];
            $settings['ai_agents'] = [
                'content_generation' => $validated['ai_content_generation'] ?? true,
                'post_optimization' => $validated['ai_post_optimization'] ?? true,
                'analytics' => $validated['ai_analytics'] ?? true,
                'scheduling' => $validated['ai_scheduling'] ?? true,
            ];
            $agency->update([
                'custom_settings' => $settings,
            ]);

            return $this->complete();
        }

        $aiSettings = $agency->custom_settings['ai_agents'] ?? [];

        return view('onboarding.step5_ai', [
            'aiSettings' => $aiSettings,
        ]);
    }

    /**
     * Quick start - create sample content and skip to dashboard.
     */
    public function quickStart()
    {
        $agency = Auth::user()->agency;

        // Create sample posts
        $sampleService = app(SampleContentService::class);
        $sampleService->createSamplePosts($agency, 'instagram');
        $sampleService->createSamplePosts($agency, 'twitter');

        // Mark onboarding complete
        $settings = $agency->custom_settings ?? [];
        $settings['onboarding_completed'] = true;
        $settings['onboarding_completed_at'] = now()->toISOString();
        $settings['quick_start'] = true;
        $agency->update(['custom_settings' => $settings]);

        session()->forget('onboarding_step');

        return redirect()->route('dashboard')
            ->with('success', 'Welcome! We\'ve created some sample posts to get you started. Check them out!');
    }

    /**
     * Complete onboarding and redirect to dashboard.
     */
    public function complete()
    {
        $agency = Auth::user()->agency;
        $settings = $agency->custom_settings ?? [];
        $settings['onboarding_completed'] = true;
        $settings['onboarding_completed_at'] = now()->toISOString();

        $agency->update([
            'custom_settings' => $settings,
        ]);

        session()->forget('onboarding_step');

        return redirect()->route('dashboard')
            ->with('success', 'Welcome aboard! Your agency is ready to go.');
    }
}
