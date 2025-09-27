<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    protected $fillable = [
        'user_id',
        'quote_id',
        'recipient_bank_code',
        'recipient_bank_name',
        'recipient_account_number',
        'recipient_account_name',
        'name_enquiry_reference',
        'amount_xaf',
        'fee_total_xaf',
        'total_pay_xaf',
        'receive_ngn_minor',
        'adjusted_rate_xaf_to_ngn',
        'usd_to_xaf',
        'usd_to_ngn',
        'fx_fetched_at',
        'payin_provider',
        'payin_ref',
        'payin_status',
        'payin_at',
        'payout_provider',
        'payout_ref',
        'payout_status',
        'payout_initiated_at',
        'payout_attempted_at',
        'payout_completed_at',
        'payout_idempotency_key',
        'last_payout_error',
        'refund_id',
        'refund_status',
        'refund_attempted_at',
        'refund_completed_at',
        'refund_response',
        'refund_error',
        'status',
        'timeline',
    ];

    protected $casts = [
        'fx_fetched_at' => 'datetime',
        'payin_at' => 'datetime',
        'payout_initiated_at' => 'datetime',
        'payout_attempted_at' => 'datetime',
        'payout_completed_at' => 'datetime',
        'refund_attempted_at' => 'datetime',
        'refund_completed_at' => 'datetime',
        'refund_response' => 'array',
        'timeline' => 'array',
    ];
    
    /**
     * Scope a query to only include transfers that need refund processing.
     */
    public function scopeNeedsRefund($query)
    {
        return $query->where(function($q) {
            $q->where('payout_status', 'failed')
              ->where(function($q) {
                  $q->whereNull('refund_status')
                    ->orWhere('refund_status', 'PENDING');
              });
        })->where('payin_status', 'success');
    }
    
    /**
     * Check if the transfer is eligible for a refund.
     */
    public function isEligibleForRefund(): bool
    {
        return $this->payin_status === 'success' && 
               $this->payout_status === 'failed' && 
               empty($this->refund_id) && 
               !in_array($this->refund_status, ['SUCCESS', 'COMPLETED']);
    }

    /**
     * Scope a query to only include transfers that need payout processing.
     */
    public function scopeNeedsPayout($query)
    {
        return $query->where(function($q) {
            $q->whereNull('payout_status')
              ->orWhere('payout_status', 'pending')
              ->orWhere(function($q) {
                  $q->where('payout_status', 'processing')
                    ->where('payout_attempted_at', '<', now()->subMinutes(15));
              });
        });
    }
}
