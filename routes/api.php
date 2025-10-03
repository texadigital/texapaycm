<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful as SanctumStateful;

/*
|--------------------------------------------------------------------------
| Mobile JSON API Routes (Non-breaking)
|--------------------------------------------------------------------------
| Feature-gated via AdminSetting key: mobile_api_enabled (default false)
| Auth model: web session cookies (no token infra added). Mobile apps should
| maintain session cookie after /auth/login.
*/

Route::middleware([
        'throttle:60,1',
        'force.json',
        'idempotency',
    ])
    ->prefix('mobile')
    ->group(function () {
    // Health & feature status endpoint should always be reachable
    Route::get('/feature', function () {
        // Forced enabled for local testing
        return response()->json(['enabled' => true]);
    })->name('api.mobile.feature');

    // Removed mobile.feature gate for local testing (inline routes below)
        // Auth - JWT based
        Route::post('/auth/login', [\App\Http\Controllers\Api\TokenAuthController::class, 'login'])
            ->name('api.mobile.auth.login');
        Route::post('/auth/refresh', [\App\Http\Controllers\Api\TokenAuthController::class, 'refresh'])
            ->name('api.mobile.auth.refresh');
        Route::post('/auth/logout', [\App\Http\Controllers\Api\TokenAuthController::class, 'logout'])
            ->name('api.mobile.auth.logout');
        Route::get('/auth/me', [\App\Http\Controllers\Api\TokenAuthController::class, 'me'])
            ->middleware('auth.jwt')
            ->name('api.mobile.auth.me');

        // Registration can remain public
        Route::post('/auth/register', [\App\Http\Controllers\Api\AuthController::class, 'register'])
            ->name('api.mobile.auth.register');

    // Public banks endpoints (reuse existing logic)
    Route::get('/banks', [\App\Http\Controllers\BankController::class, 'list'])->name('api.mobile.banks');
    Route::get('/banks/favorites', [\App\Http\Controllers\BankController::class, 'favorites'])->name('api.mobile.banks.favorites');
    Route::post('/banks/suggest', [\App\Http\Controllers\BankController::class, 'suggest'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('api.mobile.banks.suggest');

    // Health checks (public for testing)
    Route::get('/health/pawapay', function (\App\Services\PawaPay $pawaPay) {
        return response()->json($pawaPay->checkAuth());
    })->name('api.mobile.health.pawapay');

    Route::get('/health/safehaven', function (\App\Services\SafeHaven $safeHaven) {
        return response()->json($safeHaven->checkAuth());
    })->name('api.mobile.health.safehaven');

    Route::get('/health/safehaven/banks', function (\App\Services\SafeHaven $safeHaven) {
        return response()->json($safeHaven->listBanks());
    })->name('api.mobile.health.safehaven.banks');

    Route::get('/health/oxr', function () {
        /** @var \App\Services\OpenExchangeRates $oxr */
        $oxr = app(\App\Services\OpenExchangeRates::class);
        $rates = $oxr->fetchUsdRates();
        return response()->json($rates);
    })->name('api.mobile.health.oxr');

        // Authenticated routes
        Route::middleware(['auth.jwt'])->group(function () {
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
            ->middleware(['throttle:20,1', 'check.limits'])
            ->name('api.mobile.transfers.confirm');
        Route::post('/transfers/{transfer}/payin/status', [\App\Http\Controllers\Api\TransfersController::class, 'payinStatus'])
            ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
            ->name('api.mobile.transfers.payin_status');
        Route::post('/transfers/{transfer}/payout', [\App\Http\Controllers\Api\TransfersController::class, 'initiatePayout'])
            ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
            ->name('api.mobile.transfers.payout');
        Route::post('/transfers/{transfer}/payout/status', [\App\Http\Controllers\Api\TransfersController::class, 'payoutStatus'])
            ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
            ->name('api.mobile.transfers.payout_status');

        Route::get('/transfers/{transfer}/timeline', [\App\Http\Controllers\Api\TransfersController::class, 'timeline'])->name('api.mobile.transfers.timeline');
        Route::get('/transfers/{transfer}/receipt-url', [\App\Http\Controllers\Api\TransfersController::class, 'receiptUrl'])->name('api.mobile.transfers.receipt_url');
        Route::get('/transfers/{transfer}/receipt.pdf', [\App\Http\Controllers\Api\TransfersController::class, 'receiptPdf'])->name('api.mobile.transfers.receipt_pdf');
        Route::post('/transfers/{transfer}/share-url', [\App\Http\Controllers\Api\TransfersController::class, 'shareLink'])
            ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
            ->name('api.mobile.transfers.share_url');

        // Pricing & Limits
        Route::get('/pricing/limits', [\App\Http\Controllers\Api\PricingController::class, 'limits'])->name('api.mobile.pricing.limits');
        Route::get('/pricing/rate-preview', [\App\Http\Controllers\Api\PricingController::class, 'preview'])->name('api.mobile.pricing.preview');

        // (health endpoints moved above to be public)

        // Profile
        Route::get('/profile', [\App\Http\Controllers\Api\ProfileController::class, 'show'])->name('api.mobile.profile.show');
        // Security
        Route::get('/profile/security', [\App\Http\Controllers\Api\SecurityController::class, 'show'])->name('api.mobile.profile.security.show');
        Route::post('/profile/security/pin', [\App\Http\Controllers\Api\SecurityController::class, 'updatePin'])
            ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
            ->name('api.mobile.profile.security.pin');
        Route::post('/profile/security/password', [\App\Http\Controllers\Api\SecurityController::class, 'updatePassword'])
            ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
            ->name('api.mobile.profile.security.password');

        // Notifications
        Route::get('/profile/notifications', [\App\Http\Controllers\Api\ProfileController::class, 'notifications'])->name('api.mobile.profile.notifications');
        Route::put('/profile/notifications', [\App\Http\Controllers\Api\ProfileController::class, 'updateNotifications'])
            ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
            ->name('api.mobile.profile.notifications.update');
        
        // Notification management
        Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('api.mobile.notifications.index');
        Route::get('/notifications/summary', [\App\Http\Controllers\NotificationController::class, 'summary'])->name('api.mobile.notifications.summary');
        Route::put('/notifications/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])
            ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
            ->name('api.mobile.notifications.read');
        Route::put('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])
            ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
            ->name('api.mobile.notifications.read_all');
        Route::get('/notifications/preferences', [\App\Http\Controllers\NotificationController::class, 'preferences'])->name('api.mobile.notifications.preferences');
        Route::put('/notifications/preferences', [\App\Http\Controllers\NotificationController::class, 'updatePreferences'])
            ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
            ->name('api.mobile.notifications.preferences.update');
        
        // Device management for push notifications
        Route::post('/devices/register', [\App\Http\Controllers\Api\DeviceController::class, 'register'])->name('api.mobile.devices.register');
        Route::delete('/devices/unregister', [\App\Http\Controllers\Api\DeviceController::class, 'unregister'])->name('api.mobile.devices.unregister');
        Route::get('/devices', [\App\Http\Controllers\Api\DeviceController::class, 'devices'])->name('api.mobile.devices.index');
        Route::post('/devices/test-push', [\App\Http\Controllers\Api\DeviceController::class, 'testPush'])->name('api.mobile.devices.test_push');
        
        // Password Reset (Mobile API)
        Route::post('/auth/forgot-password', [\App\Http\Controllers\PasswordResetController::class, 'apiSendResetCode'])->name('api.mobile.auth.forgot_password');
        Route::post('/auth/reset-password', [\App\Http\Controllers\PasswordResetController::class, 'apiResetPassword'])->name('api.mobile.auth.reset_password');

        // Policies
        Route::get('/policies', [\App\Http\Controllers\Api\PoliciesController::class, 'index'])->name('api.mobile.policies');

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
