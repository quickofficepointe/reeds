<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_code',
        'payroll_no',
        'department_id',
        'sub_department_id',
        'employment_type',
        'title',
        'first_name',
        'middle_name',
        'last_name',
        'date_of_joining',
        'on_probation',
        'on_contract',
        'icard_number',
        'gender',
        'birth_date',
        'marital_status',
        'anniversary_date',
        'religion',
        'mother_tongue',
        'nationality',
        'ethnicity',
        'tribe',
        'designation',
        'category',
        'qr_code',
        'is_active',
    ];

    protected $casts = [
        'date_of_joining' => 'date',
        'birth_date' => 'date',
        'anniversary_date' => 'date',
        'on_probation' => 'boolean',
        'on_contract' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the department that owns the employee.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the sub-department that owns the employee.
     */
    public function subDepartment(): BelongsTo
    {
        return $this->belongsTo(SubDepartment::class);
    }

    /**
     * Get the feeding records for the employee.
     */
    public function feedingRecords(): HasMany
    {
        return $this->hasMany(FeedingRecord::class);
    }

    /**
     * Get the meal transactions for the employee.
     */
    public function mealTransactions(): HasMany
    {
        return $this->hasMany(MealTransaction::class);
    }

    /**
     * Get full name attribute
     */
    public function getFullNameAttribute(): string
    {
        $names = [$this->first_name];

        if ($this->middle_name) {
            $names[] = $this->middle_name;
        }

        $names[] = $this->last_name;

        return implode(' ', $names);
    }

    /**
     * Get formal name with title
     */
    public function getFormalNameAttribute(): string
    {
        $name = $this->full_name;

        if ($this->title) {
            $name = "{$this->title} {$name}";
        }

        return $name;
    }

    /**
     * Generate QR code for employee
     */
  // In Employee.php model - update generateQrCode method
// In Employee.php - make sure this method exists
public function generateQrCode(): array
{
    $now = now()->format('F j, Y \\a\\t g:i A');

    // Create the display text (what shows in QR code)
    $displayText = "REEDS AFRICA CONSULT - OFFICIAL MEAL CARD\n" .
                  "===============================\n" .
                  "Employee No: {$this->employee_code}\n" .
                  "Name: {$this->formal_name}\n" .
                  "Department: " . ($this->department->name ?? 'N/A') . "\n" .
                  ($this->subDepartment ? "Sub-Department: {$this->subDepartment->name}\n" : "") .
                  "Designation: " . ($this->designation ?? 'N/A') . "\n" .
                  "Employment: " . ($this->employment_type ?? 'N/A') . "\n" .
                  "Status: " . ($this->is_active ? 'ACTIVE' : 'INACTIVE') . "\n" .
                  "Generated: {$now}\n" .
                  "===============================\n" .
                  "Powered by: BizTrak Solutions";

    // Create encoded version for database lookup
    $encodedQr = base64_encode($this->employee_code . '|' . hash_hmac('sha256', $this->employee_code, env('QR_SECRET', 'default-secret')));

    // Store both versions
    $this->update([
        'qr_code' => $encodedQr,
        'qr_code_display' => $displayText
    ]);

    return [
        'display_text' => $displayText,
        'encoded' => $encodedQr
    ];
}

    /**
     * Scope active employees
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by department
     */
    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope by sub-department
     */
    public function scopeBySubDepartment($query, $subDepartmentId)
    {
        return $query->where('sub_department_id', $subDepartmentId);
    }

    /**
     * Check if employee was fed today
     */
    public function fedToday(): bool
    {
        return $this->feedingRecords()
            ->whereDate('created_at', today())
            ->exists();
    }

    /**
     * Check if employee has eaten today (meal transaction)
     */
    public function hasEatenToday(): bool
    {
        return $this->mealTransactions()
            ->whereDate('meal_date', today())
            ->exists();
    }

    /**
     * Get today's meal transaction
     */
    public function getTodayMealTransaction()
    {
        return $this->mealTransactions()
            ->whereDate('meal_date', today())
            ->first();
    }

    /**
     * Get meal transaction history
     */
    public function getMealHistory($days = 30)
    {
        return $this->mealTransactions()
            ->with('vendor')
            ->where('meal_date', '>=', now()->subDays($days))
            ->orderBy('meal_date', 'desc')
            ->orderBy('meal_time', 'desc')
            ->get();
    }

    /**
     * Get total meals consumed this month
     */
    public function getMonthlyMealCount(): int
    {
        return $this->mealTransactions()
            ->whereYear('meal_date', now()->year)
            ->whereMonth('meal_date', now()->month)
            ->count();
    }

    /**
     * Get total amount consumed this month
     */
    public function getMonthlyMealAmount(): float
    {
        return $this->mealTransactions()
            ->whereYear('meal_date', now()->year)
            ->whereMonth('meal_date', now()->month)
            ->sum('amount');
    }

    /**
     * Check if employee can be fed now (business rules)
     */
    public function canBeFedNow(): array
    {
        // Check if employee is active
        if (!$this->is_active) {
            return [
                'can_be_fed' => false,
                'reason' => 'Employee is not active'
            ];
        }

        // Check if already ate today
        if ($this->hasEatenToday()) {
            $todayMeal = $this->getTodayMealTransaction();
            return [
                'can_be_fed' => false,
                'reason' => "Already had a meal today. Scanned by {$todayMeal->vendor->name} at {$todayMeal->meal_time}"
            ];
        }

        // Check if it's within feeding hours (optional business rule)
        $currentHour = now()->hour;
        if ($currentHour < 6 || $currentHour > 22) {
            return [
                'can_be_fed' => false,
                'reason' => 'Outside of feeding hours (6:00 AM - 10:00 PM)'
            ];
        }

        return [
            'can_be_fed' => true,
            'reason' => 'OK to feed'
        ];
    }

    /**
     * Record a meal transaction
     */
    public function recordMeal($vendorId, $qrCodeScanned, $amount = 70.00): MealTransaction
    {
        return MealTransaction::create([
            'vendor_id' => $vendorId,
            'employee_id' => $this->id,
            'amount' => $amount,
            'meal_date' => today(),
            'qr_code_scanned' => $qrCodeScanned,
            'scan_data' => [
                'scanned_at' => now()->toDateTimeString(),
                'employee_name' => $this->formal_name,
                'employee_code' => $this->employee_code,
                'department' => $this->department->name ?? 'N/A',
                'designation' => $this->designation,
            ]
        ]);
    }

    /**
     * Get feeding statistics
     */
    public function getFeedingStats(): array
    {
        $totalMeals = $this->mealTransactions()->count();
        $thisMonthMeals = $this->getMonthlyMealCount();
        $thisWeekMeals = $this->mealTransactions()
            ->whereBetween('meal_date', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        return [
            'total_meals' => $totalMeals,
            'this_month' => $thisMonthMeals,
            'this_week' => $thisWeekMeals,
            'total_amount' => $this->mealTransactions()->sum('amount'),
            'last_meal' => $this->mealTransactions()->latest()->first()?->meal_date,
        ];
    }

    /**
     * Get vendors who have fed this employee
     */
    public function getFeedingVendors()
    {
        return $this->mealTransactions()
            ->with('vendor')
            ->select('vendor_id')
            ->selectRaw('COUNT(*) as meal_count')
            ->selectRaw('SUM(amount) as total_amount')
            ->groupBy('vendor_id')
            ->orderBy('meal_count', 'desc')
            ->get()
            ->map(function ($transaction) {
                return [
                    'vendor' => $transaction->vendor,
                    'meal_count' => $transaction->meal_count,
                    'total_amount' => $transaction->total_amount,
                    'last_fed' => $this->mealTransactions()
                        ->where('vendor_id', $transaction->vendor_id)
                        ->latest()
                        ->first()?->meal_date
                ];
            });
    }

    /**
     * Validate QR code
     */
    public function validateQrCode($scannedQrCode): bool
    {
        if ($this->qr_code !== $scannedQrCode) {
            return false;
        }

        // Additional validation: Check if QR code hasn't expired (optional)
        // You could add expiration logic here if needed

        return true;
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate QR code when employee is created
        static::created(function ($employee) {
            if (empty($employee->qr_code)) {
                $employee->generateQrCode();
            }
        });

        // Remove the auto-generation since we'll use existing employee codes from Excel
        // static::creating(function ($employee) {
        //     if (empty($employee->employee_code)) {
        //         $employee->employee_code = static::generateEmployeeCode();
        //     }
        // });
    }

    /**
     * Generate unique employee code
     */
    public static function generateEmployeeCode(): string
    {
        do {
            $code = 'EMP' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (static::where('employee_code', $code)->exists());

        return $code;
    }

/**
 * Generate QR code data for employee
 */
public function generateQrCodeData(): array
{
    $now = now()->format('F j, Y \\a\\t g:i A');

    return [
        'employee_id' => $this->id,
        'employee_code' => $this->employee_code,
        'formal_name' => $this->formal_name,
        'department' => $this->department->name ?? 'N/A',
        'sub_department' => $this->subDepartment->name ?? 'N/A',
        'designation' => $this->designation ?? 'N/A',
        'employment_type' => $this->employment_type ?? 'N/A',
        'qr_data' => "REEDS AFRICA CONSULT - OFFICIAL MEAL CARD\n" .
                    "===============================\n" .
                    "Employee No: {$this->employee_code}\n" .
                    "Name: {$this->formal_name}\n" .
                    "Department: " . ($this->department->name ?? 'N/A') . "\n" .
                    ($this->subDepartment ? "Sub-Department: {$this->subDepartment->name}\n" : "") .
                    "Designation: " . ($this->designation ?? 'N/A') . "\n" .
                    "Employment: " . ($this->employment_type ?? 'N/A') . "\n" .
                    "Status: " . ($this->is_active ? 'ACTIVE' : 'INACTIVE') . "\n" .
                    "Generated: {$now}\n" .
                    "===============================\n" .
                    "Powered by: BizTrak Solutions"
    ];
}

}
