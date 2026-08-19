<?php

namespace App\Http\Controllers;

use App\Models\RequestLog;
use App\Models\ServerInstance;
use Illuminate\Support\Facades\Cache;

/**
 * Dashboard Controller
 * 
 * Displays server status dashboard with real-time metrics,
 * server information, and request statistics.
 */
class DashboardController extends Controller
{
    /**
     * Display the server status dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $servers = ServerInstance::withCount('requestLogs')->get();
        $recentLogs = RequestLog::with('server')->latest()->limit(20)->get();
        $totalRequests = RequestLog::count();
        $avgResponseTime = RequestLog::avg('response_time');
        $currentAlgorithm = Cache::get('current_algorithm', 'round_robin');

        return view('dashboard', compact(
            'servers', 'recentLogs', 'totalRequests',
            'avgResponseTime', 'currentAlgorithm'
        ));
    }
}
