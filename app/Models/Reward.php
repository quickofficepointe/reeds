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

    // Scopes
    public function scopeToday($query)
    {
        return $query->whereDate('reward_date', today());
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeClaimed($query)
    {
        return $query->where('status', 'claimed');
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
     * Get today's reward (if any)
     */
    public static function getTodayReward()
    {
        return static::with(['employee', 'employee.department', 'employee.unit'])
            ->whereDate('reward_date', today())
            ->first();
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
     * Select next day's reward recipient
     */
    public static function selectNextRewardRecipient()
    {
        $availableEmployees = self::getAvailableEmployeesForReward();

        if ($availableEmployees->isEmpty()) {
            // Fallback: include recent winners if no other employees
            $availableEmployees = Employee::where('is_active', true)
                ->with(['department', 'unit'])
                ->get();
        }

        return $availableEmployees->random();
    }

    /**
     * Create reward for tomorrow
     */
    public static function createTomorrowReward($employee = null)
    {
        // Check if reward already exists for tomorrow
        $existing = static::whereDate('reward_date', now()->addDay())->first();
        if ($existing) {
            return $existing;
        }

        $employee = $employee ?? self::selectNextRewardRecipient();

        return static::create([
            'employee_id' => $employee->id,
            'unit_id' => $employee->unit_id,
            'reward_date' => now()->addDay(),
            'amount' => 200.00,
            'reason' => 'Security vigilance reward',
            'status' => 'pending'
        ]);
    }
}
