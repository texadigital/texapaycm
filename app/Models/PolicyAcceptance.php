<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PolicyAcceptance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'terms_version',
        'privacy_version',
        'accepted_at',
        'signature',
        'signature_hash',
        'ip',
        'user_agent',
    ];
}
