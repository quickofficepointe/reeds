<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    // Relationships

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
   // In App\Models\Unit.php
/**
 * Get the users (including vendors) for the unit.
 */
public function unitUsers()
{
    return $this->hasMany(User::class);
}
private function getUnitStats()
{
    return Unit::active()
        ->withCount([
            'employees as total_employees',
            'employees as active_employees' => function($query) {
                $query->where('is_active', true);
            }
        ])
        ->withSum([
            'mealTransactions as month_revenue' => function($query) {
                $query->where('meal_date', '>=', now()->startOfMonth());
            }
        ], 'amount')
        ->withCount([
            'unitVendors as active_vendors' => function($query) {
                $query->whereHas('profile', function($q) {
                    $q->where('is_verified', true);
                });
            }
        ])
        ->get()
        ->map(function($unit) {
            return [
                'id' => $unit->id,
                'name' => $unit->name,
                'total_employees' => $unit->total_employees,
                'active_employees' => $unit->active_employees,
                'total_scans' => $unit->total_scans,
                'month_scans' => $unit->month_scans,
                'today_scans' => $unit->today_scans,
                'month_revenue' => $unit->month_revenue ?? 0,
                'active_vendors' => $unit->active_vendors,
            ];
        });
}
/**
 * Get the vendors for the unit.
 */
public function unitVendors()
{
    return $this->hasMany(User::class)->where('role', User::ROLE_VENDOR);
}

    /**
     * Get the meal transactions through employees.
     */
    public function mealTransactions()
    {
        return $this->hasManyThrough(
            MealTransaction::class,
            Employee::class,
            'unit_id', // Foreign key on employees table
            'employee_id', // Foreign key on meal_transactions table
            'id', // Local key on units table
            'id' // Local key on employees table
        );
    }

    // Scopes

    /**
     * Scope active units
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Attributes

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
     * Get active vendors count
     */
    public function getActiveVendorsCountAttribute()
    {
        return $this->vendors()
            ->whereHas('profile', function($query) {
                $query->where('is_verified', true);
            })
            ->count();
    }

    /**
     * Get total scans count
     */
    public function getTotalScansAttribute()
    {
        return $this->mealTransactions()->count();
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
     * Get today's scans count
     */
    public function getTodayScansAttribute()
    {
        return $this->mealTransactions()
            ->whereDate('meal_date', today())
            ->count();
    }

    /**
     * Get this month's revenue
     */
    public function getMonthRevenueAttribute()
    {
        return $this->mealTransactions()
            ->where('meal_date', '>=', now()->startOfMonth())
            ->sum('amount');
    }
}
