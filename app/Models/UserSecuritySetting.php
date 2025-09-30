<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSecuritySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'pin_enabled',
        'pin_hash',
        'sms_login_enabled',
        'face_id_enabled',
        'last_security_update',
    ];

    protected $casts = [
        'two_factor_enabled' => 'boolean',
        'pin_enabled' => 'boolean',
        'sms_login_enabled' => 'boolean',
        'face_id_enabled' => 'boolean',
        'last_security_update' => 'datetime',
        'two_factor_recovery_codes' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
