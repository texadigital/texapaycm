<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Mobile JSON API Routes (Non-breaking)
|--------------------------------------------------------------------------
| Feature-gated via AdminSetting key: mobile_api_enabled (default false)
| Auth model: web session cookies (no token infra added). Mobile apps should
| maintain session cookie after /auth/login.
*/

Route::middleware(['web','throttle:api'])->prefix('mobile')->group(function () {
    // Health & feature gate
    Route::get('/feature', function () {
        $enabled = (bool) \App\Models\AdminSetting::getValue('mobile_api_enabled', false);
        return response()->json(['enabled' => $enabled]);
    });

    // Auth - session cookie based
    Route::post('/auth/login', [\App\Http\Controllers\Api\AuthController::class, 'login'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('api.mobile.auth.login');
    Route::post('/auth/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('api.mobile.auth.logout');

    // Public banks endpoints (reuse existing logic)
    Route::get('/banks', [\App\Http\Controllers\BankController::class, 'list'])->name('api.mobile.banks');
    Route::get('/banks/favorites', [\App\Http\Controllers\BankController::class, 'favorites'])->name('api.mobile.banks.favorites');
    Route::post('/banks/suggest', [\App\Http\Controllers\BankController::class, 'suggest'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('api.mobile.banks.suggest');

    // Authenticated routes
    Route::middleware(['auth'])->group(function () {
        // Dashboard
        Route::get('/dashboard', [\App\Http\Controllers\Api\DashboardController::class, 'summary'])->name('api.mobile.dashboard');

        // KYC (reuse existing Smile ID controller)
        Route::post('/kyc/smileid/start', [\App\Http\Controllers\Kyc\SmileIdController::class, 'start'])
            ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
            ->name('api.mobile.kyc.smileid.start');
        Route::post('/kyc/smileid/web-token', [\App\Http\Controllers\Kyc\SmileIdController::class, 'webToken'])
            ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
            ->name('api.mobile.kyc.smileid.web_token');
        Route::get('/kyc/status', [\App\Http\Controllers\Kyc\KycController::class, 'status'])->name('api.mobile.kyc.status');

        // Transfers JSON orchestration
        Route::get('/transfers', [\App\Http\Controllers\Api\TransfersController::class, 'index'])->name('api.mobile.transfers.index');
        Route::get('/transfers/{transfer}', [\App\Http\Controllers\Api\TransfersController::class, 'show'])->name('api.mobile.transfers.show');
        Route::post('/transfers/name-enquiry', [\App\Http\Controllers\Api\TransfersController::class, 'nameEnquiry'])
            ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
            ->middleware(['throttle:20,1'])
            ->name('api.mobile.transfers.name_enquiry');
        Route::post('/transfers/quote', [\App\Http\Controllers\Api\TransfersController::class, 'quote'])
            ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
            ->middleware(['throttle:20,1', 'check.limits'])
            ->name('api.mobile.transfers.quote');
        Route::post('/transfers/confirm', [\App\Http\Controllers\Api\TransfersController::class, 'confirm'])
            ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
            ->middleware(['throttle:20,1'])
            ->name('api.mobile.transfers.confirm');

        Route::get('/transfers/{transfer}/timeline', [\App\Http\Controllers\Api\TransfersController::class, 'timeline'])->name('api.mobile.transfers.timeline');
        Route::get('/transfers/{transfer}/receipt-url', [\App\Http\Controllers\Api\TransfersController::class, 'receiptUrl'])->name('api.mobile.transfers.receipt_url');

        // Pricing & Limits
        Route::get('/pricing/limits', [\App\Http\Controllers\Api\PricingController::class, 'limits'])->name('api.mobile.pricing.limits');
        Route::get('/pricing/rate-preview', [\App\Http\Controllers\Api\PricingController::class, 'preview'])->name('api.mobile.pricing.preview');

        // Profile
        Route::get('/profile', [\App\Http\Controllers\Api\ProfileController::class, 'show'])->name('api.mobile.profile.show');

        // Policies
        Route::get('/policies', [\App\Http\Controllers\Api\PoliciesController::class, 'index'])->name('api.mobile.policies');

        // Support (read-only help)
        Route::get('/support/help', [\App\Http\Controllers\Api\SupportController::class, 'help'])->name('api.mobile.support.help');
    });
});
