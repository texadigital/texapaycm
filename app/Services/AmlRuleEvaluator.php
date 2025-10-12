<?php

namespace App\Services;

use App\Models\AmlAlert;
use App\Models\AmlRule;
use App\Models\Transfer;
use Illuminate\Support\Facades\Log;

class AmlRuleEvaluator
{
    /**
     * Evaluate active AML rules against a transfer and create alerts as needed.
     * $phase can be 'payin_success', 'payout_success', etc.
     */
    public function evaluateTransfer(Transfer $transfer, string $phase = 'realtime'): array
    {
        $alerts = [];
        try {
            $rules = AmlRule::query()->where('is_active', true)->get();
            foreach ($rules as $rule) {
                if ($this->ruleMatches($rule, $transfer)) {
                    $alert = AmlAlert::create([
                        'user_id' => $transfer->user_id,
                        'transfer_id' => $transfer->id,
                        'rule_key' => $rule->key,
                        'severity' => $rule->severity ?? 'medium',
                        'status' => 'open',
                        'context' => [
                            'phase' => $phase,
                            'expression' => $rule->expression,
                            'thresholds' => $rule->thresholds,
                        ],
                    ]);
                    $alerts[] = $alert->id;

                    // Email alert to compliance
                    try {
                        app(\App\Services\ComplianceAlertService::class)
                            ->send('AML Alert Created', [
                                'alert_id' => $alert->id,
                                'rule_key' => $rule->key,
                                'severity' => $rule->severity,
                                'user_id' => $transfer->user_id,
                                'transfer_id' => $transfer->id,
                                'phase' => $phase,
                            ]);
                    } catch (\Throwable $e) { Log::warning('Compliance alert email failed (alert)', ['e' => $e->getMessage()]); }

                    // Auto-create STR draft when enabled and severity warrants
                    try {
                        $autoStr = (bool) \App\Models\AdminSetting::getValue('aml.str.auto_create_enabled', true);
                        if ($autoStr && in_array(($rule->severity ?? 'medium'), ['high','critical'], true)) {
                            $str = \App\Models\AmlStr::create([
                                'user_id' => $transfer->user_id,
                                'transfer_id' => $transfer->id,
                                'reason' => 'Rule match: ' . $rule->key,
                                'status' => 'draft',
                                'payload' => [
                                    'rule' => $rule->only(['key','name','severity','expression']),
                                    'transfer_id' => $transfer->id,
                                ],
                            ]);

                            // Email STR draft creation
                            app(\App\Services\ComplianceAlertService::class)
                                ->send('STR Draft Created', [
                                    'str_id' => $str->id,
                                    'user_id' => $transfer->user_id,
                                    'transfer_id' => $transfer->id,
                                    'reason' => $str->reason,
                                ]);
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Failed to auto-create STR', ['error' => $e->getMessage()]);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('AML rule evaluation failed', [
                'transfer_id' => $transfer->id,
                'error' => $e->getMessage(),
            ]);
        }
        return $alerts;
    }

    /**
     * Minimal v1 evaluator supporting basic keys:
     * - expression.amount_xaf_gt: int (match if transfer amount_xaf > value)
     * - expression.min_kyc_level: int (match if user's kyc_level < value)
     */
    protected function ruleMatches(AmlRule $rule, Transfer $transfer): bool
    {
        $expr = (array) ($rule->expression ?? []);

        // amount_xaf_gt
        if (isset($expr['amount_xaf_gt'])) {
            if ((int) $transfer->amount_xaf <= (int) $expr['amount_xaf_gt']) {
                return false;
            }
        }

        // min_kyc_level
        if (isset($expr['min_kyc_level'])) {
            $user = $transfer->user;
            $kycLevel = (int) ($user->kyc_level ?? 0);
            if ($kycLevel >= (int) $expr['min_kyc_level']) {
                return false;
            }
        }

        // amount_ngn_gt (receive_ngn_minor is in minor units)
        if (isset($expr['amount_ngn_gt'])) {
            $amountNgn = ((int) $transfer->receive_ngn_minor) / 100.0;
            if ($amountNgn <= (float) $expr['amount_ngn_gt']) {
                return false;
            }
        }

        // fx_usd_gt: compute USD equivalent of amount_xaf using usd_to_xaf snapshot
        if (isset($expr['fx_usd_gt'])) {
            $usdToXaf = (float) $transfer->usd_to_xaf;
            if ($usdToXaf > 0) {
                $usdEquiv = ((float) $transfer->amount_xaf) / $usdToXaf;
                if ($usdEquiv <= (float) $expr['fx_usd_gt']) {
                    return false;
                }
            }
        }

        // daily_txn_count_gt: count user's transfers in last 24h
        if (isset($expr['daily_txn_count_gt'])) {
            $since = now()->subDay();
            $count = \App\Models\Transfer::query()
                ->where('user_id', $transfer->user_id)
                ->where('created_at', '>=', $since)
                ->count();
            if ($count <= (int) $expr['daily_txn_count_gt']) {
                return false;
            }
        }

        // dormant_days_gt: if the previous successful transfer was older than threshold days
        if (isset($expr['dormant_days_gt'])) {
            $prev = \App\Models\Transfer::query()
                ->where('user_id', $transfer->user_id)
                ->whereIn('status', ['completed','payout_success'])
                ->where('id', '!=', $transfer->id)
                ->latest('created_at')
                ->first();
            if ($prev) {
                $days = $prev->created_at->diffInDays(now());
                if ($days <= (int) $expr['dormant_days_gt']) {
                    return false;
                }
            } else {
                // No previous transfer: treat as not dormant breach
                return false;
            }
        }

        return true;
    }
}
