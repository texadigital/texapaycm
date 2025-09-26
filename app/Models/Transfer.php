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
        'payout_completed_at',
        'status',
        'timeline',
    ];

    protected $casts = [
        'fx_fetched_at' => 'datetime',
        'payin_at' => 'datetime',
        'payout_initiated_at' => 'datetime',
        'payout_completed_at' => 'datetime',
        'timeline' => 'array',
    ];
}
