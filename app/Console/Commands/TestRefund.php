<?php

namespace App\Console\Commands;

use App\Models\Transfer;
use App\Services\RefundService;
use Illuminate\Console\Command;

class TestRefund extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:refund {transfer_id} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test refund functionality for a transfer';

    /**
     * Execute the console command.
     */
    public function handle(RefundService $refundService)
    {
        $transferId = $this->argument('transfer_id');
        $force = $this->option('force');
        
        $this->info("Testing refund for transfer ID: {$transferId}");
        
        $transfer = Transfer::find($transferId);
        
        if (!$transfer) {
            $this->error("Transfer not found with ID: {$transferId}");
            return 1;
        }
        
        $this->info("Transfer found:");
        $this->line("  - ID: {$transfer->id}");
        $this->line("  - Status: {$transfer->status}");
        $this->line("  - Payin Status: {$transfer->payin_status}");
        $this->line("  - Payout Status: {$transfer->payout_status}");
        $this->line("  - Refund Status: {$transfer->refund_status}");
        $this->line("  - Amount: {$transfer->amount_xaf} XAF");
        
        if (!$force) {
            if (!$transfer->isEligibleForRefund()) {
                $this->error("This transfer is not eligible for refund. Use --force to override.");
                $this->line("Eligibility check failed for the following reasons:");
                $this->line("- Payin status is not success: " . ($transfer->payin_status === 'success' ? '✓' : '✗'));
                $this->line("- Payout status is not failed: " . ($transfer->payout_status === 'failed' ? '✓' : '✗'));
                $this->line("- Refund ID is empty: " . (empty($transfer->refund_id) ? '✓' : '✗'));
                $this->line("- Refund status is not already SUCCESS/COMPLETED: " . (!in_array($transfer->refund_status, ['SUCCESS', 'COMPLETED']) ? '✓' : '✗'));
                
                if ($this->confirm('Do you want to see the full transfer details?', false)) {
                    dd($transfer->toArray());
                }
                
                return 1;
            }
            
            if (!$this->confirm('Are you sure you want to proceed with the refund?', true)) {
                $this->info('Refund cancelled.');
                return 0;
            }
        }
        
        $this->info("Initiating refund...");
        
        try {
            $result = $refundService->refundFailedPayout($transfer);
            
            if ($result['success']) {
                $this->info("✅ Refund initiated successfully!");
                $this->line("Refund ID: " . $result['refund_id']);
                $this->line("Status: " . $result['data']['status'] ?? 'unknown');
                
                if (isset($result['data']['createdAt'])) {
                    $this->line("Created At: " . $result['data']['createdAt']);
                }
                
                $this->info("\nYou can check the status with: php artisan test:refund:status {$result['refund_id']}");
            } else {
                $this->error("❌ Failed to initiate refund");
                $this->line("Error: " . ($result['message'] ?? 'Unknown error'));
                
                if (isset($result['error'])) {
                    $this->line("Details: " . json_encode($result['error'], JSON_PRETTY_PRINT));
                }
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Exception: " . $e->getMessage());
            $this->line("File: " . $e->getFile() . ":" . $e->getLine());
            $this->line("Trace: " . $e->getTraceAsString());
            
            return 1;
        }
    }
}
