<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LimitRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'scope',        // e.g., user, role, instrument, corridor
        'key',          // e.g., user_id or role name
        'metric',       // daily_amount, monthly_amount, per_tx_amount, daily_count, monthly_count
        'threshold',    // integer value in XAF or count
        'window',       // daily, monthly, per_tx
        'active',
        'metadata',
    ];

    protected $casts = [
        'threshold' => 'integer',
        'active' => 'boolean',
        'metadata' => 'array',
    ];
}
