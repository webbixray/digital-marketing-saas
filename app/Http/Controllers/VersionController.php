<?php

namespace App\Http\Controllers;

use App\Services\VersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VersionController extends Controller
{
    public function __construct(private VersionService $version)
    {
        $this->middleware(['auth', 'agency'])->only(['changelog']);
        $this->middleware('guest')->only(['latest']);
    }

    /**
     * API: Get current version (public endpoint for update checks).
     */
    public function latest(): JsonResponse
    {
        return response()->json([
            'name' => config('app.name'),
            'version' => $this->version->getVersionInfo(),
            'latest_release' => $this->version->getLatestRelease(),
            'api_versions' => config('version.api'),
        ]);
    }

    /**
     * API: Get changelog (authenticated).
     */
    public function changelog(Request $request): JsonResponse
    {
        $limit = min(50, max(1, (int) $request->query('limit', 10)));

        return response()->json([
            'changelog' => $this->version->getChangelog($limit),
        ]);
    }

    /**
     * API: Check for updates.
     */
    public function check(Request $request): JsonResponse
    {
        $remoteVersion = $request->query('remote', '');

        if (empty($remoteVersion)) {
            return response()->json(['error' => 'Remote version required'], 400);
        }

        return response()->json([
            'current' => $this->version->getVersion(),
            'remote' => $remoteVersion,
            'update_available' => $this->version->isUpdateAvailable($remoteVersion),
        ]);
    }

    /**
     * View: Changelog page (in-app).
     */
    public function index(Request $request)
    {
        $changelog = $this->version->getChangelog(20);
        $currentVersion = $this->version->getVersionInfo();

        return view('version.index', compact('changelog', 'currentVersion'));
    }
}
