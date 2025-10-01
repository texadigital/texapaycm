<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EnableMobileApi extends Command
{
    protected $signature = 'mobile:enable';
    protected $description = 'Enable the mobile API feature flag';

    public function handle()
    {
        try {
            $result = DB::table('admin_settings')
                ->updateOrInsert(
                    ['key' => 'mobile_api_enabled'],
                    ['value' => '1', 'updated_at' => now()]
                );

            $this->info('✅ Mobile API has been enabled successfully!');
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }
}
