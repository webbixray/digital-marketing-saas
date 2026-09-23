<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AgencyController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    public function show(Request $request)
    {
        $user = $request->user();
        $agencyId = $user->agency_id;
        $agency = Agency::findOrFail($agencyId);

        $team = User::where('agency_id', $agencyId)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $stats = [
            'total_members' => User::where('agency_id', $agencyId)->count(),
            'active_members' => User::where('agency_id', $agencyId)->where('is_active', true)->count(),
        ];

        return view('agency.show', compact('agency', 'team', 'stats'));
    }

    public function edit(Request $request)
    {
        $agencyId = $request->user()->agency_id;
        $agency = Agency::findOrFail($agencyId);

        return view('agency.edit', compact('agency'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:agencies,slug,'.$request->user()->agency_id,
            'email' => 'required|email|max:255',
            'timezone' => 'required|string|max:100',
            'currency' => 'required|string|size:3',
        ]);

        $agencyId = $request->user()->agency_id;
        $agency = Agency::findOrFail($agencyId);

        $agency->update($request->only(['name', 'slug', 'email', 'timezone', 'currency']));

        Log::info('Agency updated', ['agency_id' => $agency->id, 'user_id' => $request->user()->id]);

        return redirect()->route('agency.settings')->with('success', 'Agency updated.');
    }

    public function settings(Request $request)
    {
        $agencyId = $request->user()->agency_id;
        $agency = Agency::findOrFail($agencyId);

        return view('agency.settings', compact('agency'));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'agency_name' => 'required|string|max:255',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string|max:1000',
            'primary_color' => 'nullable|string|size:7',
            'logo_url' => 'nullable|url|max:255',
            'email' => 'required|email|max:255',
            'timezone' => 'required|string|max:100',
            'currency' => 'required|string|size:3',
        ]);

        $agencyId = $request->user()->agency_id;
        $agency = Agency::findOrFail($agencyId);

        $agency->update([
            'name' => $request->agency_name,
            'website' => $request->website,
            'description' => $request->description,
            'primary_color' => $request->primary_color ?? '#4f46e5',
            'logo_url' => $request->logo_url,
            'email' => $request->email,
            'timezone' => $request->timezone,
            'currency' => $request->currency,
        ]);

        return redirect()->route('agency.settings')->with('success', 'Settings updated.');
    }

    public function billing(Request $request)
    {
        $agencyId = $request->user()->agency_id;
        $agency = Agency::findOrFail($agencyId);
        $plans = [
            'starter' => ['name' => 'Starter', 'price' => 29, 'features' => ['posts_per_month' => 100, 'ai_generations_per_month' => 50, 'social_accounts' => 5, 'team_members' => 3]],
            'pro' => ['name' => 'Pro', 'price' => 79, 'features' => ['posts_per_month' => 500, 'ai_generations_per_month' => 200, 'social_accounts' => 15, 'team_members' => 10]],
            'enterprise' => ['name' => 'Enterprise', 'price' => 199, 'features' => ['posts_per_month' => -1, 'ai_generations_per_month' => -1, 'social_accounts' => -1, 'team_members' => -1]],
        ];
        $currentPlan = $agency->subscription_plan ?? 'free';
        $invoices = Invoice::where('agency_id', $agencyId)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('agency.billing', compact('agency', 'plans', 'currentPlan', 'invoices'));
    }

    public function upgrade(Request $request)
    {
        $request->validate([
            'plan' => 'required|in:starter,pro,enterprise',
        ]);

        $agencyId = $request->user()->agency_id;
        $agency = Agency::findOrFail($agencyId);
        $agency->update([
            'subscription_plan' => $request->plan,
            'subscription_start' => now(),
            'subscription_end' => now()->addMonth(),
            'subscription_status' => 'active',
        ]);

        Log::info('Agency subscription upgraded', ['agency_id' => $agency->id, 'plan' => $request->input('plan')]);

        return redirect()->route('agency.billing')->with('success', 'Subscription upgraded.');
    }

    public function subscribe(Request $request)
    {
        $request->validate([
            'plan' => 'required|in:starter,pro,enterprise',
        ]);

        $agencyId = $request->user()->agency_id;
        $agency = Agency::findOrFail($agencyId);
        $agency->update([
            'subscription_plan' => $request->plan,
            'subscription_start' => now(),
            'subscription_end' => now()->addMonth(),
        ]);

        return redirect()->route('agency.billing')->with('success', 'Subscription updated.');
    }

    public function cancelSubscription(Request $request)
    {
        $agencyId = $request->user()->agency_id;
        $agency = Agency::findOrFail($agencyId);
        $agency->update([
            'subscription_plan' => 'free',
            'subscription_status' => 'cancelled',
        ]);

        Log::warning('Agency subscription cancelled', ['agency_id' => $agency->id, 'user_id' => $request->user()->id]);

        return redirect()->route('agency.billing')->with('success', 'Subscription cancelled.');
    }

    public function team(Request $request)
    {
        $user = $request->user();
        $agencyId = $user->agency_id;
        $agency = Agency::findOrFail($agencyId);
        $members = User::where('agency_id', $agencyId)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('agency.team', compact('agency', 'members', 'user'));
    }

    public function inviteMember(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255|unique:users,email',
            'name' => 'required|string|max:255',
            'role' => 'required|in:owner,admin,member,manager,editor',
        ]);

        $agencyId = $request->user()->agency_id;

        $member = new User([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make(Str::random(16)),
        ]);
        $member->agency_id = $agencyId;
        $member->role = $validated['role'];
        $member->save();

        return redirect()->route('agency.team')->with('success', 'Member invited.');
    }

    public function updateMemberRole(Request $request, $userId)
    {
        $validated = $request->validate([
            'role' => 'required|in:owner,admin,member,manager,editor',
        ]);

        $agencyId = $request->user()->agency_id;

        $member = User::findOrFail($userId);

        if ((int) $member->agency_id !== (int) $agencyId) {
            abort(403);
        }

        // Only owner or admin can change roles
        if (! $request->user()->isOwner() && ! $request->user()->isAdmin()) {
            return redirect()->route('agency.team')->with('error', 'Only the owner or admin can change roles.');
        }

        $member->role = $validated['role'];
        $member->save();

        return redirect()->route('agency.team')->with('success', 'Member role updated.');
    }

    public function removeMember(Request $request, $userId)
    {
        $agencyId = $request->user()->agency_id;

        $member = User::findOrFail($userId);

        if ((int) $member->agency_id !== (int) $agencyId) {
            abort(403);
        }

        // Only owner or admin can remove members
        if (! $request->user()->isOwner() && ! $request->user()->isAdmin()) {
            return redirect()->route('agency.team')->with('error', 'Only the owner or admin can remove members.');
        }

        // Cannot remove yourself
        if ((int) $member->id === (int) $request->user()->id) {
            return redirect()->route('agency.team')->with('error', 'You cannot remove yourself.');
        }

        // Cannot remove agency owner
        if ($member->isOwner()) {
            return redirect()->route('agency.team')->with('error', 'Cannot remove the agency owner.');
        }

        $member->delete();

        return redirect()->route('agency.team')->with('success', 'Member removed.');
    }
}
