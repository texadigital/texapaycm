<?php

namespace App\Http\Middleware;

use App\Models\AdminSetting;
use Closure;
use Illuminate\Http\Request;

class MobileFeatureGate
{
    public function handle(Request $request, Closure $next)
    {
        $enabled = (bool) AdminSetting::getValue('mobile_api_enabled', false);
        if (!$enabled) {
            return response()->json([
                'success' => false,
                'code' => 'FEATURE_DISABLED',
                'message' => 'Mobile API is disabled',
            ], 403);
        }
        return $next($request);
    }
}
