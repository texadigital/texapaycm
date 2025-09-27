<?php

namespace App\Console\Commands;

use App\Models\Transfer;
use App\Services\SafeHaven;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReconcilePayouts extends Command
{
    protected $signature = 'texapay:reconcile-payouts {--limit=50}';
    protected $description = 'Reconcile pending NGN payouts by polling Safe Haven and updating statuses';

    public function handle(SafeHaven $safeHaven): int
    {
        $limit = (int) $this->option('limit');

        $pending = Transfer::query()
            ->whereIn('status', ['payout_pending'])
            ->orWhere('payout_status', 'pending')
            ->orderBy('id', 'asc')
            ->limit($limit)
            ->get();

        if ($pending->isEmpty()) {
            $this->info('No pending payouts to reconcile.');
            return self::SUCCESS;
        }

        foreach ($pending as $t) {
            $sessionId = $t->payout_ref ?: $t->name_enquiry_reference;
            if (!$sessionId) {
                $this->warn("Transfer {$t->id} has no sessionId; skipping");
                continue;
            }

            $resp = $safeHaven->payoutStatus($sessionId);
            $timeline = is_array($t->timeline) ? $t->timeline : [];
            $timeline[] = ['state' => 'ngn_payout_status_check', 'at' => now()->toIso8601String(), 'status' => $resp['status']];

            $update = ['timeline' => $timeline];
            if ($resp['status'] === 'success') {
                $update['status'] = 'payout_success';
                $update['payout_status'] = 'success';
                $update['payout_completed_at'] = now();
                Log::info('Reconciled payout success', ['transfer_id' => $t->id, 'sessionId' => $sessionId]);
            } elseif ($resp['status'] === 'failed') {
                $update['status'] = 'failed';
                $update['payout_status'] = 'failed';
                Log::warning('Reconciled payout failed', ['transfer_id' => $t->id, 'sessionId' => $sessionId, 'raw' => $resp['raw'] ?? null]);
            } else {
                $update['payout_status'] = 'pending';
                Log::info('Payout still pending', ['transfer_id' => $t->id, 'sessionId' => $sessionId]);
            }
            $t->update($update);
        }

        $this->info('Reconciliation run complete.');
        return self::SUCCESS;
    }
}
