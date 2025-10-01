<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'notification_type',
        'email_enabled',
        'sms_enabled',
        'push_enabled',
        'in_app_enabled',
    ];

    protected $casts = [
        'email_enabled' => 'boolean',
        'sms_enabled' => 'boolean',
        'push_enabled' => 'boolean',
        'in_app_enabled' => 'boolean',
    ];

    /**
     * Get the user that owns the preference
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get or create default preferences for a user and notification type
     */
    public static function getOrCreateDefault(User $user, string $notificationType): self
    {
        return static::firstOrCreate(
            [
                'user_id' => $user->id,
                'notification_type' => $notificationType,
            ],
            [
                'email_enabled' => true,
                'sms_enabled' => false,
                'push_enabled' => false,
                'in_app_enabled' => true,
            ]
        );
    }

    /**
     * Check if a specific channel is enabled
     */
    public function isChannelEnabled(string $channel): bool
    {
        return match ($channel) {
            'email' => $this->email_enabled,
            'sms' => $this->sms_enabled,
            'push' => $this->push_enabled,
            'in_app' => $this->in_app_enabled,
            default => false,
        };
    }

    /**
     * Get enabled channels as array
     */
    public function getEnabledChannels(): array
    {
        $channels = [];
        
        if ($this->email_enabled) $channels[] = 'email';
        if ($this->sms_enabled) $channels[] = 'sms';
        if ($this->push_enabled) $channels[] = 'push';
        if ($this->in_app_enabled) $channels[] = 'in_app';
        
        return $channels;
    }
}


