<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('transfer.bank');
});

Route::prefix('transfer')->group(function () {
    Route::get('/bank', [\App\Http\Controllers\TransferController::class, 'showBankForm'])->name('transfer.bank');
    Route::post('/bank/verify', [\App\Http\Controllers\TransferController::class, 'verifyBank'])->name('transfer.bank.verify');

    Route::get('/quote', [\App\Http\Controllers\TransferController::class, 'showQuoteForm'])->name('transfer.quote');
    Route::post('/quote', [\App\Http\Controllers\TransferController::class, 'createQuote'])->name('transfer.quote.create');

    Route::post('/confirm', [\App\Http\Controllers\TransferController::class, 'confirmPayIn'])->name('transfer.confirm');

    Route::get('/receipt/{transfer}', [\App\Http\Controllers\TransferController::class, 'showReceipt'])->name('transfer.receipt');
    Route::post('/{transfer}/payout', [\App\Http\Controllers\TransferController::class, 'initiatePayout'])->name('transfer.payout');
    Route::post('/{transfer}/payout/status', [\App\Http\Controllers\TransferController::class, 'payoutStatus'])->name('transfer.payout.status');
});

// Webhooks
Route::post('/webhooks/pawapay', [\App\Http\Controllers\Webhooks\PawaPayWebhookController::class, '__invoke'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('webhooks.pawapay');

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

