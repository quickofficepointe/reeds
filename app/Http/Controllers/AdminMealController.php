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
     * Show manual meal entry form for any date
     */
    public function showManualEntryForm()
    {
        try {
            // Get selected date from query parameter or default to today
            $selectedDate = request()->get('date', Carbon::today()->toDateString());

            Log::info('AdminMealController: Loading manual entry form', [
                'admin_id' => Auth::id(),
                'admin_name' => Auth::user()->name,
                'selected_date' => $selectedDate
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
                ->where('qr_code_scanned', 'LIKE', 'MANUAL_ENTRY_%')
                ->count();

            // Get last 30 days for quick navigation
            $recentDates = [];
            for ($i = 0; $i < 30; $i++) {
                $date = Carbon::today()->subDays($i);
                $recentDates[] = [
                    'date' => $date->toDateString(),
                    'formatted' => $date->format('l, M j, Y'),
                    'total' => MealTransaction::whereDate('meal_date', $date->toDateString())->count()
                ];
            }

            return view('reeds.admin.meals.manual-entry', compact(
                'employees',
                'vendors',
                'selectedDate',
                'scannedEmployees',
                'totalScans',
                'totalManual',
                'recentDates'
            ));

        } catch (\Exception $e) {
            Log::error('showManualEntryForm error: ' . $e->getMessage());
            return view('reeds.admin.meals.manual-entry')->withErrors('Failed to load data: ' . $e->getMessage());
        }
    }

    /**
     * Process manual meal entry for selected date
     */
    public function processManualEntry(Request $request)
    {
        try {
            Log::info('AdminMealController: Processing manual entry request', [
                'admin_id' => Auth::id(),
                'admin_name' => Auth::user()->name,
                'request_data' => $request->all()
            ]);

            $validator = Validator::make($request->all(), [
                'employee_id' => 'required|exists:employees,id',
                'vendor_id' => 'required|exists:users,id',
                'meal_date' => 'required|date',
                'meal_time' => 'required|date_format:H:i',
                'amount' => 'nullable|numeric|min:0|max:200',
                'note' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
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
                return response()->json([
                    'success' => false,
                    'message' => 'This employee already has a meal recorded for ' . $formattedDate
                ], 409);
            }

            DB::beginTransaction();

            $employee = Employee::findOrFail($request->employee_id);
            $vendor = User::findOrFail($request->vendor_id);
            $transactionCode = 'MAN-' . date('YmdHis') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $amount = $request->amount ?? 65.00;

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
                    'reason' => $request->note ?? 'Manual entry by admin',
                    'manual_time' => $request->meal_time,
                    'note' => $request->note ?? null,
                    'amount_set' => $amount
                ])
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Meal for ' . $formattedDate . ' manually recorded successfully!',
                'transaction' => [
                    'id' => $transaction->id,
                    'code' => $transaction->transaction_code,
                    'employee_name' => $employee->formal_name,
                    'employee_code' => $employee->employee_code,
                    'meal_date' => $transaction->meal_date,
                    'formatted_date' => $formattedDate,
                    'meal_time' => $transaction->meal_time,
                    'amount' => $transaction->amount,
                    'vendor' => $vendor->name,
                    'note' => $request->note
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('processManualEntry error: ' . $e->getMessage());
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
        try {
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
                'meal_date' => 'required|date',
                'meal_time' => 'required|date_format:H:i'
            ]);

            if ($validator->fails()) {
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

            foreach ($request->employee_ids as $employeeId) {
                try {
                    // Check if already has meal
                    $existing = MealTransaction::where('employee_id', $employeeId)
                        ->whereDate('meal_date', $targetDate)
                        ->exists();

                    if ($existing) {
                        $failedCount++;
                        $employee = Employee::find($employeeId);
                        $errors[] = ($employee ? $employee->formal_name : 'Unknown') . " already has meal recorded for " . $formattedDate;
                        continue;
                    }

                    $employee = Employee::find($employeeId);
                    $transactionCode = 'MAN-BULK-' . date('YmdHis') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

                    MealTransaction::create([
                        'vendor_id' => $request->vendor_id,
                        'employee_id' => $employeeId,
                        'transaction_code' => $transactionCode,
                        'amount' => 65.00,
                        'meal_date' => $targetDate,
                        'meal_time' => $request->meal_time,
                        'qr_code_scanned' => 'MANUAL_BULK_ADMIN_' . Auth::id() . '_' . time(),
                        'scan_data' => json_encode([
                            'entry_type' => 'bulk_manual_admin',
                            'admin_id' => Auth::id(),
                            'admin_name' => Auth::user()->name,
                            'batch_time' => now()->toDateTimeString(),
                            'meal_date' => $targetDate,
                            'formatted_date' => $formattedDate,
                            'meal_time' => $request->meal_time,
                            'vendor_name' => $vendor ? $vendor->name : 'Unknown'
                        ])
                    ]);

                    Log::info('AdminMealController: Bulk entry created', [
                        'employee_id' => $employeeId,
                        'employee_name' => $employee ? $employee->formal_name : 'Unknown',
                        'transaction_code' => $transactionCode
                    ]);

                    $successCount++;

                } catch (\Exception $e) {
                    $failedCount++;
                    $employee = Employee::find($employeeId);
                    $errorMsg = ($employee ? $employee->formal_name : 'Unknown') . ": " . $e->getMessage();
                    $errors[] = $errorMsg;

                    Log::error('AdminMealController: Bulk entry failed for employee', [
                        'employee_id' => $employeeId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Bulk entry for {$formattedDate} completed. Success: {$successCount}, Failed: {$failedCount}",
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('bulkManualEntry error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Bulk entry failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent entries for a date
     */
    public function getRecentEntries(Request $request)
    {
        try {
            $targetDate = $request->get('date', Carbon::today()->toDateString());

            Log::info('getRecentEntries called', ['date' => $targetDate]);

            $entries = MealTransaction::whereDate('meal_date', $targetDate)
                ->orderBy('created_at', 'desc')
                ->get();

            $formattedEntries = [];

            foreach ($entries as $transaction) {
                try {
                    $employeeName = 'Unknown';
                    $employeeCode = 'N/A';
                    if ($transaction->employee) {
                        $employeeName = $transaction->employee->formal_name ?? 'Unknown';
                        $employeeCode = $transaction->employee->employee_code ?? 'N/A';
                    }

                    $vendorName = 'Unknown';
                    if ($transaction->vendor) {
                        $vendorName = $transaction->vendor->name ?? 'Unknown';
                    }

                    $scanData = [];
                    if ($transaction->scan_data) {
                        $scanData = is_string($transaction->scan_data) ? json_decode($transaction->scan_data, true) : $transaction->scan_data;
                    }

                    $isManual = strpos($transaction->qr_code_scanned ?? '', 'MANUAL_ENTRY') !== false;

                    $formattedEntries[] = [
                        'id' => $transaction->id,
                        'employee_name' => $employeeName,
                        'employee_code' => $employeeCode,
                        'vendor' => $vendorName,
                        'amount' => $transaction->amount ?? 65,
                        'meal_time' => $transaction->meal_time ?? '12:00',
                        'meal_date' => $transaction->meal_date ? Carbon::parse($transaction->meal_date)->format('M d, Y') : 'Unknown',
                        'note' => $scanData['note'] ?? ($scanData['reason'] ?? null),
                        'is_manual' => $isManual,
                        'is_reward' => isset($scanData['is_reward']) && $scanData['is_reward'] === true,
                        'transaction_code' => $transaction->transaction_code ?? 'N/A',
                        'entry_type' => $isManual ? 'Manual' : 'QR Scan'
                    ];
                } catch (\Exception $e) {
                    Log::warning('Error formatting entry: ' . $e->getMessage(), ['transaction_id' => $transaction->id]);
                    continue;
                }
            }

            return response()->json([
                'success' => true,
                'entries' => $formattedEntries,
                'total' => count($formattedEntries)
            ]);

        } catch (\Exception $e) {
            Log::error('getRecentEntries error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading entries: ' . $e->getMessage(),
                'entries' => [],
                'total' => 0
            ], 500);
        }
    }

    /**
     * Get entries for date (for delete modal)
     */
    public function getEntriesForDate(Request $request)
    {
        try {
            $targetDate = $request->get('date', Carbon::today()->toDateString());

            $entries = MealTransaction::whereDate('meal_date', $targetDate)
                ->orderBy('meal_time', 'desc')
                ->get();

            $formattedEntries = [];

            foreach ($entries as $transaction) {
                try {
                    $isManual = strpos($transaction->qr_code_scanned ?? '', 'MANUAL_ENTRY') !== false;
                    $scanData = [];
                    if ($transaction->scan_data) {
                        $scanData = is_string($transaction->scan_data) ? json_decode($transaction->scan_data, true) : $transaction->scan_data;
                    }

                    $formattedEntries[] = [
                        'id' => $transaction->id,
                        'transaction_code' => $transaction->transaction_code ?? 'N/A',
                        'employee_name' => $transaction->employee->formal_name ?? 'Unknown',
                        'employee_code' => $transaction->employee->employee_code ?? 'N/A',
                        'department' => $transaction->employee->department->name ?? 'N/A',
                        'unit' => $transaction->employee->unit->name ?? 'N/A',
                        'vendor_name' => $transaction->vendor->name ?? 'Unknown',
                        'meal_date' => $transaction->meal_date ? Carbon::parse($transaction->meal_date)->format('Y-m-d') : 'Unknown',
                        'formatted_date' => $transaction->meal_date ? Carbon::parse($transaction->meal_date)->format('M d, Y') : 'Unknown',
                        'meal_time' => $transaction->meal_time ?? 'N/A',
                        'amount' => $transaction->amount ?? 65,
                        'entry_type' => $isManual ? 'Manual Entry' : 'QR Scan',
                        'is_reward' => isset($scanData['is_reward']) && $scanData['is_reward'] === true,
                        'scanned_by' => $isManual ? 'Admin' : ($transaction->vendor->name ?? 'Vendor')
                    ];
                } catch (\Exception $e) {
                    Log::warning('Error formatting entry for delete: ' . $e->getMessage());
                    continue;
                }
            }

            $stats = [
                'total' => count($formattedEntries),
                'manual' => count(array_filter($formattedEntries, fn($e) => $e['entry_type'] === 'Manual Entry')),
                'qr' => count(array_filter($formattedEntries, fn($e) => $e['entry_type'] === 'QR Scan')),
                'total_amount' => array_sum(array_column($formattedEntries, 'amount'))
            ];

            return response()->json([
                'success' => true,
                'date' => $targetDate,
                'formatted_date' => Carbon::parse($targetDate)->format('l, F jS, Y'),
                'entries' => $formattedEntries,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('getEntriesForDate error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load entries: ' . $e->getMessage(),
                'entries' => []
            ], 500);
        }
    }

    /**
     * Delete single meal entry
     */
    public function deleteMealEntry(Request $request, $id)
    {
        try {
            $transaction = MealTransaction::findOrFail($id);
            $reason = $request->reason ?? 'Fraudulent/Unauthorized scan';

            $employeeName = $transaction->employee ? $transaction->employee->formal_name : 'Unknown';

            Log::warning('MEAL ENTRY DELETED', [
                'admin_id' => Auth::id(),
                'admin_name' => Auth::user()->name,
                'transaction_id' => $id,
                'employee_name' => $employeeName,
                'meal_date' => $transaction->meal_date,
                'reason' => $reason
            ]);

            $transaction->delete();

            return response()->json([
                'success' => true,
                'message' => 'Meal entry has been deleted successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('deleteMealEntry error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete entry: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete meal entries
     */
    public function bulkDeleteEntries(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'transaction_ids' => 'required|array',
                'transaction_ids.*' => 'exists:meal_transactions,id',
                'reason' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $deletedCount = 0;
            $reason = $request->reason ?? 'Bulk deletion';
            $deletedIds = [];

            foreach ($request->transaction_ids as $id) {
                $transaction = MealTransaction::find($id);
                if ($transaction) {
                    $deletedIds[] = $id;
                    $transaction->delete();
                    $deletedCount++;
                }
            }

            Log::warning('BULK MEAL ENTRIES DELETED', [
                'admin_id' => Auth::id(),
                'admin_name' => Auth::user()->name,
                'deleted_count' => $deletedCount,
                'deleted_ids' => $deletedIds,
                'reason' => $reason
            ]);

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} entries.",
                'deleted_count' => $deletedCount
            ]);

        } catch (\Exception $e) {
            Log::error('bulkDeleteEntries error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete entries: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get unfed employees for a specific date
     */
    public function getUnfedEmployees(Request $request)
    {
        try {
            $targetDate = $request->get('date', Carbon::today()->toDateString());

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

        } catch (\Exception $e) {
            Log::error('getUnfedEmployees error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load unfed employees: ' . $e->getMessage(),
                'count' => 0,
                'employees' => []
            ], 500);
        }
    }

    /**
     * Get comprehensive report for selected date
     */
    public function getFeb2Report(Request $request)
    {
        try {
            $targetDate = $request->get('date', Carbon::today()->toDateString());

            Log::info('AdminMealController: Generating date report', [
                'target_date' => $targetDate
            ]);

            $totalMeals = MealTransaction::whereDate('meal_date', $targetDate)->count();
            $totalAmount = MealTransaction::whereDate('meal_date', $targetDate)->sum('amount');

            $mealsByVendor = MealTransaction::with('vendor')
                ->whereDate('meal_date', $targetDate)
                ->select('vendor_id', DB::raw('COUNT(*) as meal_count'), DB::raw('SUM(amount) as total_amount'))
                ->groupBy('vendor_id')
                ->get()
                ->map(function($item) {
                    return [
                        'vendor_id' => $item->vendor_id,
                        'vendor_name' => $item->vendor->name ?? 'Unknown Vendor',
                        'meal_count' => $item->meal_count,
                        'total_amount' => $item->total_amount
                    ];
                });

            $manualEntries = MealTransaction::whereDate('meal_date', $targetDate)
                ->where('qr_code_scanned', 'LIKE', 'MANUAL_ENTRY_%')
                ->count();

            $regularScans = $totalMeals - $manualEntries;

            $allEmployees = Employee::where('is_active', true)->count();
            $fedEmployees = MealTransaction::whereDate('meal_date', $targetDate)
                ->distinct('employee_id')
                ->count('employee_id');

            $transactions = MealTransaction::with(['employee.department', 'vendor'])
                ->whereDate('meal_date', $targetDate)
                ->orderBy('meal_time', 'desc')
                ->get()
                ->map(function($transaction) {
                    $isManual = strpos($transaction->qr_code_scanned ?? '', 'MANUAL_ENTRY') !== false;
                    $scanData = json_decode($transaction->scan_data ?? '{}', true);
                    $isReward = isset($scanData['is_reward']) && $scanData['is_reward'] === true;

                    return [
                        'id' => $transaction->id,
                        'transaction_code' => $transaction->transaction_code ?? 'N/A',
                        'type' => $isManual ? 'Manual Entry' : 'QR Scan',
                        'employee_name' => $transaction->employee->formal_name ?? 'Unknown',
                        'employee_code' => $transaction->employee->employee_code ?? 'N/A',
                        'department' => $transaction->employee->department->name ?? 'N/A',
                        'vendor' => $transaction->vendor->name ?? 'Unknown',
                        'meal_time' => $transaction->meal_time ?? 'N/A',
                        'amount' => $transaction->amount ?? 65,
                        'is_reward' => $isReward,
                        'created_at' => $transaction->created_at ? $transaction->created_at->format('Y-m-d H:i:s') : 'N/A'
                    ];
                });

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
                    'employees_not_fed' => $allEmployees - $fedEmployees,
                    'total_employees' => $allEmployees
                ],
                'vendor_breakdown' => $mealsByVendor,
                'transactions' => $transactions
            ]);

        } catch (\Exception $e) {
            Log::error('getFeb2Report error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get date stats for the last 30 days
     */
    public function getDateStats(Request $request)
    {
        try {
            $stats = [];
            for ($i = 0; $i < 30; $i++) {
                $date = Carbon::today()->subDays($i);
                $dateString = $date->toDateString();

                $stats[] = [
                    'date' => $dateString,
                    'formatted' => $date->format('D, M j, Y'),
                    'total' => MealTransaction::whereDate('meal_date', $dateString)->count(),
                    'manual' => MealTransaction::whereDate('meal_date', $dateString)
                        ->where('qr_code_scanned', 'LIKE', 'MANUAL_ENTRY_%')
                        ->count(),
                    'qr' => MealTransaction::whereDate('meal_date', $dateString)
                        ->whereNot('qr_code_scanned', 'LIKE', 'MANUAL_ENTRY_%')
                        ->count()
                ];
            }

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('getDateStats error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load stats: ' . $e->getMessage(),
                'stats' => []
            ], 500);
        }
    }
}
