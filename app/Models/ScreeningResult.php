<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScreeningResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'screening_check_id',
        'match_type', // sanctions|pep|adverse|summary
        'name',
        'list_source',
        'score',
        'decision', // pass|review|fail
        'raw',
    ];

    protected $casts = [
        'raw' => 'array',
    ];

    public function check()
    {
        return $this->belongsTo(ScreeningCheck::class, 'screening_check_id');
    }
}
