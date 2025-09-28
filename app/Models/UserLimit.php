<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserLimit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'daily_limit_xaf',
        'monthly_limit_xaf',
        'daily_count_limit',
        'monthly_count_limit',
        'is_active',
        'notes',
        'last_updated_by_admin',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_updated_by_admin' => 'datetime',
    ];

    /**
     * Get the user that owns the limit
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if daily limit is exceeded
     */
    public function isDailyLimitExceeded(int $currentDailyAmount, int $newAmount): bool
    {
        if (!$this->is_active) {
            return false;
        }
        
        return ($currentDailyAmount + $newAmount) > $this->daily_limit_xaf;
    }

    /**
     * Check if monthly limit is exceeded
     */
    public function isMonthlyLimitExceeded(int $currentMonthlyAmount, int $newAmount): bool
    {
        if (!$this->is_active) {
            return false;
        }
        
        return ($currentMonthlyAmount + $newAmount) > $this->monthly_limit_xaf;
    }

    /**
     * Check if daily count limit is exceeded
     */
    public function isDailyCountLimitExceeded(int $currentDailyCount): bool
    {
        if (!$this->is_active) {
            return false;
        }
        
        return ($currentDailyCount + 1) > $this->daily_count_limit;
    }

    /**
     * Check if monthly count limit is exceeded
     */
    public function isMonthlyCountLimitExceeded(int $currentMonthlyCount): bool
    {
        if (!$this->is_active) {
            return false;
        }
        
        return ($currentMonthlyCount + 1) > $this->monthly_count_limit;
    }

    /**
     * Get remaining daily limit
     */
    public function getRemainingDailyLimit(int $currentDailyAmount): int
    {
        return max(0, $this->daily_limit_xaf - $currentDailyAmount);
    }

    /**
     * Get remaining monthly limit
     */
    public function getRemainingMonthlyLimit(int $currentMonthlyAmount): int
    {
        return max(0, $this->monthly_limit_xaf - $currentMonthlyAmount);
    }

    /**
     * Get limit utilization percentage
     */
    public function getDailyUtilizationPercentage(int $currentDailyAmount): float
    {
        if ($this->daily_limit_xaf <= 0) {
            return 0;
        }
        
        return min(100, ($currentDailyAmount / $this->daily_limit_xaf) * 100);
    }

    /**
     * Get monthly utilization percentage
     */
    public function getMonthlyUtilizationPercentage(int $currentMonthlyAmount): float
    {
        if ($this->monthly_limit_xaf <= 0) {
            return 0;
        }
        
        return min(100, ($currentMonthlyAmount / $this->monthly_limit_xaf) * 100);
    }

    /**
     * Create default limits for a user
     */
    public static function createDefaultForUser(User $user): self
    {
        $defaultDailyLimit = AdminSetting::getValue('default_daily_limit', 500000);
        $defaultMonthlyLimit = AdminSetting::getValue('default_monthly_limit', 5000000);
        $defaultDailyCount = AdminSetting::getValue('default_daily_count', 10);
        $defaultMonthlyCount = AdminSetting::getValue('default_monthly_count', 100);

        return self::create([
            'user_id' => $user->id,
            'daily_limit_xaf' => $defaultDailyLimit,
            'monthly_limit_xaf' => $defaultMonthlyLimit,
            'daily_count_limit' => $defaultDailyCount,
            'monthly_count_limit' => $defaultMonthlyCount,
            'is_active' => true,
        ]);
    }
}
