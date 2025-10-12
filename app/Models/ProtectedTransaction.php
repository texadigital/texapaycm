<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProtectedTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'buyer_user_id',
        'receiver_bank_code','receiver_bank_name','receiver_account_number','receiver_account_name',
        'name_enquiry_reference',
        'amount_ngn_minor','fee_ngn_minor','fee_rule_version','fee_components',
        'funding_source','funding_provider','funding_ref','funding_status',
        'escrow_state','auto_release_at','locked_at','released_at','disputed_at','resolved_at',
        'payout_ref','payout_status','payout_attempted_at','payout_completed_at',
        'va_account_number','va_bank_code','va_reference',
        'card_intent_id','card_provider_ref',
        'webhook_event_ids','audit_timeline',
    ];

    protected $casts = [
        'fee_components' => 'array',
        'webhook_event_ids' => 'array',
        'audit_timeline' => 'array',
        'auto_release_at' => 'datetime',
        'locked_at' => 'datetime',
        'released_at' => 'datetime',
        'disputed_at' => 'datetime',
        'resolved_at' => 'datetime',
        'payout_attempted_at' => 'datetime',
        'payout_completed_at' => 'datetime',
    ];

    // States
    public const STATE_CREATED = 'created';
    public const STATE_LOCKED = 'locked';
    public const STATE_AWAITING = 'awaiting_approval';
    public const STATE_RELEASED = 'released';
    public const STATE_DISPUTED = 'disputed';
    public const STATE_EXPIRED = 'expired';
    public const STATE_REFUNDED = 'refunded';
    public const STATE_PARTIAL_REFUND = 'partial_refund';

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(ProtectedAuditLog::class);
    }

    public function appendTimeline(array $entry): void
    {
        $timeline = $this->audit_timeline ?? [];
        $timeline[] = array_merge(['at' => now()->toISOString()], $entry);
        $this->audit_timeline = $timeline;
    }
}
