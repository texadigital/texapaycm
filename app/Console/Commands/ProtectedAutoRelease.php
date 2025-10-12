<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProtectedTransaction;
use App\Models\ProtectedAuditLog;
use App\Services\ProtectedFeeService;
use App\Services\SafeHaven;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

class ProtectedAutoRelease extends Command
{
    protected $signature = 'protected:auto-release {--limit=100}';
    protected $description = 'Auto-release protected transactions past auto_release_at';

    public function handle(SafeHaven $safeHaven, ProtectedFeeService $feeSvc): int
    {
        $limit = (int) $this->option('limit');
        $now = now();

        $txns = ProtectedTransaction::query()
            ->where('escrow_state', ProtectedTransaction::STATE_AWAITING)
            ->whereNotNull('auto_release_at')
            ->where('auto_release_at', '<=', $now)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $count = 0;
        foreach ($txns as $txn) {
            try {
                DB::transaction(function () use ($txn, $safeHaven, $feeSvc, &$count) {
                    // Ensure fee snapshot
                    if (!$txn->fee_ngn_minor || !$txn->fee_rule_version) {
                        $calc = $feeSvc->calculate($txn->amount_ngn_minor);
                        $txn->fee_ngn_minor = $calc['fee_ngn_minor'];
                        $txn->fee_rule_version = $calc['fee_rule_version'];
                        $txn->fee_components = $calc['fee_components'];
                    }
                    $netMinor = (int) max(0, $txn->amount_ngn_minor - $txn->fee_ngn_minor);
                    $paymentRef = $txn->payout_ref ?: ('protpay_' . bin2hex(random_bytes(10)));

                    $resp = $safeHaven->payout([
                        'bankCode' => $txn->receiver_bank_code,
                        'accountNumber' => $txn->receiver_account_number,
                        'accountName' => $txn->receiver_account_name,
                        'amountNgn' => $netMinor / 100.0,
                        'narration' => 'Texa Protected auto-release',
                        'paymentReference' => $paymentRef,
                    ]);

                    $from = $txn->escrow_state;
                    $txn->payout_ref = $paymentRef;
                    $txn->payout_status = $resp['status'] ?? 'initiated';
                    $txn->payout_attempted_at = now();
                    $txn->escrow_state = ProtectedTransaction::STATE_RELEASED;
                    $txn->appendTimeline(['event' => 'auto_release', 'net_minor' => $netMinor, 'resp' => $resp]);
                    $txn->save();

                    ProtectedAuditLog::create([
                        'protected_transaction_id' => $txn->id,
                        'actor_type' => 'system',
                        'actor_id' => null,
                        'from_state' => $from,
                        'to_state' => ProtectedTransaction::STATE_RELEASED,
                        'at' => now(),
                        'reason' => 'auto_release',
                        'meta' => ['payout_ref' => $paymentRef],
                    ]);
                    $count++;
                });

                // Notify buyer about auto-release
                try {
                    app(NotificationService::class)->dispatchUserNotification('protected.auto_release', $txn->buyer, [
                        'funding_ref' => $txn->funding_ref,
                        'amount_ngn_minor' => $txn->amount_ngn_minor,
                        'net_ngn_minor' => (int) max(0, $txn->amount_ngn_minor - ($txn->fee_ngn_minor ?? 0)),
                        'payout_ref' => $txn->payout_ref,
                    ]);
                } catch (\Throwable $e) { \Log::warning('Protected auto-release notification failed', ['id'=>$txn->id,'err'=>$e->getMessage()]); }
            } catch (\Throwable $e) {
                \Log::error('Protected auto-release failed', ['id' => $txn->id, 'error' => $e->getMessage()]);
            }
        }

        $this->info("Auto-released {$count} transactions.");
        return self::SUCCESS;
    }
}
