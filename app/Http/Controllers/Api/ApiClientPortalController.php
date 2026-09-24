<?php

namespace App\Http\Controllers\Api;

use App\ClientPortal\ClientPortalService;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientApproval;
use App\Models\ClientNotification;
use Illuminate\Http\Request;

class ApiClientPortalController extends Controller
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
        return Client::where('agency_id', $this->getAgencyId())
            ->findOrFail($request->route('client'));
    }

    public function dashboard(Request $request)
    {
        $client = $this->getClient($request);
        $data = $this->service->getDashboardData($client->id);
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function campaigns(Request $request)
    {
        $client = $this->getClient($request);
        $data = $this->service->getCampaigns($client->id, $request->only(['status', 'search']));
        return response()->json(['success' => true, 'data' => $data['campaigns']]);
    }

    public function invoices(Request $request)
    {
        $client = $this->getClient($request);
        $data = $this->service->getInvoices($client->id, $request->only(['status']));
        return response()->json(['success' => true, 'data' => $data['invoices']]);
    }

    public function analytics(Request $request)
    {
        $client = $this->getClient($request);
        $data = $this->service->getAnalytics($client->id);
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function settings(Request $request)
    {
        $client = $this->getClient($request);
        $data = $this->service->getSettings($client->id);
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function approve(Request $request, ClientApproval $approval)
    {
        $client = $this->getClient($request);
        if ($approval->client_id !== $client->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $result = $this->service->approveContent($client->id, $approval->id);

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function reject(Request $request, ClientApproval $approval)
    {
        $client = $this->getClient($request);
        if ($approval->client_id !== $client->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $result = $this->service->rejectContent($client->id, $approval->id, $validated['reason'] ?? '');

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function notifications(Request $request)
    {
        $client = $this->getClient($request);
        $notifications = ClientNotification::forClient($client->id)
            ->recent()
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $notifications]);
    }
}
