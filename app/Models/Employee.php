<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_code',
        'payroll_no',
        'department_id',
        'sub_department_id',
        'unit_id',
        'employment_type',
        'title',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'phone',
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
        'qr_code_display',
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

    protected $appends = ['full_name', 'formal_name'];

    // =============================================
    // RELATIONSHIPS
    // =============================================

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function subDepartment(): BelongsTo
    {
        return $this->belongsTo(SubDepartment::class);
    }

    public function documents(): HasOne
    {
        return $this->hasOne(EmployeeDocument::class);
    }

    public function feedingRecords(): HasMany
    {
        return $this->hasMany(FeedingRecord::class);
    }

    public function mealTransactions(): HasMany
    {
        return $this->hasMany(MealTransaction::class);
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(Reward::class);
    }

    public function documentInvitation(): HasOne
    {
        return $this->hasOne(DocumentInvitation::class);
    }

    // =============================================
    // ATTRIBUTES
    // =============================================

    public function getFullNameAttribute(): string
    {
        $names = [$this->first_name];
        if ($this->middle_name) {
            $names[] = $this->middle_name;
        }
        $names[] = $this->last_name;
        return implode(' ', $names);
    }

    public function getFormalNameAttribute(): string
    {
        $name = $this->full_name;
        if ($this->title) {
            $name = "{$this->title} {$name}";
        }
        return $name;
    }

    public function getHasCompleteDocumentsAttribute(): bool
    {
        if (!$this->documents) {
            return false;
        }
        return $this->documents->hasAllRequiredDocuments();
    }

    public function getDocumentsVerifiedAttribute(): bool
    {
        return $this->documents && $this->documents->is_verified;
    }

    public function getTodayRewardAttribute()
    {
        return $this->rewards()
            ->whereDate('reward_date', today())
            ->where('status', 'pending')
            ->first();
    }

    // =============================================
    // QR CODE METHODS
    // =============================================

    /**
     * Generate QR code for employee - MINIMAL VERSION
     */
    public function generateQrCode(): array
    {
        $encodedData = $this->employee_code;
        $displayText = "{$this->employee_code} - {$this->formal_name}";

        $this->update([
            'qr_code' => $encodedData,
            'qr_code_display' => $displayText
        ]);

        return [
            'display_text' => $displayText,
            'encoded' => $encodedData
        ];
    }

    /**
     * Generate QR code data for employee - MINIMAL VERSION
     */
    public function generateQrCodeData(): array
    {
        return [
            'employee_id' => $this->id,
            'employee_code' => $this->employee_code,
            'formal_name' => $this->formal_name,
            'department' => $this->department->name ?? 'N/A',
            'sub_department' => $this->subDepartment->name ?? 'N/A',
            'unit' => $this->unit->name ?? 'N/A',
            'designation' => $this->designation ?? 'N/A',
            'employment_type' => $this->employment_type ?? 'N/A',
            'qr_data' => $this->employee_code,
            'display_text' => $this->qr_code_display ?? "{$this->employee_code} - {$this->formal_name}"
        ];
    }

    public function validateQrCode($scannedQrCode): bool
    {
        return $this->employee_code === $scannedQrCode;
    }

    // =============================================
    // MEAL TRANSACTION METHODS
    // =============================================

    /**
     * Record a meal transaction - FIXED with reward support
     * @param int $vendorId
     * @param string $qrCodeScanned
     * @param float $amount - Default 65 for regular, 200 for reward
     * @param bool $isReward - Whether this is a reward meal
     * @param int|null $rewardId - Associated reward ID
     */
    public function recordMeal($vendorId, $qrCodeScanned, $amount = 65.00, $isReward = false, $rewardId = null): MealTransaction
    {
        return MealTransaction::create([
            'vendor_id' => $vendorId,
            'employee_id' => $this->id,
            'unit_id' => $this->unit_id,
            'amount' => $amount,
            'meal_date' => today(),
            'qr_code_scanned' => $qrCodeScanned,
            'scan_data' => [
                'scanned_at' => now()->toDateTimeString(),
                'employee_name' => $this->formal_name,
                'employee_code' => $this->employee_code,
                'department' => $this->department->name ?? 'N/A',
                'unit' => $this->unit->name ?? 'N/A',
                'unit_id' => $this->unit_id,
                'department_id' => $this->department_id,
                'designation' => $this->designation,
                'is_reward' => $isReward,
                'reward_id' => $rewardId,
                'amount' => $amount
            ]
        ]);
    }

    public function fedToday(): bool
    {
        return $this->feedingRecords()
            ->whereDate('created_at', today())
            ->exists();
    }

    public function hasEatenToday(): bool
    {
        return $this->mealTransactions()
            ->whereDate('meal_date', today())
            ->exists();
    }

    public function getTodayMealTransaction()
    {
        return $this->mealTransactions()
            ->whereDate('meal_date', today())
            ->first();
    }

    public function getMealHistory($days = 30)
    {
        return $this->mealTransactions()
            ->with('vendor')
            ->where('meal_date', '>=', now()->subDays($days))
            ->orderBy('meal_date', 'desc')
            ->orderBy('meal_time', 'desc')
            ->get();
    }

    public function getMonthlyMealCount(): int
    {
        return $this->mealTransactions()
            ->whereYear('meal_date', now()->year)
            ->whereMonth('meal_date', now()->month)
            ->count();
    }

    public function getMonthlyMealAmount(): float
    {
        $transactions = $this->mealTransactions()
            ->whereYear('meal_date', now()->year)
            ->whereMonth('meal_date', now()->month)
            ->get();

        return $transactions->sum(function($meal) {
            return $meal->getEffectiveAmount();
        });
    }

    // =============================================
    // FEEDING RULES
    // =============================================

    /**
     * Check if employee can be fed now (business rules)
     */
    public function canBeFedNow(): array
    {
        if (!$this->is_active) {
            return [
                'can_be_fed' => false,
                'reason' => 'Employee is not active'
            ];
        }

        if ($this->hasEatenToday()) {
            $todayMeal = $this->getTodayMealTransaction();
            return [
                'can_be_fed' => false,
                'reason' => "Already had a meal today. Scanned by {$todayMeal->vendor->name} at {$todayMeal->meal_time}"
            ];
        }

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

    // =============================================
    // REWARD METHODS
    // =============================================

    public function hasRewardToday()
    {
        return $this->rewards()
            ->whereDate('reward_date', today())
            ->where('status', 'pending')
            ->exists();
    }

    // =============================================
    // STATISTICS METHODS
    // =============================================

    public function getFeedingStats(): array
    {
        $transactions = $this->mealTransactions()->get();

        $totalMeals = $transactions->count();
        $thisMonthMeals = $this->getMonthlyMealCount();
        $thisWeekMeals = $transactions
            ->whereBetween('meal_date', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        return [
            'total_meals' => $totalMeals,
            'this_month' => $thisMonthMeals,
            'this_week' => $thisWeekMeals,
            'total_amount' => MealTransaction::calculateTotalRevenue($transactions),
            'regular_meals' => $transactions->filter(fn($m) => !$m->isRewardMeal())->count(),
            'reward_meals' => $transactions->filter(fn($m) => $m->isRewardMeal())->count(),
            'last_meal' => $transactions->sortByDesc('meal_date')->first()?->meal_date,
            'has_documents' => $this->documents ? true : false,
            'documents_verified' => $this->documents_verified,
            'documents_complete' => $this->has_complete_documents,
        ];
    }

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

    // =============================================
    // EMPLOYEE CODE GENERATION
    // =============================================

    public static function generateEmployeeCode(): string
    {
        do {
            $code = 'EMP' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (static::where('employee_code', $code)->exists());

        return $code;
    }

    // =============================================
    // BOOT METHOD
    // =============================================

    protected static function boot()
    {
        parent::boot();

        static::created(function ($employee) {
            if (empty($employee->qr_code)) {
                $employee->generateQrCode();
            }
        });

        static::creating(function ($employee) {
            if (empty($employee->employee_code)) {
                $employee->employee_code = static::generateEmployeeCode();
            }
        });

        static::saved(function ($employee) {
            static::updateUnitEmployeeCount($employee);
        });

        static::deleted(function ($employee) {
            if ($employee->unit_id) {
                Unit::where('id', $employee->unit_id)->decrement('current_employee_count');
            }
        });
    }

    private static function updateUnitEmployeeCount($employee)
    {
        static::withoutEvents(function () use ($employee) {
            $originalUnitId = $employee->getOriginal('unit_id');
            $newUnitId = $employee->unit_id;

            if ($originalUnitId !== $newUnitId) {
                if ($originalUnitId) {
                    Unit::where('id', $originalUnitId)->decrement('current_employee_count');
                }
                if ($newUnitId) {
                    Unit::where('id', $newUnitId)->increment('current_employee_count');
                }
            }
        });
    }

    // =============================================
    // SCOPES
    // =============================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByUnit($query, $unitId)
    {
        return $query->where('unit_id', $unitId);
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeBySubDepartment($query, $subDepartmentId)
    {
        return $query->where('sub_department_id', $subDepartmentId);
    }

    public function scopeWithCompleteDocuments($query)
    {
        return $query->whereHas('documents', function ($q) {
            $q->whereNotNull('national_id_photo')
              ->whereNotNull('passport_size_photo')
              ->whereNotNull('nssf_card_photo')
              ->whereNotNull('sha_card_photo')
              ->whereNotNull('kra_certificate_photo');
        });
    }

    public function scopeWithVerifiedDocuments($query)
    {
        return $query->whereHas('documents', function ($q) {
            $q->where('is_verified', true);
        });
    }

    public function scopeSearch($query, $searchTerm)
    {
        return $query->where(function($q) use ($searchTerm) {
            $q->where('employee_code', 'LIKE', "%{$searchTerm}%")
              ->orWhere('first_name', 'LIKE', "%{$searchTerm}%")
              ->orWhere('last_name', 'LIKE', "%{$searchTerm}%")
              ->orWhere('payroll_no', 'LIKE', "%{$searchTerm}%")
              ->orWhere('icard_number', 'LIKE', "%{$searchTerm}%")
              ->orWhere('designation', 'LIKE', "%{$searchTerm}%")
              ->orWhere('email', 'LIKE', "%{$searchTerm}%")
              ->orWhere('phone', 'LIKE', "%{$searchTerm}%")
              ->orWhereHas('department', function($departmentQuery) use ($searchTerm) {
                  $departmentQuery->where('name', 'LIKE', "%{$searchTerm}%");
              })
              ->orWhereHas('subDepartment', function($subDeptQuery) use ($searchTerm) {
                  $subDeptQuery->where('name', 'LIKE', "%{$searchTerm}%");
              })
              ->orWhereHas('unit', function($unitQuery) use ($searchTerm) {
                  $unitQuery->where('name', 'LIKE', "%{$searchTerm}%");
              });
        });
    }

    // =============================================
    // DETAILS METHOD
    // =============================================

    public function getEmployeeDetails(): array
    {
        $transactions = $this->mealTransactions()->get();

        return [
            'id' => $this->id,
            'employee_code' => $this->employee_code,
            'full_name' => $this->full_name,
            'formal_name' => $this->formal_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'designation' => $this->designation,
            'date_of_joining' => $this->date_of_joining,
            'department' => $this->department ? $this->department->name : null,
            'sub_department' => $this->subDepartment ? $this->subDepartment->name : null,
            'unit' => $this->unit ? $this->unit->name : null,
            'is_active' => $this->is_active,
            'qr_code_display' => $this->qr_code_display,
            'documents' => $this->documents ? [
                'has_documents' => true,
                'is_verified' => $this->documents->is_verified,
                'next_of_kin' => [
                    'name' => $this->documents->next_of_kin_name,
                    'relationship' => $this->documents->next_of_kin_relationship,
                    'phone' => $this->documents->next_of_kin_phone,
                ],
                'uploaded_documents' => $this->documents->uploaded_count,
                'required_documents' => count($this->documents->getDocumentFields()),
            ] : [
                'has_documents' => false,
                'is_verified' => false,
            ],
            'meal_stats' => $this->getFeedingStats(),
        ];
    }
}
