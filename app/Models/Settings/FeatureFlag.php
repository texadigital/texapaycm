<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class FeatureFlag extends Model
{
    use HasFactory;

    protected $table = 'feature_flags';

    protected $fillable = [
        'key',
        'enabled',
        'rollout_percent',
        'description',
        'metadata',
        'category',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'rollout_percent' => 'integer',
        'metadata' => 'array',
    ];

    public static function isEnabled(string $key, ?int $bucket = null, bool $default = false): bool
    {
        $cacheKey = 'feature_flag:' . $key;
        $flag = Cache::remember($cacheKey, 300, fn () => self::where('key', $key)->first());
        if (!$flag) { return $default; }
        if (!$flag->enabled) { return false; }
        $percent = (int) ($flag->rollout_percent ?? 100);
        if ($percent >= 100) { return true; }
        if ($percent <= 0) { return false; }
        // Simple bucketing: require caller to pass stable bucket (e.g., user id hash 0..99)
        if ($bucket === null) { return true; }
        return ($bucket % 100) < $percent;
    }

    protected static function boot()
    {
        parent::boot();
        static::saved(function (self $model) {
            Cache::forget('feature_flag:' . $model->key);
        });
        static::deleted(function (self $model) {
            Cache::forget('feature_flag:' . $model->key);
        });
    }
}
