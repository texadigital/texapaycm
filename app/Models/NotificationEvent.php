<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_type',
        'event_key',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the event
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the event has been processed
     */
    public function isProcessed(): bool
    {
        return !is_null($this->processed_at);
    }

    /**
     * Mark the event as processed
     */
    public function markAsProcessed(): bool
    {
        return $this->update(['processed_at' => now()]);
    }

    /**
     * Generate a unique event key for deduplication
     */
    public static function generateEventKey(string $eventType, array $payload = []): string
    {
        // Create a hash based on event type and payload for deduplication
        $keyData = $eventType . ':' . json_encode($payload, JSON_SORT_KEYS);
        return hash('sha256', $keyData);
    }
}


