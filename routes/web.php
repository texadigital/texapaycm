<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Kyc\SmileIdController;
use App\Http\Controllers\Kyc\KycController;

Route::get('/', function () {
    // If an admin lands on root, send them to Filament admin
    if (Auth::check() && (bool) (Auth::user()->is_admin ?? false)) {
        return redirect('/admin');
    }
    return redirect()->route('transfer.bank');
});

// Admin-friendly receipt route (avoid redirect.admins so admins can view receipts)
Route::middleware(['auth'])->group(function () {
    Route::get('/admin/transfer/{transfer}/receipt', [\App\Http\Controllers\TransferController::class, 'showReceipt'])
        ->name('admin.transfer.receipt');
});

Route::middleware(['auth','redirect.admins'])->prefix('transfer')->group(function () {
    Route::get('/bank', [\App\Http\Controllers\TransferController::class, 'showBankForm'])->name('transfer.bank');
    Route::post('/bank/verify', [\App\Http\Controllers\TransferController::class, 'verifyBank'])->name('transfer.bank.verify');

    Route::get('/quote', [\App\Http\Controllers\TransferController::class, 'showQuoteForm'])->name('transfer.quote');
    Route::post('/quote', [\App\Http\Controllers\TransferController::class, 'createQuote'])
        ->middleware('check.limits')
        ->name('transfer.quote.create');
    Route::post('/quote/confirm', [\App\Http\Controllers\TransferController::class, 'confirmPayIn'])->name('transfer.confirm');

    Route::get('/receipt/{transfer}', [\App\Http\Controllers\TransferController::class, 'showReceipt'])->name('transfer.receipt');
    Route::post('/{transfer}/payin/status', [\App\Http\Controllers\TransferController::class, 'payinStatus'])->name('transfer.payin.status');
    Route::post('/{transfer}/payout', [\App\Http\Controllers\TransferController::class, 'initiatePayout'])->name('transfer.payout');
    Route::post('/{transfer}/payout/status', [\App\Http\Controllers\TransferController::class, 'payoutStatus'])->name('transfer.payout.status');

    // Live timeline JSON for client polling
    Route::get('/{transfer}/timeline', [\App\Http\Controllers\TransferController::class, 'timeline'])
        ->name('transfer.timeline');
    // Download single receipt as PDF
    Route::get('/{transfer}/receipt/pdf', [\App\Http\Controllers\TransferController::class, 'receiptPdf'])
        ->name('transfer.receipt.pdf');
    // Generate a temporary signed share link (JSON)
    Route::post('/{transfer}/share-url', [\App\Http\Controllers\TransferController::class, 'shareLink'])
        ->name('transfer.receipt.share.link');
});

 

// Auth + Dashboard
Route::get('/register', [\App\Http\Controllers\AuthController::class, 'showRegister'])->name('register.show');
Route::post('/register', [\App\Http\Controllers\AuthController::class, 'register'])->name('register');
Route::get('/login', [\App\Http\Controllers\AuthController::class, 'showLogin'])->name('login.show');
Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login'])->name('login');
// PIN challenge for users with PIN enabled
Route::get('/login/pin', [\App\Http\Controllers\AuthController::class, 'showPinChallenge'])->name('login.pin.show');
Route::post('/login/pin', [\App\Http\Controllers\AuthController::class, 'verifyPinChallenge'])->name('login.pin.verify');
Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->name('logout');

// Password Reset
Route::get('/forgot-password', [\App\Http\Controllers\PasswordResetController::class, 'showForgotPassword'])->name('password.forgot');
Route::post('/forgot-password', [\App\Http\Controllers\PasswordResetController::class, 'sendResetCode'])->name('password.email');
Route::get('/reset-password', [\App\Http\Controllers\PasswordResetController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [\App\Http\Controllers\PasswordResetController::class, 'resetPassword'])->name('password.update');
Route::middleware(['auth','redirect.admins'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/transactions', [\App\Http\Controllers\TransactionsController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/{transfer}', [\App\Http\Controllers\TransferController::class, 'showReceipt'])->name('transactions.show');
    Route::get('/transactions/export', [\App\Http\Controllers\TransactionsController::class, 'export'])->name('transactions.export');
    
    // Profile routes
    Route::prefix('profile')->group(function () {
        Route::get('/', [\App\Http\Controllers\ProfileController::class, 'index'])->name('profile.index');
        Route::get('/limits', [\App\Http\Controllers\ProfileController::class, 'limits'])->name('profile.limits');
        Route::get('/personal-info', [\App\Http\Controllers\ProfileController::class, 'personalInfo'])->name('profile.personal');
        Route::post('/personal-info', [\App\Http\Controllers\ProfileController::class, 'updatePersonalInfo'])->name('profile.personal.update');
        Route::get('/notifications', [\App\Http\Controllers\ProfileController::class, 'notifications'])->name('profile.notifications');
        Route::post('/notifications', [\App\Http\Controllers\ProfileController::class, 'updateNotifications'])->name('profile.notifications.update');
        // Security
        Route::get('/security', [\App\Http\Controllers\SecurityController::class, 'index'])->name('profile.security');
        Route::post('/security/toggles', [\App\Http\Controllers\SecurityController::class, 'updateToggles'])->name('profile.security.toggles');
        Route::post('/security/pin', [\App\Http\Controllers\SecurityController::class, 'updatePin'])->name('profile.security.pin');
    });
    
    // Account management
    Route::post('/account/delete', [\App\Http\Controllers\ProfileController::class, 'deleteAccount'])->name('account.delete');

    // Support routes
    Route::prefix('support')->group(function () {
        Route::get('/help', [\App\Http\Controllers\SupportController::class, 'help'])->name('support.help');
        Route::get('/contact', [\App\Http\Controllers\SupportController::class, 'contact'])->name('support.contact');
        Route::post('/contact', [\App\Http\Controllers\SupportController::class, 'submitTicket'])->name('support.contact.submit');
        Route::get('/tickets', [\App\Http\Controllers\SupportController::class, 'myTickets'])->name('support.tickets');
    });

    // Notification routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/summary', [\App\Http\Controllers\NotificationController::class, 'summary'])->name('notifications.summary');
        Route::put('/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::put('/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.read_all');
        Route::get('/preferences', [\App\Http\Controllers\NotificationController::class, 'preferences'])->name('notifications.preferences');
        Route::put('/preferences', [\App\Http\Controllers\NotificationController::class, 'updatePreferences'])->name('notifications.preferences.update');
    });
});

// Webhooks
Route::post('/api/webhooks/pawapay', [\App\Http\Controllers\Webhooks\PawaPayWebhookController::class, '__invoke'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('webhooks.pawapay');

// KYC: Smile ID webhook (public)
Route::post('/api/kyc/smileid/callback', [SmileIdController::class, 'callback'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('kyc.smileid.callback');

// Refund webhook (PawaPay sends refund status callbacks here)
Route::post('/api/v1/webhooks/pawapay/refunds', [\App\Http\Controllers\Webhooks\PawaPayRefundWebhookController::class, '__invoke'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('webhooks.pawapay.refunds');

// Public static pages
Route::get('/policies', function () {
    return view('static.policies');
})->name('policies');

// PawaPay dashboard may call versioned paths; add aliases to avoid 404s
Route::post('/api/v1/webhooks/pawapay/deposits', [\App\Http\Controllers\Webhooks\PawaPayWebhookController::class, '__invoke'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::post('/api/v2/webhooks/pawapay/deposits', [\App\Http\Controllers\Webhooks\PawaPayWebhookController::class, '__invoke'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// Health checks
Route::get('/health/safehaven', function (\App\Services\SafeHaven $safeHaven) {
    return response()->json($safeHaven->checkAuth());
});

Route::get('/health/safehaven/banks', function (\App\Services\SafeHaven $safeHaven) {
    return response()->json($safeHaven->listBanks());
});

Route::get('/health/pawapay', function (\App\Services\PawaPay $pawaPay) {
    return response()->json($pawaPay->checkAuth());
});

// PawaPay toolkit debug
Route::get('/health/pawapay/config', function (\App\Services\PawaPay $pawaPay) {
    return response()->json($pawaPay->activeConfiguration());
});
Route::get('/health/pawapay/predict', function (\App\Services\PawaPay $pawaPay) {
    $msisdn = request('msisdn');
    return response()->json($pawaPay->predictProvider((string) $msisdn));
});

// API: Banks
Route::get('/api/banks', [\App\Http\Controllers\BankController::class, 'list']);
Route::get('/api/banks/favorites', [\App\Http\Controllers\BankController::class, 'favorites']);
Route::post('/api/banks/suggest', [\App\Http\Controllers\BankController::class, 'suggest']);

// TEMP: Name Enquiry probe (for terminal testing only)
Route::get('/health/safehaven/name-enquiry', function () {
    $bank = request('bankCode');
    $acct = request('account');
    /** @var \App\Services\SafeHaven $svc */
    $svc = app(\App\Services\SafeHaven::class);
    if (!$bank || !$acct) {
        return response()->json(['error' => 'Provide bankCode and account query params'], 400);
    }
    return response()->json($svc->nameEnquiry($bank, $acct));
});

// Exchange Rates Debug
Route::get('/health/oxr', function () {
    /** @var \App\Services\OpenExchangeRates $oxr */
    $oxr = app(\App\Services\OpenExchangeRates::class);
    
    $rates = $oxr->fetchUsdRates();
    
    $debug = [
        'config' => [
            'base_url' => env('OXR_BASE_URL'),
            'app_id_set' => !empty(env('OXR_APP_ID')),
            'fallback' => env('FALLBACK_XAF_TO_NGN'),
            'cache_ttl' => env('OXR_CACHE_TTL_MINUTES', 60),
        ],
        'rates' => $rates,
        'cross_rate' => null,
        'sample_quote' => null
    ];
    
    if ($rates['usd_to_xaf'] && $rates['usd_to_ngn']) {
        $crossRate = $rates['usd_to_ngn'] / $rates['usd_to_xaf'];
        $debug['cross_rate'] = $crossRate;
        
        // Sample quote for 10,000 XAF
        $amountXaf = 10000;
        $amountNgn = $amountXaf * $crossRate;
        $debug['sample_quote'] = [
            'send_xaf' => $amountXaf,
            'receive_ngn' => round($amountNgn, 2),
            'rate' => $crossRate
        ];
    }
    
    return response()->json($debug);
});

// Signed, shareable receipt view (public, signed URL required)
Route::get('/s/receipt/{transfer}', [\App\Http\Controllers\TransferController::class, 'showSharedReceipt'])
    ->middleware('signed')
    ->name('transfer.receipt.shared');

// KYC: Smile ID start-session (authenticated)
Route::middleware(['auth'])->group(function () {
    Route::post('/api/kyc/smileid/start', [SmileIdController::class, 'start'])
        ->name('kyc.smileid.start');
    Route::post('/api/kyc/smileid/web-token', [SmileIdController::class, 'webToken'])
        ->name('kyc.smileid.web_token');
    Route::get('/kyc', [KycController::class, 'index'])->name('kyc.index');
    Route::get('/api/kyc/status', [KycController::class, 'status'])->name('kyc.status');
});


