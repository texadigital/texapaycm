<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'label',
        'description',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];
}
