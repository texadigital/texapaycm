<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transfer;
use App\Services\PawaPay;

class ReconcileTransfers extends Command
{
    protected $signature = 'texapay:reconcile-transfers {--id=} {--limit=50}';

    protected $description = 'Reconcile transfer states by refreshing pay-in from provider and recomputing overall status';

    public function handle(PawaPay $pawaPay): int
    {
        $id = $this->option('id');
        $limit = (int) $this->option('limit');

        $query = Transfer::query();
        if ($id) {
            $query->where('id', (int) $id);
        } else {
            // Focus on recent and potentially inconsistent rows
            $query->orderByDesc('id')->limit($limit);
        }

        $count = 0;
        $updated = 0;

        /** @var Transfer $t */
        foreach ($query->cursor() as $t) {
            $count++;
            $before = [$t->payin_status, $t->payout_status, $t->status];

            // Refresh pay-in if we have a ref and still not final
            if (!empty($t->payin_ref) && !in_array($t->payin_status, ['success','failed','canceled'], true)) {
                $resp = $pawaPay->getPayInStatus($t->payin_ref);
                $new = $resp['status'] ?? null; // pending|success|failed|canceled
                if ($new && $new !== $t->payin_status) {
                    $t->payin_status = $new;
                    if ($new === 'success' && !$t->payin_at) {
                        $t->payin_at = now();
                    }
                    // Append to timeline
                    $timeline = $t->timeline ?? [];
                    $timeline[] = [
                        'at' => now()->toIso8601String(),
                        'state' => 'payin_'.$new,
                        'provider' => 'PAWAPAY',
                        'reference' => $t->payin_ref,
                    ];
                    $t->timeline = $timeline;
                }
            }

            // Recompute overall status
            $overall = $t->status;
            $payin = $t->payin_status; $payout = $t->payout_status;
            if ($payin === 'success' && $payout === 'success') {
                $overall = 'completed';
            } elseif (in_array($payin, ['pending','processing', null], true)) {
                $overall = 'payin_pending';
            } elseif ($payin === 'success' && in_array($payout, [null,'pending','processing'], true)) {
                $overall = 'payout_pending';
            } elseif ($payout === 'failed' || $payin === 'failed') {
                $overall = 'failed';
            }
            if ($overall !== $t->status) {
                $t->status = $overall;
            }

            if ($t->isDirty()) {
                $t->save();
                $updated++;
                $this->info(sprintf('Reconciled transfer #%d: payin=%s payout=%s overall=%s', $t->id, $t->payin_status, $t->payout_status, $t->status));
            }
        }

        $this->line(sprintf('Checked %d transfer(s); updated %d.', $count, $updated));
        return self::SUCCESS;
    }
}
