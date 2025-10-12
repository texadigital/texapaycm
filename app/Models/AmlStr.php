<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmlStr extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'transfer_id',
        'reason',
        'status', // draft|submitted|rejected
        'submitted_at',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
        'submitted_at' => 'datetime',
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
