<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Mail;
use Twilio\Rest\Client as TwilioClient;

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Notifications Healthcheck (Live send)\n\n";

// Read targets from argv or default
$targetEmail = $argv[1] ?? 'ayuk1ndip@gmail.com';
$targetPhone = $argv[2] ?? '+237674226623';

// Mail config check
echo "-- Mail config --\n";
echo "mailer=" . config('mail.default') . " host=" . config('mail.mailers.smtp.host') . " port=" . config('mail.mailers.smtp.port') . "\n";
echo "from=" . config('mail.from.address') . " name=" . config('mail.from.name') . "\n";

// Send a simple email
try {
    Mail::raw('TexaPay live healthcheck email at ' . now()->toDateTimeString(), function ($m) use ($targetEmail) {
        $m->to($targetEmail)->subject('TexaPay Email Healthcheck');
    });
    echo "email: sent to {$targetEmail}\n";
} catch (Throwable $e) {
    echo "email: FAILED - {$e->getMessage()}\n";
}

// Twilio config check
echo "\n-- Twilio config --\n";
$twSid = config('services.twilio.sid');
$twAuth = config('services.twilio.auth_token');
$twFrom = config('services.twilio.phone_number');
echo "sid_set=" . ($twSid ? '1' : '0') . " from=" . ($twFrom ?: 'N/A') . "\n";

// Send SMS
try {
    if (!$twSid || !$twAuth || !$twFrom) {
        throw new RuntimeException('Twilio config missing');
    }
    $tw = new TwilioClient($twSid, $twAuth);
    $tw->messages->create($targetPhone, [
        'from' => $twFrom,
        'body' => 'TexaPay live SMS healthcheck at ' . now()->toDateTimeString(),
    ]);
    echo "sms: sent to {$targetPhone}\n";
} catch (Throwable $e) {
    echo "sms: FAILED - {$e->getMessage()}\n";
}

// FCM config check
echo "\n-- FCM config --\n";
$fcmKey = config('services.fcm.server_key');
$fcmProject = config('services.fcm.project_id');
echo "server_key_set=" . ($fcmKey ? '1' : '0') . " project_id=" . ($fcmProject ?: 'N/A') . "\n";

try {
    /** @var \App\Services\FcmService $fcm */
    $fcm = app(\App\Services\FcmService::class);
    $ok = $fcm->testConnection();
    echo "fcm_test_connection=" . ($ok ? 'OK' : 'FAILED') . "\n";
} catch (Throwable $e) {
    echo "fcm_test_connection: FAILED - {$e->getMessage()}\n";
}

echo "\nHealthcheck completed.\n";
