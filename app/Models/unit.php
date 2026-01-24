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

    /**
     * Get the employees for the unit.
     */
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Scope active units
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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
}
