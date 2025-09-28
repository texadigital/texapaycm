<?php

namespace App\Jobs;

use App\Models\Transfer;
use App\Models\WebhookEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessPawaPayRefund implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $backoff = 15;

    public function __construct(private int $eventId, private array $payload) {}

    public function handle(): void
    {
        $event = WebhookEvent::find($this->eventId);
        if (!$event) { return; }
        if ($event->processed_at) { return; }

        $payload = $this->payload;
        $refundId = $payload['refundId'] ?? $payload['id'] ?? null;
        $status = strtoupper($payload['status'] ?? '');
        $meta = $payload['metadata'] ?? [];
        $transferIdMeta = $meta['transfer_id'] ?? null;
        if (!$refundId && !$transferIdMeta) { return; }

        DB::transaction(function () use ($refundId, $transferIdMeta, $status, $payload, $event) {
            $transfer = null;
            if ($refundId) {
                $transfer = Transfer::where('refund_id', $refundId)->lockForUpdate()->first();
            }
            if (!$transfer && $transferIdMeta) {
                $transfer = Transfer::lockForUpdate()->find($transferIdMeta);
            }
            if (!$transfer) {
                Log::warning('Refund webhook job: transfer not found', ['refundId' => $refundId, 'transfer_id_meta' => $transferIdMeta]);
                $event->update(['processed_at' => now()]);
                return;
            }

            $timeline = is_array($transfer->timeline) ? $transfer->timeline : [];
            $timeline[] = [
                'state' => 'refund_webhook_received',
                'at' => now()->toIso8601String(),
                'status' => $status,
                'refund_id' => $refundId,
            ];

            $update = [
                'refund_status' => $status ?: $transfer->refund_status,
                'refund_response' => $payload,
                'timeline' => $timeline,
            ];
            if (in_array($status, ['COMPLETED', 'SUCCESS'], true)) {
                $update['refund_completed_at'] = now();
            }
            $transfer->update($update);

            $event->update(['processed_at' => now()]);
        });
    }
}
