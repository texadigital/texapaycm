<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginHistory extends Model
{
    use HasFactory;

    // Migration created a singular table name; bind explicitly
    protected $table = 'login_history';

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'login_method',
        'status',
        'location',
        'device_info',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
