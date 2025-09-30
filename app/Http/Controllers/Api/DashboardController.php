<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function summary(Request $request)
    {
        $userId = Auth::id();

        $todayStart = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();

        $today = Transfer::query()
            ->where('user_id', $userId)
            ->where('created_at', '>=', $todayStart)
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(amount_xaf),0) as sum')
            ->first();

        $month = Transfer::query()
            ->where('user_id', $userId)
            ->where('created_at', '>=', $monthStart)
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(amount_xaf),0) as sum')
            ->first();

        $recent = Transfer::query()
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id','status','amount_xaf','created_at']);

        $user = $request->user();

        return response()->json([
            'kyc' => [
                'status' => $user->kyc_status ?? 'unverified',
                'level' => (int) ($user->kyc_level ?? 0),
            ],
            'today' => [
                'count' => (int) ($today->cnt ?? 0),
                'totalXaf' => (int) ($today->sum ?? 0),
            ],
            'month' => [
                'count' => (int) ($month->cnt ?? 0),
                'totalXaf' => (int) ($month->sum ?? 0),
            ],
            'recentTransfers' => $recent->map(fn ($t) => [
                'id' => $t->id,
                'status' => $t->status,
                'amountXaf' => (int) $t->amount_xaf,
                'createdAt' => $t->created_at?->toISOString(),
            ])->toArray(),
        ]);
    }
}
