<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScreeningCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type', // onboarding|kyc_update|login|periodic
        'provider',
        'status', // running|completed|failed
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function results()
    {
        return $this->hasMany(ScreeningResult::class);
    }
}
