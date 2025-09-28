<?php

namespace App\Http\Controllers;

use App\Services\LimitCheckService;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    protected $limitCheckService;

    public function __construct(LimitCheckService $limitCheckService)
    {
        $this->limitCheckService = $limitCheckService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $limitStatus = $this->limitCheckService->getUserLimitStatus($user);
        $limitWarnings = $this->limitCheckService->getLimitWarnings($user);
        $userStats = $this->limitCheckService->getUserStatistics($user, 30);

        return view('profile.index', compact('user', 'limitStatus', 'limitWarnings', 'userStats'));
    }

    public function limits(Request $request)
    {
        $user = $request->user();
        $limitStatus = $this->limitCheckService->getUserLimitStatus($user);
        $limitWarnings = $this->limitCheckService->getLimitWarnings($user);
        $userStats7Days = $this->limitCheckService->getUserStatistics($user, 7);
        $userStats30Days = $this->limitCheckService->getUserStatistics($user, 30);
        $userStats90Days = $this->limitCheckService->getUserStatistics($user, 90);

        return view('profile.limits', compact(
            'user', 
            'limitStatus', 
            'limitWarnings', 
            'userStats7Days',
            'userStats30Days',
            'userStats90Days'
        ));
    }
}
