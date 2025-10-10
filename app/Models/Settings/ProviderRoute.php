<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderRoute extends Model
{
    use HasFactory;

    protected $fillable = [
        'corridor',      // e.g., XAF_NGN
        'provider_code', // e.g., MTN_MOMO_CMR
        'weight',        // routing weight
        'msisdn_prefixes', // array of 3-digit prefixes or ranges
        'active',
        'metadata',
    ];

    protected $casts = [
        'weight' => 'integer',
        'msisdn_prefixes' => 'array',
        'active' => 'boolean',
        'metadata' => 'array',
    ];
}
