<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_user_id',
        'action',
        'subject_type',
        'subject_id',
        'changes_before',
        'changes_after',
        'meta',
    ];

    protected $casts = [
        'changes_before' => 'array',
        'changes_after' => 'array',
        'meta' => 'array',
    ];
}
