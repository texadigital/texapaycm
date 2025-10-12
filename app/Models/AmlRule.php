<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmlRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'key', // unique identifier
        'name',
        'description',
        'severity', // low|medium|high|critical
        'pack_id',
        'is_active',
        'expression', // JSON expression config
        'thresholds', // JSON thresholds (e.g., amount, counts)
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expression' => 'array',
        'thresholds' => 'array',
    ];

    public function pack()
    {
        return $this->belongsTo(AmlRulePack::class, 'pack_id');
    }
}
