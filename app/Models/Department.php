<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the sub-departments for the department.
     */
    public function subDepartments(): HasMany
    {
        return $this->hasMany(SubDepartment::class);
    }

    /**
     * Get the employees for the department.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Get active employees count
     */
    public function getActiveEmployeesCountAttribute(): int
    {
        return $this->employees()->count();
    }

    /**
     * Get active sub-departments count
     */
    public function getActiveSubDepartmentsCountAttribute(): int
    {
        return $this->subDepartments()->active()->count();
    }

    /**
     * Scope active departments
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
