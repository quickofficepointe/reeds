<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MealTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'employee_id',
        'transaction_code',
        'amount',
        'meal_date',
        'meal_time',
        'qr_code_scanned',
        'scan_data',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'meal_date' => 'date',
        'scan_data' => 'array',
    ];

    // =============================================
    // RELATIONSHIPS
    // =============================================

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function reward()
    {
        return $this->belongsTo(Reward::class);
    }

    // =============================================
    // REWARD DETECTION METHODS
    // =============================================

    /**
     * Check if this transaction was a security reward meal
     * Reads from scan_data JSON field - NO new database columns needed
     */
    public function isRewardMeal(): bool
    {
        $scanData = $this->scan_data;
        return $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
    }

    /**
     * Get the reward ID associated with this meal
     */
    public function getRewardId()
    {
        $scanData = $this->scan_data;
        return $scanData['reward_id'] ?? null;
    }

    /**
     * Get the effective amount (200 for reward, 65 for regular)
     * This ensures reports show the correct value
     */
    public function getEffectiveAmount(): float
    {
        if ($this->isRewardMeal()) {
            return 200.00;
        }
        return 65.00; // Regular meal amount
    }

    /**
     * Get the unit name from scan_data
     */
    public function getUnitNameFromScan(): ?string
    {
        $scanData = $this->scan_data;
        return $scanData['unit_name'] ?? $scanData['unit'] ?? null;
    }

    /**
     * Get the unit ID from scan_data
     */
    public function getUnitIdFromScan(): ?int
    {
        $scanData = $this->scan_data;
        return $scanData['unit_id'] ?? null;
    }

    /**
     * Get display amount with reward indicator
     */
    public function getDisplayAmountAttribute(): string
    {
        if ($this->isRewardMeal()) {
            return 'KSh ' . number_format($this->amount, 2) . ' 🎖️ (Reward)';
        }
        return 'KSh ' . number_format($this->amount, 2);
    }

    /**
     * Get the correct amount for reports (65 or 200 based on type)
     */
    public function getReportAmountAttribute(): float
    {
        return $this->getEffectiveAmount();
    }

    // =============================================
    // SCOPES
    // =============================================

    /**
     * Scope to get only regular meals (non-reward)
     * Uses JSON_EXTRACT for MySQL - works with existing scan_data column
     */
    public function scopeRegularMeals($query)
    {
        return $query->where(function($q) {
            $q->whereNull('scan_data')
              ->orWhereRaw('JSON_EXTRACT(scan_data, "$.is_reward") IS NULL')
              ->orWhereRaw('JSON_EXTRACT(scan_data, "$.is_reward") = false');
        });
    }

    /**
     * Scope to get only reward meals
     * Uses JSON_EXTRACT for MySQL - works with existing scan_data column
     */
    public function scopeRewardMeals($query)
    {
        return $query->whereRaw('JSON_EXTRACT(scan_data, "$.is_reward") = true');
    }

    /**
     * Scope for today's transactions
     */
    public function scopeToday($query)
    {
        return $query->whereDate('meal_date', today());
    }

    /**
     * Scope for vendor's transactions
     */
    public function scopeByVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    /**
     * Scope for employee's transactions
     */
    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope for date range
     */
    public function scopeDateBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('meal_date', [$startDate, $endDate]);
    }

    // =============================================
    // TRANSACTION CODE GENERATION
    // =============================================

    /**
     * Generate unique transaction code
     */
    public static function generateTransactionCode(): string
    {
        do {
            $code = 'TXN' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (static::where('transaction_code', $code)->exists());

        return $code;
    }

    // =============================================
    // BOOT METHOD
    // =============================================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->transaction_code)) {
                $transaction->transaction_code = static::generateTransactionCode();
            }

            if (empty($transaction->meal_time)) {
                $transaction->meal_time = now()->format('H:i:s');
            }
        });
    }

    // =============================================
    // AGGREGATION HELPERS
    // =============================================

    /**
     * Get total revenue for a collection of transactions
     * Respects reward vs regular meal amounts
     */
    public static function calculateTotalRevenue($transactions): float
    {
        return $transactions->sum(function($meal) {
            return $meal->getEffectiveAmount();
        });
    }

    /**
     * Get regular meal revenue for a collection
     */
    public static function calculateRegularRevenue($transactions): float
    {
        return $transactions->filter(function($meal) {
            return !$meal->isRewardMeal();
        })->sum(function($meal) {
            return 65.00;
        });
    }

    /**
     * Get reward meal revenue for a collection
     */
    public static function calculateRewardRevenue($transactions): float
    {
        return $transactions->filter(function($meal) {
            return $meal->isRewardMeal();
        })->sum(function($meal) {
            return 200.00;
        });
    }
}
