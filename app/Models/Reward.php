<?php
// app/Models/Reward.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Reward extends Model
{
    use HasFactory;

    protected $table = 'rewards';

    protected $fillable = [
        'employee_id',
        'unit_id',
        'reward_date',
        'amount',
        'reason',
        'status',
        'meal_transaction_id',
        'awarded_by',
        'sms_sent',
        'sms_sent_at',
        'sms_message_id',
        'sms_status',
        'sms_error'
    ];

    protected $casts = [
        'reward_date' => 'date',
        'amount' => 'decimal:2',
        'sms_sent' => 'boolean',
        'sms_sent_at' => 'datetime'
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function mealTransaction()
    {
        return $this->belongsTo(MealTransaction::class);
    }

    public function awardedBy()
    {
        return $this->belongsTo(User::class, 'awarded_by');
    }

    // Scopes
    public function scopeToday($query)
    {
        return $query->whereDate('reward_date', today());
    }

    public function scopeByUnit($query, $unitId)
    {
        return $query->where('unit_id', $unitId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeClaimed($query)
    {
        return $query->where('status', 'claimed');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        return 'KSh ' . number_format($this->amount, 2);
    }

    public function getIsExpiredAttribute()
    {
        return $this->reward_date->isPast() && $this->status === 'pending';
    }

    // Methods
    public function markAsClaimed($mealTransactionId)
    {
        $this->update([
            'status' => 'claimed',
            'meal_transaction_id' => $mealTransactionId
        ]);
    }

    public function markAsExpired()
    {
        $this->update(['status' => 'expired']);
    }

    /**
     * Get today's rewards (multiple allowed)
     */
    public static function getTodayRewards()
    {
        return static::with(['employee', 'employee.department', 'employee.unit', 'awardedBy'])
            ->whereDate('reward_date', today())
            ->get();
    }

    /**
     * Get today's reward for a specific unit
     */
    public static function getTodayRewardForUnit($unitId)
    {
        return static::with(['employee', 'employee.department', 'employee.unit'])
            ->whereDate('reward_date', today())
            ->where('unit_id', $unitId)
            ->first();
    }

    /**
     * Check if a unit already has a reward today
     */
    public static function unitHasRewardToday($unitId)
    {
        return static::whereDate('reward_date', today())
            ->where('unit_id', $unitId)
            ->exists();
    }

    /**
     * Get all rewards for today grouped by unit
     */
    public static function getTodayRewardsGroupedByUnit()
    {
        $rewards = self::getTodayRewards();

        return $rewards->groupBy('unit_id')->map(function($unitRewards) {
            return [
                'unit' => $unitRewards->first()->unit,
                'rewards' => $unitRewards,
                'total_amount' => $unitRewards->sum('amount'),
                'count' => $unitRewards->count()
            ];
        });
    }

    /**
     * Get available employees for reward (excluding recent winners)
     */
    public static function getAvailableEmployeesForReward($excludeDays = 7)
    {
        $recentWinners = static::where('reward_date', '>=', now()->subDays($excludeDays))
            ->pluck('employee_id')
            ->toArray();

        return Employee::where('is_active', true)
            ->whereNotIn('id', $recentWinners)
            ->with(['department', 'unit'])
            ->get();
    }

    /**
     * Get available employees for a specific unit
     */
    public static function getAvailableEmployeesForUnit($unitId, $excludeDays = 7)
    {
        $recentWinners = static::where('reward_date', '>=', now()->subDays($excludeDays))
            ->where('unit_id', $unitId)
            ->pluck('employee_id')
            ->toArray();

        return Employee::where('is_active', true)
            ->where('unit_id', $unitId)
            ->whereNotIn('id', $recentWinners)
            ->with(['department'])
            ->get();
    }

    /**
     * Create reward for a specific unit
     */
    public static function createUnitReward($unitId, $employee = null, $awardedBy = null)
    {
        // Check if unit already has reward today
        if (self::unitHasRewardToday($unitId)) {
            return null;
        }

        $employee = $employee ?? self::selectNextRewardRecipientForUnit($unitId);

        if (!$employee) {
            return null;
        }

        return static::create([
            'employee_id' => $employee->id,
            'unit_id' => $unitId,
            'reward_date' => today(),
            'amount' => 200.00,
            'reason' => 'Security vigilance reward',
            'status' => 'pending',
            'awarded_by' => $awardedBy ?? auth()->id()
        ]);
    }

    /**
     * Create multiple unit rewards at once
     */
    public static function createMultipleUnitRewards(array $unitRewards, $awardedBy = null)
    {
        $created = [];

        foreach ($unitRewards as $unitReward) {
            $reward = self::createUnitReward(
                $unitReward['unit_id'],
                $unitReward['employee_id'] ?? null,
                $awardedBy
            );

            if ($reward) {
                $created[] = $reward;
            }
        }

        return $created;
    }

    /**
     * Select next reward recipient for a specific unit
     */
    public static function selectNextRewardRecipientForUnit($unitId)
    {
        $availableEmployees = self::getAvailableEmployeesForUnit($unitId);

        if ($availableEmployees->isEmpty()) {
            // Fallback: include recent winners for this unit
            $availableEmployees = Employee::where('is_active', true)
                ->where('unit_id', $unitId)
                ->with(['department'])
                ->get();
        }

        return $availableEmployees->isEmpty() ? null : $availableEmployees->random();
    }

    /**
     * Get reward statistics for dashboard
     */
    public static function getStats()
    {
        return [
            'total_issued' => self::count(),
            'total_claimed' => self::where('status', 'claimed')->count(),
            'total_pending' => self::where('status', 'pending')->count(),
            'total_expired' => self::where('status', 'expired')->count(),
            'total_amount_distributed' => self::where('status', 'claimed')->sum('amount'),
            'unique_employees' => self::distinct('employee_id')->count('employee_id'),
            'today_count' => self::whereDate('reward_date', today())->count(),
            'today_amount' => self::whereDate('reward_date', today())->sum('amount'),
            'this_week_count' => self::where('reward_date', '>=', now()->startOfWeek())->count(),
            'this_month_count' => self::where('reward_date', '>=', now()->startOfMonth())->count(),
        ];
    }
}
