<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\InboxMessage;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\User;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __construct(
        private readonly AnalyticsService $analytics,
    ) {
        $this->middleware(['auth', 'agency', 'role:owner|admin']);
    }

    /**
     * Show admin dashboard.
     */
    public function index(Request $request): View
    {
        $stats = Cache::remember('admin_dashboard_stats', 300, function () {
            return [
                'total_agencies' => Agency::count(),
                'active_agencies' => Agency::where('status', 'active')->count(),
                'total_users' => User::count(),
                'total_social_posts' => SocialPost::count(),
                'published_posts' => SocialPost::where('status', 'published')->count(),
                'failed_posts' => SocialPost::where('status', 'failed')->count(),
                'pending_posts' => SocialPost::where('status', 'scheduled')->count(),
                'total_social_accounts' => SocialAccount::where('is_active', true)->count(),
                'total_inbox_messages' => InboxMessage::count(),
                'unread_inbox_messages' => InboxMessage::where('status', 'unread')->count(),
                'posts_today' => SocialPost::whereDate('created_at', today())->count(),
                'posts_this_week' => SocialPost::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'posts_this_month' => SocialPost::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            ];
        });

        $recentActivity = Cache::remember('admin_recent_activity', 60, function () {
            return SocialPost::with('agency')
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();
        });

        $failedPosts = SocialPost::where('status', 'failed')
            ->orderBy('failed_at', 'desc')
            ->limit(10)
            ->get();

        $platformStats = SocialPost::selectRaw('platform, status, count(*) as count')
            ->groupBy('platform', 'status')
            ->get()
            ->groupBy('platform');

        return view('admin.dashboard', compact('stats', 'recentActivity', 'failedPosts', 'platformStats'));
    }

    /**
     * Show system health.
     */
    public function health(Request $request): View
    {
        $health = [
            'database' => $this->checkDatabase(),
            'queue' => $this->checkQueue(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
        ];

        return view('admin.health', compact('health'));
    }

    /**
     * Show failed jobs.
     */
    public function failedJobs(Request $request): View
    {
        $failedJobs = DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->paginate(25);

        return view('admin.failed-jobs', compact('failedJobs'));
    }

    /**
     * Retry a failed job.
     */
    public function retryJob(Request $request, string $jobId): \Illuminate\Http\RedirectResponse
    {
        $job = DB::table('failed_jobs')->where('id', $jobId)->first();

        if (!$job) {
            return back()->with('error', 'Job not found');
        }

        try {
            dispatch(unserialize($job->payload)['data']['command'] ?? null);
            DB::table('failed_jobs')->where('id', $jobId)->delete();
            return back()->with('success', 'Job dispatched for retry');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to retry job: ' . $e->getMessage());
        }
    }

    /**
     * Delete a failed job.
     */
    public function deleteFailedJob(Request $request, string $jobId): \Illuminate\Http\RedirectResponse
    {
        DB::table('failed_jobs')->where('id', $jobId)->delete();
        return back()->with('success', 'Failed job deleted');
    }

    /**
     * Check database connection.
     */
    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $time = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'ok',
                'message' => 'Database connected',
                'response_time_ms' => $time,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check queue status.
     */
    private function checkQueue(): array
    {
        try {
            $pending = DB::table('jobs')->count();
            $failed = DB::table('failed_jobs')->count();

            return [
                'status' => 'ok',
                'message' => 'Queue operational',
                'pending_jobs' => $pending,
                'failed_jobs' => $failed,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache status.
     */
    private function checkCache(): array
    {
        try {
            $key = 'health_check_' . time();
            Cache::put($key, true, 10);
            $value = Cache::get($key);
            Cache::forget($key);

            return $value ? [
                'status' => 'ok',
                'message' => 'Cache operational',
            ] : [
                'status' => 'warning',
                'message' => 'Cache read/write issue',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage status.
     */
    private function checkStorage(): array
    {
        try {
            $path = storage_path('framework/health');
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
            $file = $path . '/check.txt';
            file_put_contents($file, 'ok');
            $value = file_get_contents($file);
            unlink($file);
            rmdir($path);

            return $value === 'ok' ? [
                'status' => 'ok',
                'message' => 'Storage writable',
            ] : [
                'status' => 'warning',
                'message' => 'Storage read/write issue',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }
}
