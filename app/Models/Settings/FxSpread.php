<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FxSpread extends Model
{
    use HasFactory;

    protected $fillable = [
        'corridor', // e.g., XAF_NGN
        'provider', // optional provider code
        'margin_bps',
        'active',
    ];

    protected $casts = [
        'margin_bps' => 'integer',
        'active' => 'boolean',
    ];
}
