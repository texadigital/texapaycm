<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmlAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'actor_type', // user|admin|system
        'actor_id',
        'action', // screening.run, edd.opened, rule.eval, kyc.callback
        'subject_type', // user|transfer|edd_case|screening
        'subject_id',
        'payload',
        'checksum',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
