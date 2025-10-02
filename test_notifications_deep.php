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

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

function hr(string $title) { echo "\n==== {$title} ====\n"; }

function reflect_call(object $obj, string $method, array $args = []) {
    $rm = new ReflectionMethod(get_class($obj), $method);
    $rm->setAccessible(true);
    return $rm->invokeArgs($obj, $args);
}

function dedupe_status(int $userId, string $type, array $payload): array {
    $ek = \App\Models\NotificationEvent::generateEventKey($type, $payload);
    $exists = \App\Models\NotificationEvent::where('user_id', $userId)
        ->where('event_type', $type)
        ->where('event_key', $ek)
        ->where('created_at', '>', now()->subMinutes(5))
        ->exists();
    return [$ek, $exists];
}

function diag_and_dispatch(\App\Services\NotificationService $service, \App\Models\User $user, string $type, array $payload, array $channels, string $label) {
    echo "-- {$label} diagnostics --\n";
    try {
        $should = reflect_call($service, 'shouldSendNotification', [$user, $type]);
    } catch (Throwable $e) {
        $should = null; echo "  shouldSendNotification: exception {$e->getMessage()}\n";
    }
    try {
        $tpl = reflect_call($service, 'getNotificationTemplate', [$type, $payload]);
    } catch (Throwable $e) {
        $tpl = null; echo "  getNotificationTemplate: exception {$e->getMessage()}\n";
    }
    [$ek, $dup] = dedupe_status($user->id, $type, $payload);
    echo "  shouldSendNotification=" . (is_bool($should)? ($should?'1':'0') : 'n/a') . ", template=" . ($tpl? 'yes':'no') . ", duplicate_recent=" . ($dup? '1':'0') . "\n";

    $result = $service->dispatchUserNotification($type, $user, $payload, $channels);
    if ($result) {
        echo "  dispatch: created id={$result->id}\n";
        return;
    }
    echo "  dispatch: returned null\n";

    // If gates say we should send, template exists, and not duplicate: try manual insert to confirm DB path
    if ($should === true && $tpl && !$dup) {
        echo "  manual-insert: attempting...\n";
        try {
            $n = \App\Models\UserNotification::create([
                'user_id' => $user->id,
                'type' => $type,
                'title' => $tpl['title'] ?? ($payload['title'] ?? 'Title'),
                'message' => $tpl['message'] ?? ($payload['message'] ?? 'Message'),
                'payload' => $payload,
                'channels' => $channels,
                'dedupe_key' => $ek,
            ]);
            \App\Models\NotificationEvent::create([
                'user_id' => $user->id,
                'event_type' => $type,
                'event_key' => $ek,
                'processed_at' => now(),
            ]);
            echo "  manual-insert: created id={$n->id}\n";
        } catch (Throwable $e) {
            echo "  manual-insert: exception {$e->getMessage()}\n";
        }
    }
}

echo "TexaPay Notification System Deep Test (storage, channels, prefs, dedupe)\n";

// 0) Preconditions & environment checks
hr('0) Preconditions & environment checks');
$issues = [];

$tables = [
    'user_notifications', 'notification_events', 'notification_preferences'
];
foreach ($tables as $t) {
    $exists = \Schema::hasTable($t);
    echo "- table {$t}: " . ($exists ? 'present' : 'MISSING') . "\n";
    if (!$exists) $issues[] = "Missing table: {$t}. Run php artisan migrate";
}

echo "- queue.default: " . config('queue.default') . "\n";
echo "- mail.mailer: " . config('mail.default') . "\n";
$tw = config('services.twilio');
echo "- twilio: " . ($tw && ($tw['sid'] ?? null) ? 'configured' : 'not configured') . "\n";
$fcmOk = (config('services.fcm.server_key') && config('services.fcm.project_id'));
echo "- fcm: " . ($fcmOk ? 'configured' : 'not configured') . "\n";

if ($issues) {
    echo "\nPreflight issues detected:\n - " . implode("\n - ", $issues) . "\n";
    echo "Aborting deep test.\n";
    exit(1);
}

// 1) Ensure test user & global prefs
hr('1) Ensure test user & global prefs');
$user = User::firstOrCreate(
    ['email' => 'info@texa.ng'],
    [
        'name' => 'Test User',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
        'phone' => '650123456',
        'email_notifications' => true,
        'sms_notifications' => false,
    ]
);

// normalize prefs
$user->email_notifications = true;
$user->sms_notifications = $user->sms_notifications ?? false;
$user->save();

echo "User: id={$user->id}, email={$user->email}, email_notifications=" . ($user->email_notifications?'1':'0') . ", sms_notifications=" . ($user->sms_notifications?'1':'0') . "\n";

// Ensure per-type preference rows exist via model helper
$catalog = [
    'auth.login.success',
    'transfer.quote.created',
    'transfer.payin.success',
    'transfer.payout.failed',
    'profile.updated',
];
foreach ($catalog as $t) {
    $pref = NotificationPreference::getOrCreateDefault($user, $t);
    echo "Pref {$t}: email=" . ($pref->email_enabled?'1':'0') . 
         " sms=" . ($pref->sms_enabled?'1':'0') .
         " push=" . ($pref->push_enabled?'1':'0') .
         " in_app=" . ($pref->in_app_enabled?'1':'0') . "\n";
}

// 2) Ensure an active device for push testing (dummy token)
hr('2) Ensure active push device');
$deviceToken = str_repeat('a', 152);
$device = UserDevice::updateOrCreate(
    ['user_id' => $user->id, 'device_token' => $deviceToken],
    ['platform' => 'web', 'is_active' => true, 'last_used_at' => now()]
);

echo "Device: id={$device->id}, platform={$device->platform}, active=" . ($device->is_active?'1':'0') . "\n";

$service = new NotificationService();
$startCount = UserNotification::where('user_id', $user->id)->count();

// 3) In-app only storage (valid template)
hr('3) In-app storage (transfer.quote.created)');
diag_and_dispatch(
    $service,
    $user,
    'transfer.quote.created',
    ['quote' => ['amount_xaf' => 10000, 'expires_at' => now()->addMinutes(5)->toISOString(), 'nonce'=>time()]],
    ['in_app'],
    'in_app transfer.quote.created'
);

// 4) Email channel: store + run job handle() (no worker required)
hr('4) Email channel (auth.login.success)');
diag_and_dispatch(
    $service,
    $user,
    'auth.login.success',
    ['ip_address' => '127.0.0.1', 'nonce'=>time()],
    ['email','in_app'],
    'email auth.login.success'
);
// Attempt job on the latest created notification
if ($last = UserNotification::where('user_id',$user->id)->orderByDesc('id')->first()) {
    try { (new SendEmailNotification($last))->handle(); echo "  email job handle(): attempted on id={$last->id}\n"; } catch (Throwable $e) { echo "  email job exception: {$e->getMessage()}\n"; }
}

// 5) SMS channel (if configured): store + run handle()
hr('5) SMS channel (transfer.payin.success)');
diag_and_dispatch(
    $service,
    $user,
    'transfer.payin.success',
    ['transfer' => ['amount_xaf' => 15000], 'nonce'=>time()],
    ['sms','in_app'],
    'sms transfer.payin.success'
);
if ($last = UserNotification::where('user_id',$user->id)->orderByDesc('id')->first()) {
    try { (new SendSmsNotification($last))->handle(); echo "  sms job handle(): attempted on id={$last->id}\n"; } catch (Throwable $e) { echo "  sms job exception: {$e->getMessage()}\n"; }
}

// 6) Push channel (if FCM configured): store + run handle()
hr('6) Push channel (transfer.payout.failed)');
diag_and_dispatch(
    $service,
    $user,
    'transfer.payout.failed',
    ['transfer' => ['amount_xaf' => 20000, 'reason' => 'TEST'], 'nonce'=>time()],
    ['push','in_app'],
    'push transfer.payout.failed'
);
if ($last = UserNotification::where('user_id',$user->id)->orderByDesc('id')->first()) {
    try { $fcm = app(\App\Services\FcmService::class); (new SendPushNotification($last, $fcm))->handle(); echo "  push job handle(): attempted on id={$last->id}\n"; } catch (Throwable $e) { echo "  push job exception: {$e->getMessage()}\n"; }
}

// 7) Deduplication window test (same type+payload twice)
hr('7) Deduplication test (auth.login.success)');
$payload = ['ip_address' => '127.0.0.1'];
$first = $service->dispatchUserNotification('auth.login.failed', $user, $payload, ['in_app']);
$second = $service->dispatchUserNotification('auth.login.failed', $user, $payload, ['in_app']);
echo "first=" . ($first? 'created#'.$first->id : 'null') . ", second=" . ($second? 'created#'.$second->id : 'null (deduped)') . "\n";

// 8) Per-type preference toggle test
hr('8) Preference toggle test (profile.updated)');
$pref = NotificationPreference::getOrCreateDefault($user, 'profile.updated');
$pref->update(['email_enabled' => false, 'in_app_enabled' => true]);
$pref->refresh();
diag_and_dispatch(
    $service,
    $user,
    'profile.updated',
    ['changes'=>['name'=>'New'], 'nonce'=>time()],
    ['email','in_app'],
    'prefs profile.updated (email off)'
);

// Summary
hr('Summary');
$total = UserNotification::where('user_id',$user->id)->count();
$newRows = $total - $startCount;
echo "New rows created: {$newRows}\n";
$recent = UserNotification::where('user_id',$user->id)->orderBy('id','desc')->limit(5)->get(['id','type','channels','created_at']);
foreach ($recent as $r) {
    echo "- #{$r->id} {$r->type} channels=" . json_encode($r->channels) . " at {$r->created_at}\n";
}

echo "\nDeep test completed.\n";
