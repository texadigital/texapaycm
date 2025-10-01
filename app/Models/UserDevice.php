<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class UserDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_token',
        'platform',
        'device_id',
        'app_version',
        'os_version',
        'is_active',
        'last_used_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    /**
     * Get the user that owns the device
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Register or update a device for a user
     */
    public static function registerDevice(
        User $user,
        string $deviceToken,
        string $platform = 'android',
        ?string $deviceId = null,
        ?string $appVersion = null,
        ?string $osVersion = null
    ): self {
        return static::updateOrCreate(
            [
                'user_id' => $user->id,
                'device_token' => $deviceToken,
            ],
            [
                'platform' => $platform,
                'device_id' => $deviceId,
                'app_version' => $appVersion,
                'os_version' => $osVersion,
                'is_active' => true,
                'last_used_at' => now(),
            ]
        );
    }

    /**
     * Deactivate a device
     */
    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    /**
     * Get active devices for a user
     */
    public static function getActiveDevicesForUser(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Clean up old inactive devices (older than 30 days)
     */
    public static function cleanupOldDevices(): int
    {
        return static::where('is_active', false)
            ->where('updated_at', '<', now()->subDays(30))
            ->delete();
    }

    /**
     * Check if device token is valid format
     */
    public static function isValidDeviceToken(string $token): bool
    {
        // FCM tokens are typically 152+ characters
        // APNS tokens are typically 64 characters
        return strlen($token) >= 64 && strlen($token) <= 255;
    }

    /**
     * Get platform-specific icon
     */
    public function getPlatformIcon(): string
    {
        return match ($this->platform) {
            'android' => '🤖',
            'ios' => '🍎',
            'web' => '🌐',
            default => '📱',
        };
    }

    /**
     * Get device display name
     */
    public function getDisplayName(): string
    {
        $parts = [];
        
        if ($this->app_version) {
            $parts[] = "v{$this->app_version}";
        }
        
        if ($this->os_version) {
            $parts[] = $this->os_version;
        }
        
        $suffix = $parts ? ' (' . implode(', ', $parts) . ')' : '';
        
        return ucfirst($this->platform) . $suffix;
    }
}

