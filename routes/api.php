<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful as SanctumStateful;

/*
|--------------------------------------------------------------------------
| Mobile JSON API Routes (Non-breaking)
|--------------------------------------------------------------------------
| Feature-gated via AdminSetting key: mobile_api_enabled (default false)
| maintain session cookie after /auth/login.
*/

Route::middleware([
        'throttle:60,1',
        'force.json',]);

// Public mobile endpoints (no auth)
Route::prefix('mobile')->group(function () {
    // Health & feature status endpoint should always be reachable
    Route::get('/feature', function () {
        // Forced enabled for local testing
        return response()->json(['enabled' => true]);
    })->name('api.mobile.feature');
{{ ... }}
        $rates = $oxr->fetchUsdRates();
        return response()->json($rates);
    })->name('api.mobile.health.oxr');

        // Authenticated routes
        Route::middleware(['auth:sanctum', 'jwt.auth.optional'])->prefix('mobile')->group(function () {
        // Dashboard
        Route::get('/dashboard', [\App\Http\Controllers\Api\DashboardController::class, 'summary'])->name('api.mobile.dashboard');

        // KYC (reuse existing Smile ID controller)
        Route::post('/kyc/smileid/start', [\App\Http\Controllers\Kyc\SmileIdController::class, 'start'])
{{ ... }}
        Route::delete('/devices/unregister', [\App\Http\Controllers\Api\DeviceController::class, 'unregister'])->name('api.mobile.devices.unregister');
        Route::get('/devices', [\App\Http\Controllers\Api\DeviceController::class, 'devices'])->name('api.mobile.devices.index');
        Route::post('/devices/test-push', [\App\Http\Controllers\Api\DeviceController::class, 'testPush'])->name('api.mobile.devices.test_push');
        
        // Password Reset (Mobile API)
    Route::post('/auth/forgot-password', [\App\Http\Controllers\PasswordResetController::class, 'apiSendResetCode'])->name('api.mobile.auth.forgot_password');
    Route::post('/auth/reset-password', [\App\Http\Controllers\PasswordResetController::class, 'apiResetPassword'])->name('api.mobile.auth.reset_password');
    // Security: verify PIN by phone (for reset flows)
    Route::post('/security/verify-pin', [\App\Http\Controllers\Api\SecurityController::class, 'verifyPin'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('api.mobile.security.verify_pin');

        // Policies
        Route::get('/policies', [\App\Http\Controllers\Api\PoliciesController::class, 'index'])->name('api.mobile.policies');

        // Account management
{{ ... }}
            ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
            ->name('api.mobile.account.delete');

        // Support (read-only help)
        Route::get('/support/help', [\App\Http\Controllers\Api\SupportController::class, 'help'])->name('api.mobile.support.help');
        Route::post('/support/contact', [\App\Http\Controllers\Api\SupportController::class, 'contact'])
            ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
            ->name('api.mobile.support.contact');
        Route::get('/support/tickets', [\App\Http\Controllers\Api\SupportController::class, 'index'])->name('api.mobile.support.tickets');
        Route::get('/support/tickets/{ticket}', [\App\Http\Controllers\Api\SupportController::class, 'show'])->name('api.mobile.support.tickets.show');
        Route::post('/support/tickets/{ticket}/reply', [\App\Http\Controllers\Api\SupportController::class, 'reply'])
            ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
            ->name('api.mobile.support.tickets.reply');
        }); // end auth group

}); // end outer mobile group
