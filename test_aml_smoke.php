<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\AdminSetting;
use App\Models\User;
use App\Models\EddCase;
use App\Models\Transfer;
use App\Services\ScreeningService;
use App\Services\AmlRuleEvaluator;

echo "=== AML Smoke Test ===\n";

// 1) Force screening review for testing
AdminSetting::setValue('aml.screening.force_review', true, 'boolean', 'Force review for testing', 'aml');
echo "Set aml.screening.force_review = true\n";

// 2) Ensure a test user exists
$user = User::first();
if (!$user) {
    $user = User::create([
        'name' => 'AML Test User',
        'email' => 'aml-test@example.com',
        'password' => bcrypt('Password123!'),
        'kyc_level' => 0,
        'kyc_status' => 'unverified',
    ]);
    echo "Created test user ID={$user->id}\n";
} else {
    echo "Using existing user ID={$user->id}\n";
}

// 3) Run screening to trigger EDD case
$screen = app(ScreeningService::class);
$result = $screen->runUserScreening($user, 'kyc_update');
echo "Screening result: " . json_encode($result) . "\n";

$openCase = EddCase::where('user_id', $user->id)->whereIn('status', ['open','pending_docs','review'])->latest('id')->first();
echo $openCase ? ("EDD case opened: ID={$openCase->id}\n") : "No EDD case opened (unexpected)\n";

// 4) Create a high-amount transfer and evaluate rules
$transfer = Transfer::create([
    'user_id' => $user->id,
    'recipient_bank_code' => '999240',
    'recipient_bank_name' => 'SAFE HAVEN SANDBOX BANK',
    'recipient_account_number' => '1234567890',
    'recipient_account_name' => 'Test Beneficiary',
    'amount_xaf' => 600000,
    'fee_total_xaf' => 0,
    'total_pay_xaf' => 600000,
    'receive_ngn_minor' => 10000,
    'adjusted_rate_xaf_to_ngn' => 1.0,
    'usd_to_xaf' => 600.0,
    'usd_to_ngn' => 1000.0,
    'fx_fetched_at' => now(),
    'status' => 'completed',
    'payin_status' => 'success',
    'payout_status' => 'success',
    'timeline' => [],
]);
echo "Created transfer ID={$transfer->id} amount_xaf={$transfer->amount_xaf}\n";

$eval = app(AmlRuleEvaluator::class);
$alerts = $eval->evaluateTransfer($transfer->fresh(), 'manual');
echo "Alerts created: " . json_encode($alerts) . "\n";

echo "=== Done ===\n";
