<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Unit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'location',
        'description',
        'capacity',
        'current_employee_count',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'capacity' => 'integer',
        'current_employee_count' => 'integer'
    ];

    // =============================================
    // RELATIONSHIPS
    // =============================================

    /**
     * Get the employees for the unit.
     */
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Get the users (including vendors) for the unit.
     */
    public function unitUsers()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the vendors for the unit.
     */
    public function unitVendors()
    {
        return $this->hasMany(User::class)->where('role', User::ROLE_VENDOR);
    }

    /**
     * Get meal transactions through employees.
     * This allows $unit->mealTransactions() to work without unit_id in meal_transactions table.
     */
    public function mealTransactions()
    {
        return $this->hasManyThrough(
            MealTransaction::class,
            Employee::class,
            'unit_id',      // Foreign key on employees table
            'employee_id',  // Foreign key on meal_transactions table
            'id',           // Local key on units table
            'id'            // Local key on employees table
        );
    }

    /**
     * Get regular meals (non-reward) for this unit
     */
    public function regularMeals()
    {
        return $this->mealTransactions()->regularMeals();
    }

    /**
     * Get reward meals for this unit
     */
    public function rewardMeals()
    {
        return $this->mealTransactions()->rewardMeals();
    }

    // =============================================
    // REWARD-SPECIFIC METHODS
    // =============================================

    /**
     * Get today's regular meals for this unit (already filtered through mealTransactions)
     */
    public function getTodayRegularMeals()
    {
        return $this->mealTransactions()
            ->whereDate('meal_date', today())
            ->get()
            ->filter(fn($meal) => !$meal->isRewardMeal());
    }

    /**
     * Get today's reward meals for this unit
     */
    public function getTodayRewardMeals()
    {
        return $this->mealTransactions()
            ->whereDate('meal_date', today())
            ->get()
            ->filter(fn($meal) => $meal->isRewardMeal());
    }

    /**
     * Get today's reward meals count
     */
    public function getTodayRewardCountAttribute()
    {
        return $this->getTodayRewardMeals()->count();
    }

    /**
     * Get today's regular meals count
     */
    public function getTodayRegularCountAttribute()
    {
        return $this->getTodayRegularMeals()->count();
    }

    /**
     * Get today's reward revenue (200 KES each)
     */
    public function getTodayRewardRevenueAttribute()
    {
        return $this->getTodayRewardMeals()->count() * 200.00;
    }

    /**
     * Get today's regular revenue (65 KES each)
     */
    public function getTodayRegularRevenueAttribute()
    {
        return $this->getTodayRegularMeals()->count() * 65.00;
    }

    // =============================================
    // STATS ATTRIBUTES
    // =============================================

    /**
     * Get today's total scans count (regular + reward)
     */
    public function getTodayTotalScansAttribute()
    {
        return $this->mealTransactions()->whereDate('meal_date', today())->count();
    }

    /**
     * Get today's total revenue (regular: 65, reward: 200)
     */
    public function getTodayRevenueAttribute()
    {
        $transactions = $this->mealTransactions()->whereDate('meal_date', today())->get();

        return $transactions->sum(function($meal) {
            return $meal->getEffectiveAmount();
        });
    }

    /**
     * Get this month's scans count
     */
    public function getMonthScansAttribute()
    {
        return $this->mealTransactions()
            ->where('meal_date', '>=', now()->startOfMonth())
            ->count();
    }

    /**
     * Get this month's reward scans count
     */
    public function getMonthRewardScansAttribute()
    {
        return $this->mealTransactions()
            ->where('meal_date', '>=', now()->startOfMonth())
            ->get()
            ->filter(fn($meal) => $meal->isRewardMeal())
            ->count();
    }

    /**
     * Get this month's regular scans count
     */
    public function getMonthRegularScansAttribute()
    {
        return $this->mealTransactions()
            ->where('meal_date', '>=', now()->startOfMonth())
            ->get()
            ->filter(fn($meal) => !$meal->isRewardMeal())
            ->count();
    }

    /**
     * Get this month's revenue
     */
    public function getMonthRevenueAttribute()
    {
        $transactions = $this->mealTransactions()
            ->where('meal_date', '>=', now()->startOfMonth())
            ->get();

        return $transactions->sum(function($meal) {
            return $meal->getEffectiveAmount();
        });
    }

    /**
     * Get this month's reward revenue
     */
    public function getMonthRewardRevenueAttribute()
    {
        return $this->getMonthRewardScansAttribute * 200.00;
    }

    /**
     * Get this month's regular revenue
     */
    public function getMonthRegularRevenueAttribute()
    {
        return $this->getMonthRegularScansAttribute * 65.00;
    }

    /**
     * Get total scans all time
     */
    public function getTotalScansAttribute()
    {
        return $this->mealTransactions()->count();
    }

    /**
     * Get total revenue all time (regular: 65, reward: 200)
     */
    public function getTotalRevenueAttribute()
    {
        $transactions = $this->mealTransactions()->get();

        return $transactions->sum(function($meal) {
            return $meal->getEffectiveAmount();
        });
    }

    /**
     * Get active vendors count for this unit
     */
    public function getActiveVendorsCountAttribute()
    {
        return User::where('role', 2)
            ->where('unit_id', $this->id)
            ->whereHas('profile', function($q) {
                $q->where('is_verified', true);
            })
            ->count();
    }

    /**
     * Check if unit is full
     */
    public function getIsFullAttribute()
    {
        if (!$this->capacity) {
            return false;
        }
        return $this->current_employee_count >= $this->capacity;
    }

    /**
     * Get available slots
     */
    public function getAvailableSlotsAttribute()
    {
        if (!$this->capacity) {
            return null;
        }
        return $this->capacity - $this->current_employee_count;
    }

    /**
     * Get occupancy rate percentage
     */
    public function getOccupancyRateAttribute()
    {
        if (!$this->capacity || $this->capacity == 0) {
            return null;
        }
        return round(($this->current_employee_count / $this->capacity) * 100, 1);
    }

    // =============================================
    // AGGREGATION METHODS
    // =============================================

    /**
     * Get scan statistics for a specific date range
     */
    public function getScanStats($startDate, $endDate)
    {
        $transactions = $this->mealTransactions()
            ->whereBetween('meal_date', [$startDate, $endDate])
            ->get();

        $regularMeals = $transactions->filter(fn($m) => !$m->isRewardMeal());
        $rewardMeals = $transactions->filter(fn($m) => $m->isRewardMeal());

        return [
            'total_scans' => $transactions->count(),
            'regular_scans' => $regularMeals->count(),
            'reward_scans' => $rewardMeals->count(),
            'total_revenue' => MealTransaction::calculateTotalRevenue($transactions),
            'regular_revenue' => $regularMeals->count() * 65.00,
            'reward_revenue' => $rewardMeals->count() * 200.00,
            'unique_employees' => $transactions->pluck('employee_id')->unique()->count(),
        ];
    }

    /**
     * Get daily breakdown for a date range
     */
    public function getDailyBreakdown($startDate, $endDate)
    {
        $transactions = $this->mealTransactions()
            ->whereBetween('meal_date', [$startDate, $endDate])
            ->get()
            ->groupBy(fn($t) => $t->meal_date->format('Y-m-d'));

        $breakdown = [];
        $period = \Carbon\CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $dayTransactions = $transactions->get($dateStr, collect());

            $breakdown[] = [
                'date' => $dateStr,
                'formatted_date' => $date->format('D, M j, Y'),
                'regular_scans' => $dayTransactions->filter(fn($m) => !$m->isRewardMeal())->count(),
                'reward_scans' => $dayTransactions->filter(fn($m) => $m->isRewardMeal())->count(),
                'total_scans' => $dayTransactions->count(),
                'regular_revenue' => $dayTransactions->filter(fn($m) => !$m->isRewardMeal())->count() * 65.00,
                'reward_revenue' => $dayTransactions->filter(fn($m) => $m->isRewardMeal())->count() * 200.00,
                'total_revenue' => MealTransaction::calculateTotalRevenue($dayTransactions),
            ];
        }

        return $breakdown;
    }

    /**
     * Get top employees by scan count for this unit
     */
    public function getTopEmployees($limit = 10, $startDate = null, $endDate = null)
    {
        $query = $this->mealTransactions()
            ->with('employee.department');

        if ($startDate && $endDate) {
            $query->whereBetween('meal_date', [$startDate, $endDate]);
        }

        return $query->select(
                'employee_id',
                DB::raw('COUNT(*) as scan_count'),
                DB::raw('SUM(CASE WHEN JSON_EXTRACT(scan_data, "$.is_reward") = true THEN 200.00 ELSE 65.00 END) as total_revenue')
            )
            ->groupBy('employee_id')
            ->orderByDesc('scan_count')
            ->limit($limit)
            ->get()
            ->map(function($item) {
                $employee = Employee::find($item->employee_id);
                return [
                    'id' => $item->employee_id,
                    'name' => $employee->formal_name ?? 'Unknown',
                    'employee_code' => $employee->employee_code ?? 'N/A',
                    'department' => $employee->department->name ?? 'N/A',
                    'scan_count' => $item->scan_count,
                    'total_revenue' => $item->total_revenue,
                ];
            });
    }

    // =============================================
    // SCOPES
    // =============================================

    /**
     * Scope active units
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope units with capacity available
     */
    public function scopeWithAvailableCapacity($query)
    {
        return $query->where(function($q) {
            $q->whereNull('capacity')
              ->orWhereRaw('current_employee_count < capacity');
        });
    }

    /**
     * Scope units at or near capacity
     */
    public function scopeNearCapacity($query, $threshold = 90)
    {
        return $query->whereNotNull('capacity')
            ->whereRaw('(current_employee_count / capacity) * 100 >= ?', [$threshold]);
    }

    // =============================================
    // HELPER METHODS
    // =============================================

    /**
     * Update current employee count
     */
    public function updateEmployeeCount()
    {
        $this->update([
            'current_employee_count' => $this->employees()->where('is_active', true)->count()
        ]);
    }

    /**
     * Check if unit has capacity for more employees
     */
    public function hasCapacity(): bool
    {
        if (!$this->capacity) {
            return true;
        }
        return $this->current_employee_count < $this->capacity;
    }

    /**
     * Get remaining capacity
     */
    public function getRemainingCapacity(): ?int
    {
        if (!$this->capacity) {
            return null;
        }
        return max(0, $this->capacity - $this->current_employee_count);
    }

    /**
     * Get summary for dashboard display
     */
    public function getDashboardSummary()
    {
        $todayTransactions = $this->mealTransactions()->whereDate('meal_date', today())->get();
        $monthTransactions = $this->mealTransactions()
            ->where('meal_date', '>=', now()->startOfMonth())
            ->get();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'location' => $this->location,
            'total_employees' => $this->employees()->count(),
            'active_employees' => $this->employees()->where('is_active', true)->count(),
            'capacity' => $this->capacity,
            'occupancy_rate' => $this->occupancy_rate,
            'today_scans' => $todayTransactions->count(),
            'today_revenue' => MealTransaction::calculateTotalRevenue($todayTransactions),
            'today_regular_scans' => $todayTransactions->filter(fn($m) => !$m->isRewardMeal())->count(),
            'today_reward_scans' => $todayTransactions->filter(fn($m) => $m->isRewardMeal())->count(),
            'month_scans' => $monthTransactions->count(),
            'month_revenue' => MealTransaction::calculateTotalRevenue($monthTransactions),
            'active_vendors' => $this->active_vendors_count,
            'is_full' => $this->is_full,
            'available_slots' => $this->available_slots,
        ];
    }
}
