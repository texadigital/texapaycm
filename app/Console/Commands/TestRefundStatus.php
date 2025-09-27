<?php

namespace App\Console\Commands;

use App\Services\RefundService;
use Illuminate\Console\Command;

class TestRefundStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:refund:status {refund_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the status of a refund';

    /**
     * Execute the console command.
     */
    public function handle(RefundService $refundService)
    {
        $refundId = $this->argument('refund_id');
        
        $this->info("Checking status of refund: {$refundId}");
        
        try {
            $result = $refundService->checkRefundStatus($refundId);
            
            if ($result['success']) {
                $this->info("✅ Refund status: " . strtoupper($result['status']));
                
                if (isset($result['data'])) {
                    $this->line("\nRefund Details:");
                    
                    $rows = [];
                    foreach ($result['data'] as $key => $value) {
                        if (is_array($value) || is_object($value)) {
                            $value = json_encode($value, JSON_PRETTY_PRINT);
                        }
                        $rows[] = [$key, $value];
                    }
                    
                    $this->table(['Field', 'Value'], $rows);
                }
                
                // If we have a transfer ID in the metadata, update the transfer
                if (isset($result['data']['metadata']['transfer_id'])) {
                    $transfer = \App\Models\Transfer::find($result['data']['metadata']['transfer_id']);
                    if ($transfer) {
                        $updateData = [
                            'refund_status' => $result['status'],
                            'refund_response' => $result['data']
                        ];
                        
                        if (in_array($result['status'], ['COMPLETED', 'SUCCESS', 'FAILED', 'REJECTED', 'CANCELLED'])) {
                            $updateData['refund_completed_at'] = now();
                        }
                        
                        $transfer->update($updateData);
                        $this->info("\n✅ Updated transfer #{$transfer->id} with refund status.");
                    }
                }
                
                return 0;
            } else {
                $this->error("❌ Failed to check refund status");
                $this->line("Error: " . ($result['message'] ?? 'Unknown error'));
                
                if (isset($result['error'])) {
                    $this->line("Details: " . json_encode($result['error'], JSON_PRETTY_PRINT));
                }
                
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Exception: " . $e->getMessage());
            $this->line("File: " . $e->getFile() . ":" . $e->getLine());
            $this->line("Trace: " . $e->getTraceAsString());
            
            return 1;
        }
    }
}
