<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;

class AdminSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'setting_key',
        'setting_value',
        'setting_type',
        'description',
        'is_public',
        'category',
        'sort_order',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get a setting value with caching
     */
    public static function getValue(string $key, $default = null)
    {
        $cacheKey = "admin_setting_{$key}";
        
        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $setting = self::where('setting_key', $key)->first();
            
            if (!$setting) {
                return $default;
            }
            
            return self::castValue($setting->setting_value, $setting->setting_type);
        });
    }

    /**
     * Set a setting value
     */
    public static function setValue(string $key, $value, string $type = 'string', string $description = null, string $category = 'general'): self
    {
        $setting = self::updateOrCreate(
            ['setting_key' => $key],
            [
                'setting_value' => self::prepareValue($value, $type),
                'setting_type' => $type,
                'description' => $description,
                'category' => $category,
            ]
        );

        // Clear cache
        Cache::forget("admin_setting_{$key}");

        return $setting;
    }

    /**
     * Get all settings by category
     */
    public static function getByCategory(string $category): array
    {
        $cacheKey = "admin_settings_category_{$category}";
        
        return Cache::remember($cacheKey, 3600, function () use ($category) {
            return self::where('category', $category)
                ->orderBy('sort_order')
                ->orderBy('setting_key')
                ->get()
                ->mapWithKeys(function ($setting) {
                    return [
                        $setting->setting_key => self::castValue($setting->setting_value, $setting->setting_type)
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get all public settings (accessible by non-admin users)
     */
    public static function getPublicSettings(): array
    {
        $cacheKey = "admin_settings_public";
        
        return Cache::remember($cacheKey, 3600, function () {
            return self::where('is_public', true)
                ->orderBy('category')
                ->orderBy('sort_order')
                ->get()
                ->mapWithKeys(function ($setting) {
                    return [
                        $setting->setting_key => self::castValue($setting->setting_value, $setting->setting_type)
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Cast value to appropriate type
     */
    protected static function castValue($value, string $type)
    {
        switch ($type) {
            case 'integer':
                return (int) $value;
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'json':
                return json_decode($value, true);
            case 'string':
            default:
                return (string) $value;
        }
    }

    /**
     * Prepare value for storage
     */
    protected static function prepareValue($value, string $type): string
    {
        switch ($type) {
            case 'json':
                return json_encode($value);
            case 'boolean':
                return $value ? '1' : '0';
            default:
                return (string) $value;
        }
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache(): void
    {
        $settings = self::all();
        
        foreach ($settings as $setting) {
            Cache::forget("admin_setting_{$setting->setting_key}");
        }
        
        // Clear category caches
        $categories = self::distinct('category')->pluck('category');
        foreach ($categories as $category) {
            Cache::forget("admin_settings_category_{$category}");
        }
        
        Cache::forget("admin_settings_public");
    }

    /**
     * Boot method to clear cache on model events
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($setting) {
            Cache::forget("admin_setting_{$setting->setting_key}");
            Cache::forget("admin_settings_category_{$setting->category}");
            Cache::forget("admin_settings_public");
        });

        static::deleted(function ($setting) {
            Cache::forget("admin_setting_{$setting->setting_key}");
            Cache::forget("admin_settings_category_{$setting->category}");
            Cache::forget("admin_settings_public");
        });
    }
}
