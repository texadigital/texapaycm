<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transfer;
use App\Services\PawaPay;

class DebugPayinStatus extends Command
{
    protected $signature = 'texapay:debug-payin {--id=} {--ref=}';

    protected $description = 'Print the provider pay-in status (PawaPay) for a given transfer id or deposit reference';

    public function handle(PawaPay $pawaPay): int
    {
        $id = $this->option('id');
        $ref = $this->option('ref');

        if (!$ref && $id) {
            $t = Transfer::find((int) $id);
            if (!$t) {
                $this->error('Transfer not found: '.$id);
                return self::FAILURE;
            }
            if (!$t->payin_ref) {
                $this->error('Transfer has no payin_ref');
                return self::FAILURE;
            }
            $ref = $t->payin_ref;
            $this->info("Using transfer #{$t->id} payin_ref={$ref}");
        }

        if (!$ref) {
            $this->error('Provide --id or --ref');
            return self::FAILURE;
        }

        $resp = $pawaPay->getPayInStatus($ref);
        $this->line('Provider response:');
        $this->line(json_encode($resp, JSON_PRETTY_PRINT));
        return self::SUCCESS;
    }
}
