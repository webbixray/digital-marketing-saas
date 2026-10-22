<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AgencyResource;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\UserResource;
use App\Models\Agency;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiAgencyController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    public function settings(Request $request): JsonResponse
    {
        $agency = Agency::find($request->user()->agency_id);

        return (new AgencyResource($agency))->response();
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'website' => 'nullable|url',
            'timezone' => 'nullable|string|max:50',
            'currency' => 'nullable|string|size:3',
        ]);

        $agency = Agency::find($request->user()->agency_id);
        $agency->update($data);

        return (new AgencyResource($agency))->response();
    }

    public function team(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;
        $users = User::where('agency_id', $agencyId)->orderBy('created_at', 'desc')->paginate(20);

        return UserResource::collection($users)->response();
    }

    public function billing(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;
        $invoices = Invoice::where('agency_id', $agencyId)
            ->with('client')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return InvoiceResource::collection($invoices)->response();
    }
}
