<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    // If an admin lands on root, send them to Filament admin
    if (Auth::check() && (bool) (Auth::user()->is_admin ?? false)) {
        return redirect('/admin');
    }
    return redirect()->route('transfer.bank');
});

Route::middleware(['auth','redirect.admins'])->prefix('transfer')->group(function () {
    Route::get('/bank', [\App\Http\Controllers\TransferController::class, 'showBankForm'])->name('transfer.bank');
    Route::post('/bank/verify', [\App\Http\Controllers\TransferController::class, 'verifyBank'])->name('transfer.bank.verify');

    Route::get('/quote', [\App\Http\Controllers\TransferController::class, 'showQuoteForm'])->name('transfer.quote');
    Route::post('/quote', [\App\Http\Controllers\TransferController::class, 'createQuote'])
        ->middleware('check.limits')
        ->name('transfer.quote.create');

    Route::get('/receipt/{transfer}', [\App\Http\Controllers\TransferController::class, 'showReceipt'])->name('transfer.receipt');
    Route::post('/{transfer}/payout', [\App\Http\Controllers\TransferController::class, 'initiatePayout'])->name('transfer.payout');
    Route::post('/{transfer}/payout/status', [\App\Http\Controllers\TransferController::class, 'payoutStatus'])->name('transfer.payout.status');
});

// Auth + Dashboard
Route::get('/register', [\App\Http\Controllers\AuthController::class, 'showRegister'])->name('register.show');
Route::get('/login', [\App\Http\Controllers\AuthController::class, 'showLogin'])->name('login.show');
Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login'])->name('login');
Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->name('logout');
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
});

// Webhooks
Route::post('/api/webhooks/pawapay', [\App\Http\Controllers\Webhooks\PawaPayWebhookController::class, '__invoke'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('webhooks.pawapay');

// Refund webhook (PawaPay sends refund status callbacks here)
Route::post('/api/v1/webhooks/pawapay/refunds', [\App\Http\Controllers\Webhooks\PawaPayRefundWebhookController::class, '__invoke'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('webhooks.pawapay.refunds');

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

