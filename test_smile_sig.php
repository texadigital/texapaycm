<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$svc = app(App\Services\SmileIdService::class);
$ts = $svc->nowIso8601Utc();
$sig = $svc->generateSignature($ts);
echo json_encode(['timestamp' => $ts, 'signature' => $sig], JSON_UNESCAPED_SLASHES) . "\n";
