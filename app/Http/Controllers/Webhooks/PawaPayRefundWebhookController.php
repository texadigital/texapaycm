<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Transfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PawaPayRefundWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        $payload = $request->json()->all();
        $signature = $request->header('X-Signature'); // TODO: verify if supported
        Log::info('PawaPay refund webhook received', ['payload' => $payload]);

        $refundId = $payload['refundId'] ?? $payload['id'] ?? null;
        if (!$refundId) {
            Log::warning('Refund webhook missing refundId', ['payload' => $payload]);
            return response()->json(['ok' => false, 'error' => 'Missing refundId'], 400);
        }

        $event = \App\Models\WebhookEvent::firstOrCreate(
            ['provider' => 'pawapay', 'type' => 'refunds', 'event_id' => (string) $refundId],
            ['payload' => $payload, 'signature_hash' => $signature ? sha1($signature) : null]
        );
        if ($event->processed_at) {
            return response()->json(['ok' => true, 'duplicate' => true]);
        }
        $queue = env('NOTIFICATION_QUEUE_NAME', 'notifications');
        $connection = env('NOTIFICATION_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'database'));
        Log::info('Dispatching ProcessPawaPayRefund', [
            'event_id' => $event->id,
            'queue' => $queue,
            'connection' => $connection,
        ]);
        \App\Jobs\ProcessPawaPayRefund::dispatch($event->id, $payload)
            ->onConnection($connection)
            ->onQueue($queue);
        return response()->json(['ok' => true, 'queued' => $queue, 'connection' => $connection]);
    }
}
