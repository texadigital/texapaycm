<?php
require __DIR__ . '/../vendor/autoload.php';
use Minishlink\WebPush\VAPID;
$k = VAPID::createVapidKeys();
echo "VAPID_PUBLIC_KEY=" . $k['publicKey'] . PHP_EOL;
echo "VAPID_PRIVATE_KEY=" . $k['privateKey'] . PHP_EOL;
echo "VAPID_SUBJECT=mailto:admin@example.com" . PHP_EOL;
