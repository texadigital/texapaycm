<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount_xaf',
        'usd_to_xaf',
        'usd_to_ngn',
        'cross_rate_xaf_to_ngn',
        'adjusted_rate_xaf_to_ngn',
        'fee_total_xaf',
        'total_pay_xaf',
        'receive_ngn_minor',
        'status',
        'quote_ref',
        'expires_at',
        'fx_fetched_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'fx_fetched_at' => 'datetime',
    ];
}
