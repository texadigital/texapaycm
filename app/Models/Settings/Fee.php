<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fee extends Model
{
    use HasFactory;

    protected $fillable = [
        'corridor',
        'min_xaf',
        'max_xaf',
        'flat_xaf',
        'percent_bps',
        'cap_xaf',
        'active',
    ];

    protected $casts = [
        'min_xaf' => 'integer',
        'max_xaf' => 'integer',
        'flat_xaf' => 'integer',
        'percent_bps' => 'integer',
        'cap_xaf' => 'integer',
        'active' => 'boolean',
    ];
}
