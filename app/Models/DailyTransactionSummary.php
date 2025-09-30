<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class DailyTransactionSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'transaction_date',
        'total_amount_xaf',
        'transaction_count',
        'successful_amount_xaf',
        'successful_count',
        'transaction_details',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'transaction_details' => 'array',
    ];

    /**
     * Get the user that owns the summary
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get or create today's summary for a user
     */
    public static function getTodaysSummary(int $userId): self
    {
        return self::firstOrCreate([
            'user_id' => $userId,
            'transaction_date' => Carbon::today(),
        ]);
    }

    /**
     * Get current month's summary for a user
     */
    public static function getMonthSummary(int $userId, Carbon $date = null): array
    {
        $date = $date ?? Carbon::now();
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        $summaries = self::where('user_id', $userId)
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->get();

        return [
            'total_amount' => $summaries->sum('total_amount_xaf'),
            'total_count' => $summaries->sum('transaction_count'),
            'successful_amount' => $summaries->sum('successful_amount_xaf'),
            'successful_count' => $summaries->sum('successful_count'),
            'days_with_transactions' => $summaries->count(),
        ];
    }

    /**
     * Update summary with new transaction
     */
    public function addTransaction(int $amount, bool $isSuccessful = false): void
    {
        $this->increment('total_amount_xaf', $amount);
        $this->increment('transaction_count');

        if ($isSuccessful) {
            $this->increment('successful_amount_xaf', $amount);
            $this->increment('successful_count');
        }
    }

    /**
     * Get user's current daily usage
     */
    public static function getDailyUsage(int $userId, Carbon $date = null): array
    {
        $date = $date ?? Carbon::today();
        
        $summary = self::where('user_id', $userId)
            ->where('transaction_date', $date)
            ->first();

        if (!$summary) {
            return [
                'amount' => 0,
                'count' => 0,
                'successful_amount' => 0,
                'successful_count' => 0,
            ];
        }

        return [
            'amount' => $summary->total_amount_xaf,
            'count' => $summary->transaction_count,
            'successful_amount' => $summary->successful_amount_xaf,
            'successful_count' => $summary->successful_count,
        ];
    }

    /**
     * Get user's current monthly usage
     */
    public static function getMonthlyUsage(int $userId, Carbon $date = null): array
    {
        return self::getMonthSummary($userId, $date);
    }

    /**
     * Get transaction statistics for a user
     */
    public static function getUserStats(int $userId, int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);
        
        $summaries = self::where('user_id', $userId)
            ->where('transaction_date', '>=', $startDate)
            ->orderBy('transaction_date')
            ->get();

        $totalAmount = $summaries->sum('total_amount_xaf');
        $totalCount = $summaries->sum('transaction_count');
        $successfulAmount = $summaries->sum('successful_amount_xaf');
        $successfulCount = $summaries->sum('successful_count');

        return [
            'period_days' => $days,
            'total_amount' => $totalAmount,
            'total_count' => $totalCount,
            'successful_amount' => $successfulAmount,
            'successful_count' => $successfulCount,
            'success_rate' => $totalCount > 0 ? ($successfulCount / $totalCount) * 100 : 0,
            'average_daily_amount' => $days > 0 ? $totalAmount / $days : 0,
            'average_transaction_amount' => $totalCount > 0 ? $totalAmount / $totalCount : 0,
            'active_days' => $summaries->where('transaction_count', '>', 0)->count(),
        ];
    }

    /**
     * Clean up old summaries (older than specified days)
     */
    public static function cleanup(int $keepDays = 365): int
    {
        $cutoffDate = Carbon::now()->subDays($keepDays);
        
        return self::where('transaction_date', '<', $cutoffDate)->delete();
    }
}
