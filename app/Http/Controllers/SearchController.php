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
        $query = trim($request->get('q', ''));
        $type = in_array($request->get('type', 'all'), ['all', 'posts', 'campaigns', 'clients', 'content', 'invoices', 'workflows']) ? $request->get('type', 'all') : 'all';

        $results = [];

        if (empty($query) || strlen($query) < 2) {
            return view('search.index', compact('results', 'query', 'type'));
        }

        // Escape LIKE wildcards in user input — whereLike handles this natively
        if (empty($query) || strlen($query) < 2) {
            return view('search.index', compact('results', 'query', 'type'));
        }

        // Search posts
        if ($type === 'all' || $type === 'posts') {
            $results['posts'] = SocialPost::where('agency_id', $agencyId)
                ->where(function ($q) use ($query) {
                    $q->where('content', 'LIKE', '%'.$query.'%')
                        ->orWhere('platform', 'LIKE', '%'.$query.'%');
                })
                ->limit(10)
                ->get();
        }

        // Search campaigns
        if ($type === 'all' || $type === 'campaigns') {
            $results['campaigns'] = Campaign::where('agency_id', $agencyId)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', '%'.$query.'%')
                        ->orWhere('description', 'LIKE', '%'.$query.'%');
                })
                ->limit(10)
                ->get();
        }

        // Search clients
        if ($type === 'all' || $type === 'clients') {
            $results['clients'] = Client::where('agency_id', $agencyId)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', '%'.$query.'%')
                        ->orWhere('email', 'LIKE', '%'.$query.'%')
                        ->orWhere('company', 'LIKE', '%'.$query.'%');
                })
                ->limit(10)
                ->get();
        }

        // Search content
        if ($type === 'all' || $type === 'content') {
            $results['content'] = ContentAsset::where('agency_id', $agencyId)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', '%'.$query.'%')
                        ->orWhere('content', 'LIKE', '%'.$query.'%');
                })
                ->limit(10)
                ->get();
        }

        // Search invoices
        if ($type === 'all' || $type === 'invoices') {
            $results['invoices'] = Invoice::where('agency_id', $agencyId)
                ->where('invoice_number', 'LIKE', '%'.$query.'%')
                ->limit(10)
                ->get();
        }

        // Search workflows
        if ($type === 'all' || $type === 'workflows') {
            $results['workflows'] = Workflow::where('agency_id', $agencyId)
                ->where('name', 'LIKE', '%'.$query.'%')
                ->limit(10)
                ->get();
        }

        return view('search.index', compact('results', 'query', 'type'));
    }
}
