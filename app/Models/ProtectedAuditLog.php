<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProtectedAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'protected_transaction_id',
        'actor_type',
        'actor_id',
        'from_state',
        'to_state',
        'at',
        'reason',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'at' => 'datetime',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(ProtectedTransaction::class, 'protected_transaction_id');
    }
}
