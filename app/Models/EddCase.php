<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EddCase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'case_ref',
        'risk_reason',
        'trigger_source',
        'status',
        'owner_id',
        'sla_due_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sla_due_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
