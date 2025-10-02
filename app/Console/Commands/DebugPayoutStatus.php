<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transfer;
use App\Services\SafeHaven;

class DebugPayoutStatus extends Command
{
    protected $signature = 'texapay:debug-payout {--id=} {--ref=} {--apply}';

    protected $description = 'Print SafeHaven payout status for a transfer or session ref; pass --apply to persist success/failure to DB';

    public function handle(SafeHaven $safeHaven): int
    {
        $id = $this->option('id');
        $ref = $this->option('ref');
        $apply = (bool) $this->option('apply');

        $t = null;
        if (!$ref && $id) {
            $t = Transfer::find((int) $id);
            if (!$t) { $this->error('Transfer not found'); return self::FAILURE; }
            $ref = $t->payout_ref ?: $t->name_enquiry_reference;
            if (!$ref) { $this->error('No payout_ref or name_enquiry_reference on transfer'); return self::FAILURE; }
            $this->info("Using transfer #{$t->id} sessionId={$ref}");
        }
        if (!$ref) { $this->error('Provide --id or --ref'); return self::FAILURE; }

        $resp = $safeHaven->payoutStatus($ref);
        $this->line('Provider response:');
        $this->line(json_encode($resp, JSON_PRETTY_PRINT));

        if ($apply && $t) {
            $status = $resp['status'] ?? 'pending';
            if ($status === 'success') {
                $t->update([
                    'payout_status' => 'success',
                    'status' => 'payout_success',
                    'payout_completed_at' => now(),
                ]);
                $this->info('Applied success to DB.');
            } elseif ($status === 'failed') {
                $t->update([
                    'payout_status' => 'failed',
                    'status' => 'failed',
                ]);
                $this->info('Applied failure to DB.');
            }
        }
        return self::SUCCESS;
    }
}
