<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Client;
use App\Models\ContentAsset;
use App\Models\Invoice;
use App\Models\SocialPost;
use App\Models\Workflow;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request)
    {
        $agencyId = $request->user()->agency_id;
        $query = $request->get('q', '');
        $type = $request->get('type', 'all');

        $results = [];

        if (empty($query)) {
            return view('search.index', compact('results', 'query', 'type'));
        }

        // Search posts
        if ($type === 'all' || $type === 'posts') {
            $results['posts'] = SocialPost::where('agency_id', $agencyId)
                ->where(function ($q) use ($query) {
                    $q->where('content', 'like', "%{$query}%")
                        ->orWhere('platform', 'like', "%{$query}%");
                })
                ->limit(10)
                ->get();
        }

        // Search campaigns
        if ($type === 'all' || $type === 'campaigns') {
            $results['campaigns'] = Campaign::where('agency_id', $agencyId)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('description', 'like', "%{$query}%");
                })
                ->limit(10)
                ->get();
        }

        // Search clients
        if ($type === 'all' || $type === 'clients') {
            $results['clients'] = Client::where('agency_id', $agencyId)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%")
                        ->orWhere('company', 'like', "%{$query}%");
                })
                ->limit(10)
                ->get();
        }

        // Search content
        if ($type === 'all' || $type === 'content') {
            $results['content'] = ContentAsset::where('agency_id', $agencyId)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('content', 'like', "%{$query}%");
                })
                ->limit(10)
                ->get();
        }

        // Search invoices
        if ($type === 'all' || $type === 'invoices') {
            $results['invoices'] = Invoice::where('agency_id', $agencyId)
                ->where('invoice_number', 'like', "%{$query}%")
                ->limit(10)
                ->get();
        }

        // Search workflows
        if ($type === 'all' || $type === 'workflows') {
            $results['workflows'] = Workflow::where('agency_id', $agencyId)
                ->where('name', 'like', "%{$query}%")
                ->limit(10)
                ->get();
        }

        return view('search.index', compact('results', 'query', 'type'));
    }
}
