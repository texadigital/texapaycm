<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/enable-mobile-api', function () {
    try {
        $setting = DB::table('admin_settings')
            ->where('key', 'mobile_api_enabled')
            ->first();

        if ($setting) {
            DB::table('admin_settings')
                ->where('key', 'mobile_api_enabled')
                ->update(['value' => '1']);
        } else {
            DB::table('admin_settings')->insert([
                'key' => 'mobile_api_enabled',
                'value' => '1',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        return 'Mobile API has been enabled successfully. This route will now be disabled.';
    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
})->middleware('web');
