<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmlAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'transfer_id',
        'rule_key',
        'severity',
        'status',
        'notes',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }
}
