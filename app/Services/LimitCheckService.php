<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserLimit;
use App\Models\DailyTransactionSummary;
use App\Models\Transfer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class LimitCheckService
{
    /**
     * Check if a user can make a transaction
     */
    public function canUserTransact(User $user, int $amount): array
    {
        try {
            // Feature flag for KYC-based gating
            $kycEnabled = (bool) \App\Models\AdminSetting::getValue('kyc_enabled', false);

            if (!$kycEnabled) {
                // Legacy behavior
                return $user->canMakeTransaction($amount);
            }

            // Read KYC caps by level from AdminSetting with sane defaults
            $level = (int) ($user->kyc_level ?? 0);
            $caps = $this->getKycCapsForLevel($level);

            // Pull existing per-user limits to combine (min of both systems)
            $limits = $user->getOrCreateLimits();
            $dailyUsage = DailyTransactionSummary::getDailyUsage($user->id);
            $monthlyUsage = DailyTransactionSummary::getMonthlyUsage($user->id);

            // Effective caps (amount only, counts still from per-user limits)
            $effectiveDailyCap = min((int) $limits->daily_limit_xaf, (int) $caps['daily_cap_xaf']);
            $effectiveMonthlyCap = min((int) $limits->monthly_limit_xaf, (int) $caps['monthly_cap_xaf']);

            // Per-transaction cap check first
            if ($amount > (int) $caps['per_tx_cap_xaf']) {
                return [
                    'can_transact' => false,
                    'reason' => $level === 0 ? 'KYC required to send this amount.' : 'Per-transaction cap exceeded.',
                    'limit_type' => 'kyc_per_tx',
                    'limit' => (int) $caps['per_tx_cap_xaf'],
                ];
            }

            // Daily amount cap
            if (($dailyUsage['amount'] + $amount) > $effectiveDailyCap) {
                return [
                    'can_transact' => false,
                    'reason' => $level === 0 ? 'KYC required: daily cap exceeded.' : 'Daily cap exceeded.',
                    'limit_type' => 'kyc_daily_amount',
                    'current_usage' => $dailyUsage['amount'],
                    'limit' => $effectiveDailyCap,
                    'remaining' => max(0, $effectiveDailyCap - $dailyUsage['amount'])
                ];
            }

            // Monthly amount cap
            if (($monthlyUsage['total_amount'] + $amount) > $effectiveMonthlyCap) {
                return [
                    'can_transact' => false,
                    'reason' => $level === 0 ? 'KYC required: monthly cap exceeded.' : 'Monthly cap exceeded.',
                    'limit_type' => 'kyc_monthly_amount',
                    'current_usage' => $monthlyUsage['total_amount'],
                    'limit' => $effectiveMonthlyCap,
                    'remaining' => max(0, $effectiveMonthlyCap - $monthlyUsage['total_amount'])
                ];
            }

            // Fall back to existing count-based checks via legacy method for simplicity
            $legacy = $user->canMakeTransaction($amount);
            if (!$legacy['can_transact']) {
                return $legacy;
            }

            return [
                'can_transact' => true,
                'daily_remaining' => max(0, $effectiveDailyCap - $dailyUsage['amount']),
                'monthly_remaining' => max(0, $effectiveMonthlyCap - $monthlyUsage['total_amount']),
            ];
        } catch (\Exception $e) {
            Log::error('Error checking user transaction limits', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);

            return [
                'can_transact' => false,
                'reason' => 'Unable to verify transaction limits. Please try again.',
                'limit_type' => 'system_error'
            ];
        }
    }

    /**
     * Get admin-configured caps by KYC level with defaults in XAF.
     */
    protected function getKycCapsForLevel(int $level): array
    {
        $prefix = $level === 1 ? 'kyc.level1.' : 'kyc.level0.';
        $defaults = $level === 1
            ? ['per_tx_cap_xaf' => 2000000, 'daily_cap_xaf' => 5000000, 'monthly_cap_xaf' => 50000000]
            : ['per_tx_cap_xaf' => 50000, 'daily_cap_xaf' => 150000, 'monthly_cap_xaf' => 1000000];

        return [
            'per_tx_cap_xaf' => (int) \App\Models\AdminSetting::getValue($prefix . 'per_tx_cap_xaf', $defaults['per_tx_cap_xaf']),
            'daily_cap_xaf' => (int) \App\Models\AdminSetting::getValue($prefix . 'daily_cap_xaf', $defaults['daily_cap_xaf']),
            'monthly_cap_xaf' => (int) \App\Models\AdminSetting::getValue($prefix . 'monthly_cap_xaf', $defaults['monthly_cap_xaf']),
        ];
    }

    /**
     * Record a transaction attempt (successful or failed)
     */
    public function recordTransaction(User $user, int $amount, bool $isSuccessful = false): void
    {
        try {
            $summary = DailyTransactionSummary::getTodaysSummary($user->id);
            $summary->addTransaction($amount, $isSuccessful);

            Log::info('Transaction recorded in limits system', [
                'user_id' => $user->id,
                'amount' => $amount,
                'successful' => $isSuccessful,
                'daily_total' => $summary->total_amount_xaf,
                'daily_count' => $summary->transaction_count
            ]);
        } catch (\Exception $e) {
            Log::error('Error recording transaction in limits system', [
                'user_id' => $user->id,
                'amount' => $amount,
                'successful' => $isSuccessful,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get user's current limit status
     */
    public function getUserLimitStatus(User $user): array
    {
        try {
            $limits = $user->getOrCreateLimits();
            $dailyUsage = DailyTransactionSummary::getDailyUsage($user->id);
            $monthlyUsage = DailyTransactionSummary::getMonthlyUsage($user->id);

            return [
                'limits' => [
                    'daily_limit_xaf' => $limits->daily_limit_xaf,
                    'monthly_limit_xaf' => $limits->monthly_limit_xaf,
                    'daily_count_limit' => $limits->daily_count_limit,
                    'monthly_count_limit' => $limits->monthly_count_limit,
                    'is_active' => $limits->is_active,
                ],
                'usage' => [
                    'daily_amount' => $dailyUsage['amount'],
                    'daily_count' => $dailyUsage['count'],
                    'monthly_amount' => $monthlyUsage['total_amount'],
                    'monthly_count' => $monthlyUsage['total_count'],
                ],
                'remaining' => [
                    'daily_amount' => $limits->getRemainingDailyLimit($dailyUsage['amount']),
                    'monthly_amount' => $limits->getRemainingMonthlyLimit($monthlyUsage['total_amount']),
                    'daily_count' => max(0, $limits->daily_count_limit - $dailyUsage['count']),
                    'monthly_count' => max(0, $limits->monthly_count_limit - $monthlyUsage['total_count']),
                ],
                'utilization' => [
                    'daily_percentage' => $limits->getDailyUtilizationPercentage($dailyUsage['amount']),
                    'monthly_percentage' => $limits->getMonthlyUtilizationPercentage($monthlyUsage['total_amount']),
                    'daily_count_percentage' => $limits->daily_count_limit > 0 ? 
                        min(100, ($dailyUsage['count'] / $limits->daily_count_limit) * 100) : 0,
                    'monthly_count_percentage' => $limits->monthly_count_limit > 0 ? 
                        min(100, ($monthlyUsage['total_count'] / $limits->monthly_count_limit) * 100) : 0,
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error getting user limit status', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return [
                'error' => 'Unable to retrieve limit status',
                'limits' => null,
                'usage' => null,
                'remaining' => null,
                'utilization' => null
            ];
        }
    }

    /**
     * Check if user is approaching limits (warning thresholds)
     */
    public function getLimitWarnings(User $user): array
    {
        $warnings = [];
        
        try {
            $status = $this->getUserLimitStatus($user);
            
            if (isset($status['error'])) {
                return $warnings;
            }

            $utilization = $status['utilization'];

            // Daily amount warning (80% threshold)
            if ($utilization['daily_percentage'] >= 80) {
                $warnings[] = [
                    'type' => 'daily_amount',
                    'level' => $utilization['daily_percentage'] >= 95 ? 'critical' : 'warning',
                    'message' => sprintf(
                        'You have used %.1f%% of your daily transaction limit (%s XAF)',
                        $utilization['daily_percentage'],
                        number_format($status['limits']['daily_limit_xaf'])
                    ),
                    'remaining' => $status['remaining']['daily_amount']
                ];
            }

            // Monthly amount warning (80% threshold)
            if ($utilization['monthly_percentage'] >= 80) {
                $warnings[] = [
                    'type' => 'monthly_amount',
                    'level' => $utilization['monthly_percentage'] >= 95 ? 'critical' : 'warning',
                    'message' => sprintf(
                        'You have used %.1f%% of your monthly transaction limit (%s XAF)',
                        $utilization['monthly_percentage'],
                        number_format($status['limits']['monthly_limit_xaf'])
                    ),
                    'remaining' => $status['remaining']['monthly_amount']
                ];
            }

            // Daily count warning (80% threshold)
            if ($utilization['daily_count_percentage'] >= 80) {
                $warnings[] = [
                    'type' => 'daily_count',
                    'level' => $utilization['daily_count_percentage'] >= 95 ? 'critical' : 'warning',
                    'message' => sprintf(
                        'You have made %d out of %d allowed daily transactions',
                        $status['usage']['daily_count'],
                        $status['limits']['daily_count_limit']
                    ),
                    'remaining' => $status['remaining']['daily_count']
                ];
            }

            // Monthly count warning (80% threshold)
            if ($utilization['monthly_count_percentage'] >= 80) {
                $warnings[] = [
                    'type' => 'monthly_count',
                    'level' => $utilization['monthly_count_percentage'] >= 95 ? 'critical' : 'warning',
                    'message' => sprintf(
                        'You have made %d out of %d allowed monthly transactions',
                        $status['usage']['monthly_count'],
                        $status['limits']['monthly_count_limit']
                    ),
                    'remaining' => $status['remaining']['monthly_count']
                ];
            }

        } catch (\Exception $e) {
            Log::error('Error checking limit warnings', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }

        return $warnings;
    }

    /**
     * Update transaction summary when a transfer status changes
     */
    public function updateTransactionStatus(Transfer $transfer, string $oldStatus, string $newStatus): void
    {
        try {
            // Only update if status changed from non-successful to successful
            $wasSuccessful = in_array($oldStatus, ['payout_success', 'payin_success']);
            $isSuccessful = in_array($newStatus, ['payout_success', 'payin_success']);

            if (!$wasSuccessful && $isSuccessful) {
                // Transaction became successful
                $date = Carbon::parse($transfer->created_at)->toDateString();
                $summary = DailyTransactionSummary::firstOrCreate([
                    'user_id' => $transfer->user_id,
                    'transaction_date' => $date,
                ]);

                $summary->increment('successful_amount_xaf', $transfer->amount_xaf);
                $summary->increment('successful_count');

                Log::info('Transaction status updated to successful', [
                    'transfer_id' => $transfer->id,
                    'user_id' => $transfer->user_id,
                    'amount' => $transfer->amount_xaf,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error updating transaction status in limits system', [
                'transfer_id' => $transfer->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get user statistics for admin dashboard
     */
    public function getUserStatistics(User $user, int $days = 30): array
    {
        try {
            return DailyTransactionSummary::getUserStats($user->id, $days);
        } catch (\Exception $e) {
            Log::error('Error getting user statistics', [
                'user_id' => $user->id,
                'days' => $days,
                'error' => $e->getMessage()
            ]);

            return [
                'error' => 'Unable to retrieve user statistics',
                'period_days' => $days,
                'total_amount' => 0,
                'total_count' => 0,
                'successful_amount' => 0,
                'successful_count' => 0,
                'success_rate' => 0,
                'average_daily_amount' => 0,
                'average_transaction_amount' => 0,
                'active_days' => 0,
            ];
        }
    }

    /**
     * Record a completed transfer in an idempotent way so it only counts once.
     * Returns true if this call performed the recording, false if it was already recorded.
     */
    public function recordCompletedTransferOnce(Transfer $transfer): bool
    {
        try {
            $cacheKey = 'limits_recorded:transfer:' . $transfer->id;

            // Cache::add returns true only if the key did not exist
            if (!Cache::add($cacheKey, true, now()->addDays(7))) {
                Log::info('Limit recording skipped (already recorded)', [
                    'transfer_id' => $transfer->id,
                    'user_id' => $transfer->user_id,
                ]);
                return false;
            }

            $this->recordTransaction($transfer->user, (int) $transfer->amount_xaf, true);

            Log::info('Limit recording completed (idempotent)', [
                'transfer_id' => $transfer->id,
                'user_id' => $transfer->user_id,
                'amount' => (int) $transfer->amount_xaf,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error in recordCompletedTransferOnce', [
                'transfer_id' => $transfer->id,
                'user_id' => $transfer->user_id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
