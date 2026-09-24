<?php

namespace App\Http\Controllers;

use App\ClientPortal\ClientPortalService;
use App\Models\Client;
use App\Models\ClientApproval;
use App\Models\ClientNotification;
use App\Models\ClientReport;
use App\Models\SocialAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ClientPortal2Controller extends Controller
{
    private ClientPortalService $service;

    public function __construct(ClientPortalService $service)
    {
        $this->service = $service;
    }

    private function getAgencyId(): int
    {
        return auth()->user()->agency_id;
    }

    private function getClient(Request $request): Client
    {
        $client = Client::where('agency_id', $this->getAgencyId())
            ->findOrFail($request->route('client'));
        return $client;
    }

    public function dashboard(Request $request)
    {
        $client = $this->getClient($request);
        $data = $this->service->getDashboardData($client->id);
        return view('client-portal.dashboard', $data);
    }

    public function campaigns(Request $request)
    {
        $client = $this->getClient($request);
        $data = $this->service->getCampaigns($client->id, $request->only(['status', 'client_id', 'search']));
        return view('client-portal.campaigns', $data);
    }

    public function analytics(Request $request)
    {
        $client = $this->getClient($request);
        $data = $this->service->getAnalytics($client->id);
        return view('client-portal.analytics', $data);
    }

    public function invoices(Request $request)
    {
        $client = $this->getClient($request);
        $data = $this->service->getInvoices($client->id, $request->only(['status', 'client_id', 'date_from', 'date_to']));
        return view('client-portal.invoices', $data);
    }

    public function settings(Request $request)
    {
        $client = $this->getClient($request);
        $data = $this->service->getSettings($client->id);
        return view('client-portal.settings', $data);
    }

    public function updateSettings(Request $request)
    {
        $client = $this->getClient($request);
        $validated = $request->validate([
            'brand_name' => 'required|string|max:255',
            'brand_color' => 'required|string|max:7',
            'logo_url' => 'nullable|url',
            'custom_domain' => 'nullable|string|unique:client_portal_settings',
            'is_enabled' => 'boolean',
            'show_analytics' => 'boolean',
            'show_invoices' => 'boolean',
            'allow_approvals' => 'boolean',
            'show_team_activity' => 'boolean',
            'welcome_message' => 'nullable|string|max:1000',
        ]);

        $this->service->updateSettings($client->id, $validated);

        return redirect()->route('client-portal.v2.settings', ['client' => $client->id])
            ->with('success', 'Settings updated successfully!');
    }

    public function activity(Request $request)
    {
        $client = $this->getClient($request);
        $data = $this->service->getActivityFeed($client->id);
        return view('client-portal.activity', $data);
    }

    public function notifications(Request $request)
    {
        $client = $this->getClient($request);
        $notifications = ClientNotification::forClient($client->id)
            ->recent()
            ->paginate(20);

        $typeCounts = ClientNotification::forClient($client->id)
            ->select('type', \DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        return view('client-portal.notifications', compact('notifications', 'typeCounts', 'client'));
    }

    public function markNotificationRead(Request $request, ClientNotification $notification)
    {
        $client = $this->getClient($request);
        if ($notification->client_id !== $client->id) {
            abort(403);
        }

        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    public function markAllNotificationsRead(Request $request)
    {
        $client = $this->getClient($request);
        ClientNotification::forClient($client->id)->unread()->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function profile(Request $request)
    {
        $client = $this->getClient($request);
        $socialAccounts = SocialAccount::where('client_id', $client->id)->get();

        return view('client-portal.profile', compact('client', 'socialAccounts'));
    }

    public function approvals(Request $request)
    {
        $client = $this->getClient($request);
        $data = $this->service->getApprovalQueue($client->id);
        return view('client-portal.approvals', $data);
    }

    public function downloadReport(Request $request, ClientReport $report)
    {
        $client = $this->getClient($request);
        if ($report->client_id !== $client->id) {
            abort(403);
        }

        return response()->json([
            'report' => $report,
            'download_url' => $report->public_url,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $client = $this->getClient($request);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:100',
        ]);

        $client->update($validated);

        return redirect()->back()->with('success', 'Profile updated successfully!');
    }

    public function updatePassword(Request $request)
    {
        $client = $this->getClient($request);
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = auth()->user();
        if (!Hash::check($validated['current_password'], $user->password)) {
            return redirect()->back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->update(['password' => Hash::make($validated['password'])]);

        return redirect()->back()->with('success', 'Password updated successfully!');
    }

    public function connectSocial(Request $request)
    {
        $client = $this->getClient($request);
        $validated = $request->validate([
            'platform' => 'required|string|in:facebook,twitter,linkedin,instagram',
            'account_name' => 'required|string|max:255',
        ]);

        SocialAccount::create([
            'client_id' => $client->id,
            'agency_id' => $this->getAgencyId(),
            'platform' => $validated['platform'],
            'platform_display_name' => $validated['account_name'],
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'Social account connected successfully!');
    }

    public function disconnectSocial(Request $request, SocialAccount $account)
    {
        $client = $this->getClient($request);
        if ($account->client_id !== $client->id) {
            abort(403);
        }

        $account->delete();

        return redirect()->back()->with('success', 'Social account disconnected successfully!');
    }

    public function approve(Request $request, ClientApproval $approval)
    {
        $client = $this->getClient($request);
        if ($approval->client_id !== $client->id) {
            abort(403);
        }

        $this->service->approveContent($client->id, $approval->id);

        return response()->json(['success' => true, 'message' => 'Content approved']);
    }

    public function reject(Request $request, ClientApproval $approval)
    {
        $client = $this->getClient($request);
        if ($approval->client_id !== $client->id) {
            abort(403);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $this->service->rejectContent($client->id, $approval->id, $validated['reason'] ?? '');

        return response()->json(['success' => true, 'message' => 'Content rejected']);
    }
}
