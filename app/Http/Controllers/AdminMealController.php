<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\MealTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;

class AdminMealController extends Controller
{
    /**
     * Show manual meal entry form for allowed dates
     */
    public function showManualEntryForm()
    {
        // Define allowed dates for manual entry
        $allowedDates = [
            '2026-02-02',
            '2026-02-03',
            '2026-02-16',
            '2026-02-24',
            '2026-02-25',
            '2026-02-26',
            '2026-02-27',
            '2026-02-28'
        ];

        // Get selected date from query parameter or default to first allowed date
        $selectedDate = request()->get('date', $allowedDates[0]);

        // Validate selected date is in allowed list
        if (!in_array($selectedDate, $allowedDates)) {
            $selectedDate = $allowedDates[0];
        }

        Log::info('AdminMealController: Loading manual entry form', [
            'admin_id' => Auth::id(),
            'admin_name' => Auth::user()->name,
            'selected_date' => $selectedDate,
            'allowed_dates' => $allowedDates
        ]);

        // Get all active employees
        $employees = Employee::where('is_active', true)
            ->with(['department', 'subDepartment', 'unit'])
            ->orderBy('first_name')
            ->get();

        // Get all vendors
        $vendors = User::where('role', 2)
            ->orderBy('name')
            ->get();

        // Get already scanned employees for selected date
        $scannedEmployees = MealTransaction::whereDate('meal_date', $selectedDate)
            ->pluck('employee_id')
            ->toArray();

        // Get total counts for the selected date
        $totalScans = MealTransaction::whereDate('meal_date', $selectedDate)->count();
        $totalManual = MealTransaction::whereDate('meal_date', $selectedDate)
            ->where('qr_code_scanned', 'LIKE', 'MANUAL_ENTRY_ADMIN_%')
            ->count();

        // Get counts for each allowed date for stats
        $dateStats = [];
        foreach ($allowedDates as $date) {
            $dateStats[$date] = [
                'total' => MealTransaction::whereDate('meal_date', $date)->count(),
                'manual' => MealTransaction::whereDate('meal_date', $date)
                    ->where('qr_code_scanned', 'LIKE', 'MANUAL_ENTRY_ADMIN_%')
                    ->count(),
                'formatted' => Carbon::parse($date)->format('l, F jS, Y')
            ];
        }

        Log::info('AdminMealController: Form data loaded', [
            'employees_count' => $employees->count(),
            'vendors_count' => $vendors->count(),
            'scanned_employees_count' => count($scannedEmployees),
            'total_scans' => $totalScans,
            'total_manual' => $totalManual,
            'selected_date' => $selectedDate
        ]);

        return view('reeds.admin.meals.manual-entry', compact(
            'employees',
            'vendors',
            'allowedDates',
            'selectedDate',
            'scannedEmployees',
            'totalScans',
            'totalManual',
            'dateStats'
        ));
    }

    /**
     * Process manual meal entry for selected date
     */
    public function processManualEntry(Request $request)
    {
        $allowedDates = [
            '2026-02-02',
            '2026-02-03',
            '2026-02-16',
            '2026-02-24',
            '2026-02-25',
            '2026-02-26',
            '2026-02-27',
            '2026-02-28'
        ];

        Log::info('AdminMealController: Processing manual entry request', [
            'admin_id' => Auth::id(),
            'admin_name' => Auth::user()->name,
            'request_data' => $request->all()
        ]);

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'vendor_id' => 'required|exists:users,id',
            'meal_date' => 'required|date|in:' . implode(',', $allowedDates),
            'meal_time' => 'required|date_format:H:i',
            'amount' => 'nullable|numeric|min:65|max:65',
            'note' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            Log::warning('AdminMealController: Validation failed', [
                'errors' => $validator->errors()->toArray()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $targetDate = $request->meal_date;
        $dateObj = Carbon::parse($targetDate);
        $formattedDate = $dateObj->format('l, F jS, Y');

        // Check if employee already has meal for this date
        $existingMeal = MealTransaction::where('employee_id', $request->employee_id)
            ->whereDate('meal_date', $targetDate)
            ->first();

        if ($existingMeal) {
            Log::warning('AdminMealController: Duplicate meal attempt', [
                'employee_id' => $request->employee_id,
                'target_date' => $targetDate,
                'existing_meal_id' => $existingMeal->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'This employee already has a meal recorded for ' . $formattedDate
            ], 409);
        }

        DB::beginTransaction();
        try {
            // Get employee details
            $employee = Employee::findOrFail($request->employee_id);
            $vendor = User::findOrFail($request->vendor_id);

            // Generate transaction code with manual prefix
            $transactionCode = 'MAN-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            // Set default amount to 65 KES if not provided
            $amount = $request->amount ?? 65.00;

            Log::info('AdminMealController: Creating meal transaction', [
                'employee_id' => $employee->id,
                'employee_name' => $employee->formal_name,
                'vendor_id' => $vendor->id,
                'vendor_name' => $vendor->name,
                'transaction_code' => $transactionCode,
                'amount' => $amount,
                'meal_date' => $targetDate,
                'meal_time' => $request->meal_time,
                'note' => $request->note
            ]);

            // Create meal transaction
            $transaction = MealTransaction::create([
                'vendor_id' => $request->vendor_id,
                'employee_id' => $request->employee_id,
                'transaction_code' => $transactionCode,
                'amount' => $amount,
                'meal_date' => $targetDate,
                'meal_time' => $request->meal_time,
                'qr_code_scanned' => 'MANUAL_ENTRY_ADMIN_' . Auth::id() . '_' . time(),
                'scan_data' => json_encode([
                    'entry_type' => 'manual_admin',
                    'admin_id' => Auth::id(),
                    'admin_name' => Auth::user()->name,
                    'entry_time' => now()->toDateTimeString(),
                    'entry_date' => $targetDate,
                    'formatted_date' => $formattedDate,
                    'reason' => 'Employee did not have meal card',
                    'manual_time' => $request->meal_time,
                    'note' => $request->note ?? null,
                    'amount_set' => $amount
                ])
            ]);

            DB::commit();

            Log::info('AdminMealController: Meal transaction created successfully', [
                'transaction_id' => $transaction->id,
                'transaction_code' => $transaction->transaction_code
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Meal for ' . $formattedDate . ' manually recorded successfully!',
                'transaction' => [
                    'code' => $transaction->transaction_code,
                    'employee_name' => $employee->formal_name,
                    'employee_code' => $employee->employee_code,
                    'meal_date' => $transaction->meal_date,
                    'formatted_date' => $formattedDate,
                    'meal_time' => $transaction->meal_time,
                    'amount' => $transaction->amount,
                    'vendor' => $vendor->name
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AdminMealController: Failed to create meal transaction', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to record manual meal entry: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk manual entry for multiple employees
     */
    public function bulkManualEntry(Request $request)
    {
        $allowedDates = [
            '2026-02-02',
            '2026-02-03',
            '2026-02-16',
            '2026-02-24',
            '2026-02-25',
            '2026-02-26',
            '2026-02-27',
            '2026-02-28'
        ];

        Log::info('AdminMealController: Processing bulk manual entry', [
            'admin_id' => Auth::id(),
            'employee_ids_count' => count($request->employee_ids ?? []),
            'vendor_id' => $request->vendor_id,
            'meal_date' => $request->meal_date,
            'meal_time' => $request->meal_time
        ]);

        $validator = Validator::make($request->all(), [
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
            'vendor_id' => 'required|exists:users,id',
            'meal_date' => 'required|date|in:' . implode(',', $allowedDates),
            'meal_time' => 'required|date_format:H:i'
        ]);

        if ($validator->fails()) {
            Log::warning('AdminMealController: Bulk entry validation failed', [
                'errors' => $validator->errors()->toArray()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $targetDate = $request->meal_date;
        $formattedDate = Carbon::parse($targetDate)->format('l, F jS, Y');
        $successCount = 0;
        $failedCount = 0;
        $errors = [];
        $vendor = User::find($request->vendor_id);

        DB::beginTransaction();
        try {
            foreach ($request->employee_ids as $employeeId) {
                try {
                    // Check if already has meal
                    $existing = MealTransaction::where('employee_id', $employeeId)
                        ->whereDate('meal_date', $targetDate)
                        ->exists();

                    if ($existing) {
                        $failedCount++;
                        $errors[] = "Employee ID {$employeeId} already has meal recorded for " . $formattedDate;
                        continue;
                    }

                    $employee = Employee::find($employeeId);

                    $transactionCode = 'MAN-BULK-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

                    MealTransaction::create([
                        'vendor_id' => $request->vendor_id,
                        'employee_id' => $employeeId,
                        'transaction_code' => $transactionCode,
                        'amount' => 65.00,
                        'meal_date' => $targetDate,
                        'meal_time' => $request->meal_time,
                        'qr_code_scanned' => 'MANUAL_BULK_ADMIN_' . Auth::id(),
                        'scan_data' => json_encode([
                            'entry_type' => 'bulk_manual_admin',
                            'admin_id' => Auth::id(),
                            'admin_name' => Auth::user()->name,
                            'batch_time' => now()->toDateTimeString(),
                            'meal_date' => $targetDate,
                            'formatted_date' => $formattedDate,
                            'meal_time' => $request->meal_time,
                            'vendor_name' => $vendor->name
                        ])
                    ]);

                    Log::info('AdminMealController: Bulk entry created', [
                        'employee_id' => $employeeId,
                        'employee_name' => $employee->formal_name,
                        'transaction_code' => $transactionCode
                    ]);

                    $successCount++;

                } catch (\Exception $e) {
                    $failedCount++;
                    $errorMsg = "Employee ID {$employeeId}: " . $e->getMessage();
                    $errors[] = $errorMsg;

                    Log::error('AdminMealController: Bulk entry failed for employee', [
                        'employee_id' => $employeeId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();

            Log::info('AdminMealController: Bulk entry completed', [
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'total_attempted' => count($request->employee_ids)
            ]);

            return response()->json([
                'success' => true,
                'message' => "Bulk entry for {$formattedDate} completed. Success: {$successCount}, Failed: {$failedCount}",
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('AdminMealController: Bulk entry transaction failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Bulk entry failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getRecentEntries(Request $request)
    {
        $targetDate = $request->get('date', '2026-02-02');

        $entries = MealTransaction::with(['employee', 'vendor'])
            ->whereDate('meal_date', $targetDate)
            ->where('qr_code_scanned', 'LIKE', 'MANUAL_ENTRY_ADMIN_%')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($transaction) {
                $scanData = json_decode($transaction->scan_data, true);
                return [
                    'employee_name' => $transaction->employee->formal_name ?? 'Unknown',
                    'employee_code' => $transaction->employee->employee_code ?? 'N/A',
                    'vendor' => $transaction->vendor->name ?? 'Unknown',
                    'amount' => $transaction->amount,
                    'meal_time' => $transaction->meal_time,
                    'meal_date' => Carbon::parse($transaction->meal_date)->format('M d, Y'),
                    'note' => $scanData['note'] ?? null
                ];
            });

        return response()->json([
            'success' => true,
            'entries' => $entries
        ]);
    }

    /**
     * Get unfed employees for a specific date
     */
    public function getUnfedEmployees(Request $request)
    {
        $targetDate = $request->get('date', '2026-02-02');

        $fedEmployeeIds = MealTransaction::whereDate('meal_date', $targetDate)
            ->pluck('employee_id')
            ->toArray();

        $unfedEmployees = Employee::where('is_active', true)
            ->whereNotIn('id', $fedEmployeeIds)
            ->with('department')
            ->get()
            ->map(function($employee) {
                return [
                    'id' => $employee->id,
                    'formal_name' => $employee->formal_name,
                    'employee_code' => $employee->employee_code,
                    'department' => $employee->department->name ?? 'N/A'
                ];
            });

        return response()->json([
            'success' => true,
            'count' => $unfedEmployees->count(),
            'employees' => $unfedEmployees
        ]);
    }

    /**
     * Get comprehensive report for selected date
     */
    public function getFeb2Report(Request $request)
    {
        $targetDate = $request->get('date', '2026-02-02');

        Log::info('AdminMealController: Generating date report', [
            'target_date' => $targetDate
        ]);

        // Total meals for the date
        $totalMeals = MealTransaction::whereDate('meal_date', $targetDate)
            ->count();

        // Total amount
        $totalAmount = MealTransaction::whereDate('meal_date', $targetDate)
            ->sum('amount');

        // Meals by vendor with details
        $mealsByVendor = MealTransaction::with('vendor')
            ->whereDate('meal_date', $targetDate)
            ->select('vendor_id', DB::raw('COUNT(*) as meal_count'), DB::raw('SUM(amount) as total_amount'))
            ->groupBy('vendor_id')
            ->get()
            ->map(function($item) {
                $item->vendor_name = $item->vendor->name ?? 'Unknown Vendor';
                return $item;
            });

        // Manual entries (admin entries)
        $manualEntries = MealTransaction::whereDate('meal_date', $targetDate)
            ->where('qr_code_scanned', 'LIKE', 'MANUAL_ENTRY_ADMIN_%')
            ->count();

        // Regular QR scans
        $regularScans = $totalMeals - $manualEntries;

        // All transactions for the date with employee details
        $transactions = MealTransaction::with(['employee.department', 'vendor'])
            ->whereDate('meal_date', $targetDate)
            ->orderBy('meal_time', 'desc')
            ->get()
            ->map(function($transaction) {
                return [
                    'id' => $transaction->id,
                    'transaction_code' => $transaction->transaction_code,
                    'type' => strpos($transaction->qr_code_scanned, 'MANUAL_ENTRY_ADMIN') !== false ? 'Manual' : 'QR Scan',
                    'employee_name' => $transaction->employee->formal_name ?? 'Unknown',
                    'employee_code' => $transaction->employee->employee_code ?? 'N/A',
                    'department' => $transaction->employee->department->name ?? 'N/A',
                    'vendor' => $transaction->vendor->name ?? 'Unknown',
                    'meal_time' => $transaction->meal_time,
                    'amount' => $transaction->amount,
                    'created_at' => $transaction->created_at->format('Y-m-d H:i:s')
                ];
            });

        // Employees who haven't been fed on that day
        $allEmployees = Employee::where('is_active', true)->count();
        $fedEmployees = MealTransaction::whereDate('meal_date', $targetDate)
            ->distinct('employee_id')
            ->count('employee_id');
        $notFedCount = $allEmployees - $fedEmployees;

        Log::info('AdminMealController: Report generated', [
            'total_meals' => $totalMeals,
            'total_amount' => $totalAmount,
            'manual_entries' => $manualEntries
        ]);

        return response()->json([
            'success' => true,
            'report_date' => $targetDate,
            'formatted_date' => Carbon::parse($targetDate)->format('l, F jS, Y'),
            'summary' => [
                'total_meals' => $totalMeals,
                'total_amount' => $totalAmount,
                'manual_entries' => $manualEntries,
                'regular_scans' => $regularScans,
                'employees_fed' => $fedEmployees,
                'employees_not_fed' => $notFedCount
            ],
            'vendor_breakdown' => $mealsByVendor,
            'transactions' => $transactions
        ]);
    }
}
