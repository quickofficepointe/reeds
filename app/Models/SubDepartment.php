<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubDepartment extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the department that owns the sub-department.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the employees for the sub-department.
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
     * Scope active sub-departments
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
