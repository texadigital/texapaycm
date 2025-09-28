<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel as FilamentPanel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'pin_hash',
        'is_admin',
        // Profile fields
        'full_name',
        'notification_email',
        'avatar_path',
        'phone_verified_at',
        'profile_completed_at',
        // Notification prefs (if present)
        'email_notifications',
        'sms_notifications',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'pin_hash',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'profile_completed_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'email_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
        ];
    }

    /**
     * Get the user's transaction limit
     */
    public function userLimit()
    {
        return $this->hasOne(UserLimit::class);
    }

    /**
     * Get the user's daily transaction summaries
     */
    public function dailyTransactionSummaries()
    {
        return $this->hasMany(DailyTransactionSummary::class);
    }

    /**
     * Security settings relation
     */
    public function securitySettings()
    {
        return $this->hasOne(UserSecuritySetting::class);
    }

    /**
     * Ensure a settings row exists
     */
    public function getOrCreateSecuritySettings(): UserSecuritySetting
    {
        return $this->securitySettings ?: $this->securitySettings()->create();
    }

    /**
     * Get or create user limits
     */
    public function getOrCreateLimits(): UserLimit
    {
        return $this->userLimit ?: UserLimit::createDefaultForUser($this);
    }

    /**
     * Check if user can make a transaction
     */
    public function canMakeTransaction(int $amount): array
    {
        $limits = $this->getOrCreateLimits();
        
        if (!$limits->is_active) {
            return [
                'can_transact' => false,
                'reason' => 'Transaction limits are disabled for your account.',
                'limit_type' => 'disabled'
            ];
        }

        // Get current usage
        $dailyUsage = DailyTransactionSummary::getDailyUsage($this->id);
        $monthlyUsage = DailyTransactionSummary::getMonthlyUsage($this->id);

        // Check daily amount limit
        if ($limits->isDailyLimitExceeded($dailyUsage['amount'], $amount)) {
            return [
                'can_transact' => false,
                'reason' => 'Daily transaction limit exceeded.',
                'limit_type' => 'daily_amount',
                'current_usage' => $dailyUsage['amount'],
                'limit' => $limits->daily_limit_xaf,
                'remaining' => $limits->getRemainingDailyLimit($dailyUsage['amount'])
            ];
        }

        // Check monthly amount limit
        if ($limits->isMonthlyLimitExceeded($monthlyUsage['total_amount'], $amount)) {
            return [
                'can_transact' => false,
                'reason' => 'Monthly transaction limit exceeded.',
                'limit_type' => 'monthly_amount',
                'current_usage' => $monthlyUsage['total_amount'],
                'limit' => $limits->monthly_limit_xaf,
                'remaining' => $limits->getRemainingMonthlyLimit($monthlyUsage['total_amount'])
            ];
        }

        // Check daily count limit
        if ($limits->isDailyCountLimitExceeded($dailyUsage['count'])) {
            return [
                'can_transact' => false,
                'reason' => 'Daily transaction count limit exceeded.',
                'limit_type' => 'daily_count',
                'current_usage' => $dailyUsage['count'],
                'limit' => $limits->daily_count_limit
            ];
        }

        // Check monthly count limit
        if ($limits->isMonthlyCountLimitExceeded($monthlyUsage['total_count'])) {
            return [
                'can_transact' => false,
                'reason' => 'Monthly transaction count limit exceeded.',
                'limit_type' => 'monthly_count',
                'current_usage' => $monthlyUsage['total_count'],
                'limit' => $limits->monthly_count_limit
            ];
        }

        return [
            'can_transact' => true,
            'daily_remaining' => $limits->getRemainingDailyLimit($dailyUsage['amount']),
            'monthly_remaining' => $limits->getRemainingMonthlyLimit($monthlyUsage['total_amount']),
            'daily_utilization' => $limits->getDailyUtilizationPercentage($dailyUsage['amount']),
            'monthly_utilization' => $limits->getMonthlyUtilizationPercentage($monthlyUsage['total_amount'])
        ];
    }

    /**
     * Restrict Filament panel access to admins only.
     */
    public function canAccessPanel(FilamentPanel $panel): bool
    {
        return (bool) ($this->is_admin ?? false);
    }
}

