<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmlRulePack extends Model
{
    use HasFactory;

    protected $fillable = [
        'key', // unique identifier
        'name',
        'description',
        'is_active',
        'tags', // JSON array of tags like ["atf","pep"]
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'tags' => 'array',
    ];

    public function rules()
    {
        return $this->hasMany(AmlRule::class, 'pack_id');
    }
}
