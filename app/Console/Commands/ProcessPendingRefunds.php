<?php

namespace App\Console\Commands;

use App\Models\Transfer;
use App\Services\RefundService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessPendingRefunds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refunds:process-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and process pending refunds';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(RefundService $refundService)
    {
        $this->info('Starting to process pending refunds...');
        
        // Get transfers that need refund processing
        $transfers = Transfer::query()
            ->where('payin_status', 'success')
            ->where('payout_status', 'failed')
            ->where(function($query) {
                $query->whereNull('refund_status')
                      ->orWhere('refund_status', 'PENDING');
            })
            ->where(function($query) {
                // Only process if no refund attempted or last attempt was more than 1 hour ago
                $query->whereNull('refund_attempted_at')
                      ->orWhere('refund_attempted_at', '<', now()->subHour());
            })
            ->get();

        $this->info("Found {$transfers->count()} transfers needing refund processing");
        
        $processed = 0;
        $succeeded = 0;
        $failed = 0;
        
        foreach ($transfers as $transfer) {
            $this->info("Processing refund for transfer ID: {$transfer->id}");
            $processed++;
            
            try {
                // Check if refund was already initiated
                if ($transfer->refund_id) {
                    $this->info("Checking status of existing refund: {$transfer->refund_id}");
                    $status = $refundService->checkRefundStatus($transfer->refund_id);
                    
                    if ($status['success']) {
                        $updateData = [
                            'refund_status' => $status['status'],
                            'refund_response' => $status['data'] ?? null
                        ];
                        
                        if (in_array($status['status'], ['COMPLETED', 'SUCCESS'])) {
                            $updateData['refund_completed_at'] = now();
                            $this->info("Refund {$transfer->refund_id} status: {$status['status']}");
                            $succeeded++;
                        } elseif (in_array($status['status'], ['FAILED', 'REJECTED', 'CANCELLED'])) {
                            $this->error("Refund {$transfer->refund_id} failed with status: {$status['status']}");
                            $failed++;
                        } else {
                            $this->info("Refund {$transfer->refund_id} is still pending with status: {$status['status']}");
                        }
                        
                        $transfer->update($updateData);
                        continue;
                    }
                }
                
                // If we get here, we need to initiate a new refund
                $this->info("Initiating new refund for transfer ID: {$transfer->id}");
                $result = $refundService->refundFailedPayout($transfer);
                
                if ($result['success']) {
                    $this->info("Successfully initiated refund: {$result['refund_id']}");
                    $succeeded++;
                } else {
                    $this->error("Failed to initiate refund: " . ($result['message'] ?? 'Unknown error'));
                    $failed++;
                }
                
            } catch (\Exception $e) {
                $this->error("Error processing refund for transfer {$transfer->id}: " . $e->getMessage());
                Log::error("Error in ProcessPendingRefunds for transfer {$transfer->id}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $failed++;
            }
        }
        
        $this->info("\nRefund processing completed");
        $this->info("Total processed: {$processed}");
        $this->info("Succeeded: {$succeeded}");
        $this->info("Failed: {$failed}");
        
        return 0;
    }
}
