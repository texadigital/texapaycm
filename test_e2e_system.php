<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use App\Models\UserDevice;
use App\Models\UserNotification;
use App\Models\NotificationPreference;
use App\Models\NotificationEvent;
use App\Services\NotificationService;
use App\Jobs\SendEmailNotification;
use App\Jobs\SendSmsNotification;
use App\Jobs\SendPushNotification;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Twilio\Rest\Client as TwilioClient;

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

function hr(string $t){ echo "\n==== {$t} ====\n"; }

echo "TexaPay E2E System Test (user lifecycle, transfers, notifications)\n";

// Targets (email/SMS) from CLI args
$targetEmail = $argv[1] ?? 'ayuk1ndip@gmail.com';
$targetPhone = $argv[2] ?? '+237674226623';

// 0) Preflight
hr('0) Preflight');
$tables = ['user_notifications','notification_events','notification_preferences'];
foreach ($tables as $t) {
}
echo "queue.default=" . config('queue.default') . ", mailer=" . config('mail.default') . "\n";

// 1) User creation & profile setup
hr('1) User creation & prefs');
$email = 'e2e+' . time() . '@texa.ng';
// Generate a unique Cameroon-like local phone each run (9 digits) starting with 674
$uniquePhone = '674' . str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

$user = User::firstOrCreate(
    ['phone' => $uniquePhone],
    [
        'name' => 'E2E User',
        'email' => $email,
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
        'email_notifications' => true,
        'sms_notifications' => true,
    ]
);
// Route notifications for this run to the target email
$user->email = $targetEmail;
$user->save();

echo "user_id={$user->id} email={$user->email}\n";
$types = [
    'auth.login.success','auth.login.failed',
    'transfer.quote.created','transfer.payin.success','transfer.payout.failed',
    'profile.updated'
];
foreach ($types as $t) { NotificationPreference::getOrCreateDefault($user, $t); }

echo "prefs seeded for ".count($types)." types\n";

// 2) Device registration (push)
hr('2) Device registration');
$token = str_repeat('a', 152);
$device = UserDevice::registerDevice($user, $token, 'web', 'e2e_web_1');
echo "device_id={$device->id} active=".($device->is_active?'1':'0')."\n";

// 3) User activities & auth notifications
hr('3) Auth notifications');
$svc = new NotificationService();
$authSuccess = $svc->dispatchUserNotification('auth.login.success', $user, ['ip_address'=>'127.0.0.1','ua'=>'E2E','nonce'=>time()], ['email','in_app']);
$authFail    = $svc->dispatchUserNotification('auth.login.failed',  $user, ['ip_address'=>'127.0.0.1','ua'=>'E2E','nonce'=>time()], ['in_app']);
echo "auth.login.success=".($authSuccess? 'id#'.$authSuccess->id:'null').", auth.login.failed=".($authFail? 'id#'.$authFail->id:'null')."\n";

// 4) Transfer flow simulations (quote -> payin success -> payout failed)
hr('4) Transfer flow notifications');
$quote = $svc->dispatchUserNotification('transfer.quote.created', $user, [
    'quote'=>['amount_xaf'=>12345,'receive_ngn_minor'=>987654,'expires_at'=>now()->addMinutes(3)->toISOString()],
    'nonce'=>time()
], ['in_app']);
$payin = $svc->dispatchUserNotification('transfer.payin.success', $user, [
    'transfer'=>['id'=>Str::uuid()->toString(),'amount_xaf'=>12345,'payin_at'=>now()->toISOString()],
    'nonce'=>time()
], ['sms','in_app']);
$payoutFail = $svc->dispatchUserNotification('transfer.payout.failed', $user, [
    'transfer'=>['id'=>Str::uuid()->toString(),'amount_xaf'=>12345,'failure_reason'=>'E2E_SIMULATED'],
    'nonce'=>time()
], ['push','in_app']);

echo "quote=".($quote? 'id#'.$quote->id:'null').", payin=".($payin? 'id#'.$payin->id:'null').", payout.failed=".($payoutFail? 'id#'.$payoutFail->id:'null')."\n";

// 5) Execute channel jobs directly (email/sms/push)
hr('5) Channel jobs execution');
$latest = UserNotification::where('user_id',$user->id)->orderByDesc('id')->take(5)->get();
foreach ($latest as $n) {
    echo "job for notif#{$n->id} type={$n->type} channels=".json_encode($n->channels)."\n";
    if (in_array('email', $n->channels ?? [])) {
        try { (new SendEmailNotification($n))->handle(); echo "  email: OK\n"; } catch (Throwable $e) { echo "  email: FAIL {$e->getMessage()}\n"; }
    }
    if (in_array('sms', $n->channels ?? [])) {
        try { (new SendSmsNotification($n))->handle(); echo "  sms: OK\n"; } catch (Throwable $e) { echo "  sms: FAIL {$e->getMessage()}\n"; }
    }
    if (in_array('push', $n->channels ?? [])) {
        try { (new SendPushNotification($n))->handle(); echo "  push: OK (attempted)\n"; } catch (Throwable $e) { echo "  push: FAIL {$e->getMessage()}\n"; }
    }
}

// 6) Live email & SMS probe (independent of jobs)
hr('6) Live email & SMS probe');
try {
    Mail::raw('E2E system test email at '.now()->toDateTimeString(), function($m) use($targetEmail){
        $m->to($targetEmail)->subject('TexaPay E2E Email');
    });
    echo "live email: sent to {$targetEmail}\n";
} catch (Throwable $e) { echo "live email: FAIL {$e->getMessage()}\n"; }

try {
    $sid = config('services.twilio.sid');
    $auth = config('services.twilio.auth_token');
    $from = config('services.twilio.phone_number');
    if ($sid && $auth && $from) {
        $tw = new TwilioClient($sid, $auth);
        $tw->messages->create($targetPhone, ['from'=>$from, 'body'=>'TexaPay E2E SMS at '.now()->toDateTimeString()]);
        echo "live sms: sent to {$targetPhone}\n";
    } else {
        echo "live sms: SKIPPED (config missing)\n";
    }
} catch (Throwable $e) { echo "live sms: FAIL {$e->getMessage()}\n"; }

// 7) Summary & dedupe sample
hr('7) Summary');
$total = UserNotification::where('user_id',$user->id)->count();
echo "total notifications for user: {$total}\n";
$recent = UserNotification::where('user_id',$user->id)->orderByDesc('id')->limit(10)->get(['id','type','channels','created_at']);
foreach ($recent as $r) {
    echo "- #{$r->id} {$r->type} channels=".json_encode($r->channels)." at {$r->created_at}\n";
}

hr('Done');
