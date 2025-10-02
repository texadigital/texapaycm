<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SafeHaven;

class CheckSafeHaven extends Command
{
    protected $signature = 'texapay:safehaven:check';
    protected $description = 'Check SafeHaven auth/health and print diagnostic info';

    public function handle(SafeHaven $safeHaven): int
    {
        $info = $safeHaven->checkAuth();
        $this->line(json_encode($info, JSON_PRETTY_PRINT));
        if (!($info['ok'] ?? false)) {
            $this->error('SafeHaven auth not OK');
            return self::FAILURE;
        }
        $this->info('SafeHaven auth OK');
        return self::SUCCESS;
    }
}
