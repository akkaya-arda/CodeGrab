<?php

namespace App\Http\Controllers;

use App\Models\GmailAccount;
use App\Models\OutlookAccount;
use App\Models\ImapAccount;
use App\Models\GuardFetchLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    public function getSummary()
    {
        
        $gmailTotal = GmailAccount::count();
        $gmailActive = GmailAccount::where('is_active', true)->count();

        $outlookTotal = OutlookAccount::count();
        $outlookActive = OutlookAccount::where('is_active', true)->count();

        $imapTotal = ImapAccount::count();
        $imapActive = ImapAccount::where('is_active', true)->count();

        
        $totalFetches = GuardFetchLog::count();
        $successFetches = GuardFetchLog::where('status', 'success')->count();
        $failedFetches = GuardFetchLog::where('status', 'failed')->count();

        $successRate = $totalFetches > 0 ? round(($successFetches / $totalFetches) * 100, 1) : 0;

        
        $platformData = GuardFetchLog::select('platform', DB::raw('count(*) as total'))
            ->groupBy('platform')
            ->orderBy('total', 'desc')
            ->get();

        
        $providerData = GuardFetchLog::select('account_type', DB::raw('count(*) as total'))
            ->groupBy('account_type')
            ->orderBy('total', 'desc')
            ->get();

        
        $recentLogs = GuardFetchLog::orderBy('created_at', 'desc')->limit(10)->get();

        
        $sevenDaysAgo = now()->subDays(7);
        $dailyTrend = GuardFetchLog::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->where('created_at', '>=', $sevenDaysAgo)
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'accounts' => [
                    'gmail' => ['total' => $gmailTotal, 'active' => $gmailActive],
                    'outlook' => ['total' => $outlookTotal, 'active' => $outlookActive],
                    'imap' => ['total' => $imapTotal, 'active' => $imapActive],
                    'all' => ['total' => $gmailTotal + $outlookTotal + $imapTotal, 'active' => $gmailActive + $outlookActive + $imapActive]
                ],
                'fetches' => [
                    'total' => $totalFetches,
                    'success' => $successFetches,
                    'failed' => $failedFetches,
                    'success_rate' => $successRate
                ],
                'distributions' => [
                    'platforms' => $platformData,
                    'providers' => $providerData,
                    'daily_trend' => $dailyTrend
                ],
                'recent_logs' => $recentLogs
            ]
        ]);
    }

    public function getLogs(Request $request)
    {
        $query = GuardFetchLog::query();

        
        if ($search = $request->query('search')) {
            $query->where(function($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('platform', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%")
                  ->orWhere('account_type', 'like', "%{$search}%");
            });
        }

        
        if ($platform = $request->query('platform')) {
            $query->where('platform', $platform);
        }

        
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        
        if ($accountType = $request->query('account_type')) {
            $query->where('account_type', $accountType);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }
}
