<?php

namespace App\Http\Controllers;

use App\Models\Transfer;
use App\Services\LimitCheckService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $limitCheckService;

    public function __construct(LimitCheckService $limitCheckService)
    {
        $this->limitCheckService = $limitCheckService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $transfers = Transfer::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // Get user's limit status and warnings
        $limitStatus = $this->limitCheckService->getUserLimitStatus($user);
        $limitWarnings = $this->limitCheckService->getLimitWarnings($user);
        $userStats = $this->limitCheckService->getUserStatistics($user, 30);

        return view('dashboard.index', compact(
            'user', 
            'transfers', 
            'limitStatus', 
            'limitWarnings', 
            'userStats'
        ));
    }
}
