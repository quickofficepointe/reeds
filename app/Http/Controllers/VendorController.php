<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\MealTransaction;
use App\Models\VendorInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\VendorInvoiceGenerated;
use Carbon\Carbon;

class VendorController extends Controller
{
    /**
     * Display vendor dashboard
     */
    public function index()
    {
        $vendor = Auth::user();
        $today = now()->format('Y-m-d');

        // Get today's stats
        $todayStats = MealTransaction::where('vendor_id', $vendor->id)
            ->whereDate('meal_date', $today)
            ->select(
                DB::raw('COUNT(*) as total_meals'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->first();

        // Get total stats
        $totalStats = MealTransaction::where('vendor_id', $vendor->id)
            ->select(
                DB::raw('COUNT(*) as total_meals'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->first();

        // Recent transactions
        $recentTransactions = MealTransaction::with('employee.department')
            ->where('vendor_id', $vendor->id)
            ->whereDate('meal_date', $today)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('reeds.vendor.index', compact('todayStats', 'totalStats', 'recentTransactions'));
    }

    /**
     * Show QR scanning page
     */
    public function scan()
    {
        $vendor = Auth::user();

        // Check if vendor is assigned to a unit
        if (!$vendor->unit_id) {
            return redirect()->route('vendor.dashboard')
                ->with('error', 'You are not assigned to any unit. Please contact administrator.');
        }

        // Get recent scans for this vendor
        $recentScans = MealTransaction::with(['employee.department', 'employee.unit'])
            ->where('vendor_id', $vendor->id)
            ->whereDate('meal_date', today())
            ->latest()
            ->take(10)
            ->get();

        return view('reeds.vendor.scan', compact('vendor', 'recentScans'));
    }

    /**
     * Process QR scan - UPDATED FOR MINIMAL QR CODES
     */
    public function processScan(Request $request)
    {
        Log::info('QR Scan Request Received', [
            'vendor_id' => Auth::id(),
            'qr_code_length' => strlen($request->qr_code),
            'qr_code_preview' => substr($request->qr_code, 0, 100),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        // Test endpoint check
        if ($request->has('test')) {
            return response()->json([
                'success' => true,
                'message' => 'Scan endpoint is accessible',
                'test' => true
            ]);
        }

        // Updated validation for minimal QR codes
        $validator = Validator::make($request->all(), [
            'qr_code' => 'required|string|min:3|max:100' // Adjusted for minimal codes
        ]);

        if ($validator->fails()) {
            Log::warning('QR code validation failed', [
                'errors' => $validator->errors()->toArray(),
                'qr_code_length' => strlen($request->qr_code)
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid QR code format. Please scan a valid QR code.',
                'error_type' => 'validation_error'
            ], 422);
        }

        $vendor = Auth::user();
        $qrCode = trim($request->qr_code);

        DB::beginTransaction();
        try {
            Log::info('Processing QR scan', [
                'qr_code_length' => strlen($qrCode),
                'qr_code_preview' => substr($qrCode, 0, 50),
                'vendor_id' => $vendor->id
            ]);

            $employee = null;
            $extractionMethod = 'unknown';

            // METHOD 1: Direct employee code match (PRIMARY FOR MINIMAL QR)
            $employee = Employee::where('employee_code', $qrCode)
                ->where('is_active', true)
                ->with('department')
                ->first();

            if ($employee) {
                $extractionMethod = 'direct_employee_code';
                Log::info('Found employee using direct employee code match', [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->formal_name
                ]);
            }

            // METHOD 2: Try QR code field match (backward compatibility)
            if (!$employee) {
                $employee = Employee::where('qr_code', $qrCode)
                    ->where('is_active', true)
                    ->with('department')
                    ->first();
                if ($employee) {
                    $extractionMethod = 'qr_code_match';
                    Log::info('Found employee using QR code field match', [
                        'employee_id' => $employee->id,
                        'employee_name' => $employee->formal_name
                    ]);
                }
            }

            // METHOD 3: Extract from text QR (for existing QR codes)
            if (!$employee && strpos($qrCode, 'REEDS AFRICA CONSULT') !== false) {
                Log::info('Detected text QR code, extracting employee code...');
                $employeeCode = $this->extractEmployeeCodeFromText($qrCode);

                if ($employeeCode) {
                    Log::info('Extracted employee code from QR text:', ['employee_code' => $employeeCode]);

                    $employee = Employee::where('employee_code', $employeeCode)
                        ->where('is_active', true)
                        ->with('department')
                        ->first();

                    if ($employee) {
                        $extractionMethod = 'text_extraction';
                        Log::info('Found employee using extracted code', [
                            'employee_id' => $employee->id,
                            'employee_name' => $employee->formal_name
                        ]);
                    }
                }
            }

            // Employee not found
            if (!$employee) {
                Log::warning('No employee found for QR code', [
                    'qr_code_length' => strlen($qrCode),
                    'qr_code_preview' => substr($qrCode, 0, 30),
                    'method' => $extractionMethod
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found. Please ensure you\'re scanning a valid employee QR code.',
                    'error_type' => 'employee_not_found'
                ], 200);
            }

            // Check if employee can be fed
            $canFeed = $employee->canBeFedNow();
            if (!$canFeed['can_be_fed']) {
                Log::warning('Employee cannot be fed', [
                    'employee_id' => $employee->id,
                    'reason' => $canFeed['reason']
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $canFeed['reason'],
                    'error_type' => 'feeding_hours'
                ], 200);
            }

            // Check for duplicate meal today
            $existingMeal = $employee->getTodayMealTransaction();
            if ($existingMeal) {
                Log::warning('Duplicate meal attempt', [
                    'employee_id' => $employee->id,
                    'existing_transaction' => $existingMeal->transaction_code,
                    'existing_time' => $existingMeal->created_at
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Employee has already been fed today at ' . $existingMeal->meal_time,
                    'error_type' => 'duplicate_meal'
                ], 200);
            }

            // Create meal transaction
            $transaction = $employee->recordMeal($vendor->id, $qrCode);

            DB::commit();

            Log::info('Meal transaction created successfully', [
                'transaction_code' => $transaction->transaction_code,
                'employee_id' => $employee->id,
                'employee_name' => $employee->formal_name,
                'amount' => $transaction->amount,
                'method' => $extractionMethod
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Meal recorded successfully!',
                'transaction' => [
                    'code' => $transaction->transaction_code,
                    'employee_name' => $employee->formal_name,
                    'employee_code' => $employee->employee_code,
                    'department' => $employee->department->name ?? 'N/A',
                    'amount' => $transaction->amount,
                    'time' => $transaction->meal_time,
                    'date' => $transaction->meal_date,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('QR Scan Processing Error: ' . $e->getMessage(), [
                'qr_code' => $qrCode,
                'vendor_id' => $vendor->id,
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'A system error occurred while processing the scan. Please try again.',
                'error_type' => 'system_error'
            ], 500);
        }
    }

    /**
     * Extract employee code from text QR code
     */
    private function extractEmployeeCodeFromText(string $qrText): ?string
    {
        // Method 1: Look for "Employee No: XXXXX" pattern
        if (preg_match('/Employee No:\s*([A-Za-z0-9]+)/i', $qrText, $matches)) {
            $code = trim($matches[1]);
            Log::info('Extracted employee code using pattern 1:', ['code' => $code]);
            return $code;
        }

        // Method 2: Look for employee code on the line after "Employee No:"
        $lines = explode("\n", $qrText);
        for ($i = 0; $i < count($lines) - 1; $i++) {
            if (strpos($lines[$i], 'Employee No:') !== false && isset($lines[$i + 1])) {
                $potentialCode = trim($lines[$i + 1]);
                if (preg_match('/^[A-Za-z0-9]{3,20}$/', $potentialCode)) {
                    Log::info('Extracted employee code using pattern 2:', ['code' => $potentialCode]);
                    return $potentialCode;
                }
            }
        }

        // Method 3: Look for any alphanumeric code that might be employee code
        if (preg_match('/Employee No[^A-Za-z0-9]*([A-Za-z0-9]{3,20})/i', $qrText, $matches)) {
            $code = trim($matches[1]);
            Log::info('Extracted employee code using pattern 3:', ['code' => $code]);
            return $code;
        }

        Log::warning('Could not extract employee code from text QR', [
            'text_preview' => substr($qrText, 0, 100),
            'lines' => $lines
        ]);

        return null;
    }

    /**
     * Get scan history
     */
   /**
 * Get scan history - FIXED VERSION
 */
/**
 * Get scan history - CORRECTED VERSION
 */
public function getScanHistory(Request $request)
{
    try {
        $vendor = Auth::user();
        $date = $request->get('date', today()->format('Y-m-d'));

        Log::info('Fetching scan history for vendor', [
            'vendor_id' => $vendor->id,
            'date' => $date
        ]);

        // Start the query
        $query = MealTransaction::query();

        // Apply vendor filter
        $query->where('vendor_id', $vendor->id);

        // Apply date filter
        if ($date && $date !== 'all') {
            $query->whereDate('meal_date', $date);
        }

        // Eager load employee with all necessary relationships
        $query->with([
            'employee.department',
            'employee.unit'
        ]);

        // Get transactions
        $transactions = $query->orderBy('created_at', 'desc')->get();

        Log::info('Found transactions', [
            'count' => $transactions->count(),
            'vendor_id' => $vendor->id,
            'date' => $date
        ]);

        // Transform data for frontend
        $formattedTransactions = $transactions->map(function ($transaction) {
            // Safely get employee data
            $employee = $transaction->employee;

            return [
                'id' => $transaction->id,
                'transaction_code' => $transaction->transaction_code ?? 'N/A',
                'employee' => [
                    'formal_name' => $employee->formal_name ?? 'N/A',
                    'employee_code' => $employee->employee_code ?? 'N/A',
                    'department' => [
                        'name' => $employee->department->name ?? 'N/A'
                    ],
                    'unit' => [
                        'name' => $employee->unit->name ?? 'N/A'
                    ]
                ],
                'amount' => floatval($transaction->amount ?? 0),
                'meal_time' => $transaction->meal_time ?? 'N/A',
                'meal_date' => $transaction->meal_date ? $transaction->meal_date->format('Y-m-d') : 'N/A',
                'created_at' => $transaction->created_at ? $transaction->created_at->format('H:i:s') : 'N/A',
            ];
        });

        // Calculate stats
        $totalScans = $transactions->count();
        $totalRevenue = $transactions->sum('amount');
        $averageDaily = $totalScans; // For single day, it's just the count

        return response()->json([
            'success' => true,
            'transactions' => $formattedTransactions,
            'stats' => [
                'total_scans' => $totalScans,
                'total_revenue' => floatval($totalRevenue),
                'average_daily' => $averageDaily
            ],
            'meta' => [
                'current_page' => 1,
                'total' => $totalScans,
                'per_page' => 50
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('Error in getScanHistory: ' . $e->getMessage(), [
            'exception' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Error loading history: ' . $e->getMessage(),
            'error' => $e->getMessage()
        ], 500);
    }
}
    /**
     * Get dashboard stats
     */
    public function getDashboardStats()
    {
        $vendor = Auth::user();
        $today = now()->format('Y-m-d');

        $todayStats = MealTransaction::where('vendor_id', $vendor->id)
            ->whereDate('meal_date', $today)
            ->select(
                DB::raw('COUNT(*) as total_meals'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->first();

        $totalStats = MealTransaction::where('vendor_id', $vendor->id)
            ->select(
                DB::raw('COUNT(*) as total_meals'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->first();

        return response()->json([
            'success' => true,
            'today' => [
                'scans' => $todayStats->total_meals ?? 0,
                'revenue' => $todayStats->total_amount ?? 0
            ],
            'total' => [
                'scans' => $totalStats->total_meals ?? 0,
                'revenue' => $totalStats->total_amount ?? 0
            ]
        ]);
    }

    /**
     * Show history page
     */
    public function history()
    {
        return view('reeds.vendor.history');
    }

    /**
     * Show performance analytics page
     */
    public function performance()
    {
        return view('reeds.vendor.performance');
    }

    /**
     * Show invoices page
     */
public function invoices()
{
    return view('reeds.vendor.invoices');
}
public function invoicesData()
{
    $vendor = Auth::user();

    $invoices = VendorInvoice::where('vendor_id', $vendor->id)
        ->orderBy('created_at', 'desc')
        ->get();

    $stats = [
        'pending_invoices' => $invoices->where('status', 'pending')->count(),
        'paid_invoices' => $invoices->where('status', 'paid')->count(),
        'total_revenue' => $invoices->sum('total_amount')
    ];

    return response()->json([
        'success' => true,
        'invoices' => $invoices->map(function($invoice) {
            return [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'period_start' => $invoice->period_start->format('Y-m-d'),
                'period_end' => $invoice->period_end->format('Y-m-d'),
                'period' => $invoice->period_start->format('M d') . ' - ' . $invoice->period_end->format('M d, Y'),
                'total_amount' => floatval($invoice->total_amount),
                'total_scans' => $invoice->total_scans,
                'status' => $invoice->status,
                'invoice_date' => $invoice->invoice_date ? $invoice->invoice_date->format('Y-m-d') : $invoice->created_at->format('Y-m-d'),
                'due_date' => $invoice->due_date ? $invoice->due_date->format('Y-m-d') : null,
                'created_at' => $invoice->created_at->format('Y-m-d H:i:s'),
                'is_test' => $invoice->is_test ?? false
            ];
        }),
        'stats' => $stats
    ]);
}
/**
 * Get invoice details for modal view
 */
public function getInvoiceDetails($id)
{
    $vendor = Auth::user();

    $invoice = VendorInvoice::with(['items', 'vendor.profile'])
        ->where('vendor_id', $vendor->id)
        ->where('id', $id)
        ->firstOrFail();

    return response()->json([
        'success' => true,
        'invoice' => [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'period_start' => $invoice->period_start->format('F j, Y'),
            'period_end' => $invoice->period_end->format('F j, Y'),
            'total_amount' => floatval($invoice->total_amount),
            'total_scans' => $invoice->total_scans,
            'status' => $invoice->status,
            'invoice_date' => $invoice->invoice_date->format('F j, Y'),
            'due_date' => $invoice->due_date->format('F j, Y'),
            'is_test' => $invoice->is_test ?? false,
            'notes' => $invoice->notes,
            'items' => $invoice->items->map(function($item) {
                return [
                    'date' => $item->date->format('M j, Y'),
                    'description' => $item->description,
                    'scans' => $item->scans,
                    'rate' => floatval($item->rate),
                    'amount' => floatval($item->amount)
                ];
            })
        ]
    ]);
}
    /**
     * View invoice
     */
    public function viewInvoice($id)
{
    $vendor = Auth::user();

    $invoice = VendorInvoice::with(['items', 'vendor.profile'])
        ->where('vendor_id', $vendor->id)
        ->where('id', $id)
        ->firstOrFail();

    return view('reeds.vendor.invoice-view', compact('invoice'));
}

    /**
     * Download invoice
     */
  public function downloadInvoice($id)
{
    $vendor = Auth::user();
    $invoice = VendorInvoice::with(['items', 'vendor.profile'])
        ->where('vendor_id', $vendor->id)
        ->where('id', $id)
        ->firstOrFail();

    $pdf = \PDF::loadView('reeds.vendor.invoice-pdf', compact('invoice'));

    return $pdf->download('invoice-' . $invoice->invoice_number . '.pdf');
}

    /**
     * Get daily analytics
     */
/**
 * Get daily analytics - FIXED VERSION
 */
public function dailyAnalytics()
{
    try {
        $vendor = Auth::user();
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now();

        Log::info('Fetching daily analytics', [
            'vendor_id' => $vendor->id,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        // Get daily transactions
        $transactions = MealTransaction::where('vendor_id', $vendor->id)
            ->whereBetween('meal_date', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(meal_date) as date'),
                DB::raw('COUNT(*) as scans'),
                DB::raw('SUM(amount) as revenue')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Fill in missing days
        $allDates = [];
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $allDates[] = $currentDate->format('Y-m-d');
            $currentDate->addDay();
        }

        $dailyData = [];
        foreach ($allDates as $date) {
            $transaction = $transactions->firstWhere('date', $date);
            $dailyData[] = [
                'date' => $date,
                'scans' => $transaction ? $transaction->scans : 0,
                'revenue' => $transaction ? floatval($transaction->revenue) : 0
            ];
        }

        $labels = array_map(function($item) {
            return Carbon::parse($item['date'])->format('M d');
        }, $dailyData);

        $scansData = array_column($dailyData, 'scans');
        $revenueData = array_column($dailyData, 'revenue');

        // Get department distribution
        $departments = MealTransaction::where('vendor_id', $vendor->id)
            ->whereBetween('meal_date', [$startDate, $endDate])
            ->join('employees', 'meal_transactions.employee_id', '=', 'employees.id')
            ->leftJoin('departments', 'employees.department_id', '=', 'departments.id')
            ->select(
                'departments.name',
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('departments.name')
            ->get();

        // Get peak hours
        $peakHours = MealTransaction::where('vendor_id', $vendor->id)
            ->whereBetween('meal_date', [$startDate, $endDate])
            ->select(
                DB::raw('HOUR(meal_time) as hour'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        $hourLabels = [];
        $hourData = [];
        for ($i = 6; $i <= 18; $i++) { // 6 AM to 6 PM
            $hourLabels[] = sprintf('%02d:00', $i);
            $transaction = $peakHours->firstWhere('hour', $i);
            $hourData[] = $transaction ? $transaction->count : 0;
        }

        // Get top employees
        $topEmployees = MealTransaction::where('vendor_id', $vendor->id)
            ->whereBetween('meal_date', [$startDate, $endDate])
            ->join('employees', 'meal_transactions.employee_id', '=', 'employees.id')
            ->leftJoin('departments', 'employees.department_id', '=', 'departments.id')
            ->select(
                'employees.id',
                'employees.formal_name',
                'employees.employee_code',
                'departments.name as department',
                DB::raw('COUNT(*) as total_scans'),
                DB::raw('MAX(meal_transactions.created_at) as last_scan')
            )
            ->groupBy('employees.id', 'employees.formal_name', 'employees.employee_code', 'departments.name')
            ->orderBy('total_scans', 'desc')
            ->limit(10)
            ->get()
            ->map(function($employee) {
                $lastScan = $employee->last_scan ? Carbon::parse($employee->last_scan)->format('M d, H:i') : 'N/A';

                return [
                    'formal_name' => $employee->formal_name ?? 'Unknown',
                    'employee_code' => $employee->employee_code ?? 'N/A',
                    'department' => $employee->department ?? 'N/A',
                    'total_scans' => $employee->total_scans,
                    'last_scan' => $lastScan,
                    'avg_time' => 'N/A' // We'll calculate this separately if needed
                ];
            });

        // Calculate metrics
        $totalScans = $transactions->sum('scans');
        $totalRevenue = $transactions->sum('revenue');
        $averageDaily = $totalScans / max(count($transactions), 1);

        // Find best day
        $bestDay = $transactions->sortByDesc('scans')->first();

        return response()->json([
            'success' => true,
            'metrics' => [
                'total_scans' => $totalScans,
                'total_revenue' => floatval($totalRevenue),
                'average_daily' => round($averageDaily, 1),
                'best_day' => $bestDay ? [
                    'date' => Carbon::parse($bestDay->date)->format('M d'),
                    'count' => $bestDay->scans
                ] : null
            ],
            'charts' => [
                'daily_scans' => [
                    'labels' => $labels,
                    'data' => $scansData
                ],
                'revenue' => [
                    'labels' => $labels,
                    'data' => $revenueData
                ],
                'departments' => [
                    'labels' => $departments->pluck('name')->toArray(),
                    'data' => $departments->pluck('count')->toArray()
                ],
                'peak_hours' => [
                    'labels' => $hourLabels,
                    'data' => $hourData
                ]
            ],
            'top_employees' => $topEmployees
        ]);

    } catch (\Exception $e) {
        Log::error('Error in dailyAnalytics: ' . $e->getMessage(), [
            'exception' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to load analytics: ' . $e->getMessage()
        ], 500);
    }
}

    /**
     * Get weekly analytics
     */
    public function weeklyAnalytics()
    {
        $vendor = Auth::user();
        $startDate = Carbon::now()->subWeeks(12);

        $transactions = MealTransaction::where('vendor_id', $vendor->id)
            ->where('meal_date', '>=', $startDate)
            ->select(
                DB::raw('YEARWEEK(meal_date, 1) as week'),
                DB::raw('MIN(DATE(meal_date)) as week_start'),
                DB::raw('MAX(DATE(meal_date)) as week_end'),
                DB::raw('COUNT(*) as scans'),
                DB::raw('SUM(amount) as revenue')
            )
            ->groupBy('week')
            ->orderBy('week')
            ->get();

        $labels = $transactions->map(function($transaction) {
            return 'Week ' . Carbon::parse($transaction->week_start)->format('W');
        })->toArray();

        $scansData = $transactions->pluck('scans')->toArray();
        $revenueData = $transactions->pluck('revenue')->toArray();

        // Calculate metrics
        $totalScans = $transactions->sum('scans');
        $totalRevenue = $transactions->sum('revenue');
        $averageWeekly = $totalScans / max(count($transactions), 1);
        $bestWeek = $transactions->sortByDesc('scans')->first();

        return response()->json([
            'success' => true,
            'metrics' => [
                'total_scans' => $totalScans,
                'total_revenue' => $totalRevenue,
                'average_weekly' => round($averageWeekly, 1),
                'best_week' => $bestWeek ? [
                    'week' => 'Week ' . Carbon::parse($bestWeek->week_start)->format('W'),
                    'count' => $bestWeek->scans
                ] : null
            ],
            'charts' => [
                'weekly_scans' => [
                    'labels' => $labels,
                    'data' => $scansData
                ],
                'weekly_revenue' => [
                    'labels' => $labels,
                    'data' => $revenueData
                ]
            ]
        ]);
    }

    /**
     * Get monthly analytics
     */
    public function monthlyAnalytics()
    {
        $vendor = Auth::user();
        $startDate = Carbon::now()->subMonths(12);

        $transactions = MealTransaction::where('vendor_id', $vendor->id)
            ->where('meal_date', '>=', $startDate)
            ->select(
                DB::raw('DATE_FORMAT(meal_date, "%Y-%m") as month'),
                DB::raw('COUNT(*) as scans'),
                DB::raw('SUM(amount) as revenue')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $labels = $transactions->map(function($transaction) {
            return Carbon::createFromFormat('Y-m', $transaction->month)->format('M Y');
        })->toArray();

        $scansData = $transactions->pluck('scans')->toArray();
        $revenueData = $transactions->pluck('revenue')->toArray();

        // Calculate metrics
        $totalScans = $transactions->sum('scans');
        $totalRevenue = $transactions->sum('revenue');
        $averageMonthly = $totalScans / max(count($transactions), 1);
        $bestMonth = $transactions->sortByDesc('scans')->first();

        return response()->json([
            'success' => true,
            'metrics' => [
                'total_scans' => $totalScans,
                'total_revenue' => $totalRevenue,
                'average_monthly' => round($averageMonthly, 1),
                'best_month' => $bestMonth ? [
                    'month' => Carbon::createFromFormat('Y-m', $bestMonth->month)->format('F Y'),
                    'count' => $bestMonth->scans
                ] : null
            ],
            'charts' => [
                'monthly_scans' => [
                    'labels' => $labels,
                    'data' => $scansData
                ],
                'monthly_revenue' => [
                    'labels' => $labels,
                    'data' => $revenueData
                ]
            ]
        ]);
    }

    /**
     * Generate test invoice
     */
    public function generateTestInvoice(Request $request)
    {
        $vendor = Auth::user();

        // Determine period
        if ($request->period === 'custom') {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
        } else {
            // Calculate current 2-week period
            $startDate = Carbon::now()->startOfWeek(); // Monday
            $endDate = Carbon::now()->endOfWeek()->subDay(); // Saturday

            // If it's the second week, go back one week
            $weekNumber = Carbon::now()->weekOfMonth;
            if ($weekNumber % 2 === 0) {
                $startDate->subWeek();
                $endDate->subWeek();
            }
        }

        // Get transactions for period
        $transactions = MealTransaction::where('vendor_id', $vendor->id)
            ->whereBetween('meal_date', [$startDate, $endDate])
            ->get();

        // Create invoice
        $invoice = new VendorInvoice();
        $invoice->vendor_id = $vendor->id;
        $invoice->invoice_number = 'TEST-' . Carbon::now()->format('Ymd-His');
        $invoice->period_start = $startDate->format('Y-m-d');
        $invoice->period_end = $endDate->format('Y-m-d');
        $invoice->total_scans = $transactions->count();
        $invoice->total_amount = $transactions->sum('amount');
        $invoice->status = 'draft';
        $invoice->is_test = true;
        $invoice->save();

        // Generate invoice items
        foreach ($transactions->groupBy('meal_date') as $date => $dayTransactions) {
            $invoice->items()->create([
                'date' => $date,
                'scans' => $dayTransactions->count(),
                'amount' => $dayTransactions->sum('amount'),
                'rate' => 70, // Ksh 70 per meal
                'description' => 'Meal transactions for ' . Carbon::parse($date)->format('F j, Y')
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Test invoice generated successfully',
            'invoice' => $invoice
        ]);
    }

    /**
     * Automated invoice generation command (to be run via scheduler)
     */
    public function generateBiWeeklyInvoices()
    {
        Log::info('Starting bi-weekly invoice generation');

        // Get all active vendors
        $vendors = \App\Models\User::where('role', 'vendor')
            ->where('is_active', true)
            ->whereHas('profile', function($q) {
                $q->where('is_verified', true);
            })
            ->get();

        $invoiceCount = 0;
        $errors = [];

        foreach ($vendors as $vendor) {
            try {
                // Calculate 2-week period (Monday to Saturday, every 2 weeks)
                $today = Carbon::now();

                // Find the most recent Monday that's part of a 2-week cycle
                $startDate = $today->copy()->startOfWeek(); // Monday
                $endDate = $today->copy()->endOfWeek()->subDay(); // Saturday

                // Check if this is the second week of the cycle
                $weekOfYear = $startDate->weekOfYear;
                if ($weekOfYear % 2 !== 0) {
                    // If odd week, go back one week to get the full 2-week period
                    $startDate->subWeek();
                    $endDate->subWeek();
                }

                // Check if invoice already exists for this period
                $existingInvoice = VendorInvoice::where('vendor_id', $vendor->id)
                    ->where('period_start', $startDate->format('Y-m-d'))
                    ->where('period_end', $endDate->format('Y-m-d'))
                    ->first();

                if ($existingInvoice) {
                    Log::info("Invoice already exists for vendor {$vendor->id}, period {$startDate} to {$endDate}");
                    continue;
                }

                // Get transactions for period
                $transactions = MealTransaction::where('vendor_id', $vendor->id)
                    ->whereBetween('meal_date', [$startDate, $endDate])
                    ->get();

                if ($transactions->isEmpty()) {
                    Log::info("No transactions found for vendor {$vendor->id}, period {$startDate} to {$endDate}");
                    continue;
                }

                // Generate invoice number
                $invoiceNumber = 'INV-' . $vendor->id . '-' . $endDate->format('Ymd') . '-' . str_pad($vendor->invoices()->count() + 1, 3, '0', STR_PAD_LEFT);

                // Create invoice
                $invoice = new VendorInvoice();
                $invoice->vendor_id = $vendor->id;
                $invoice->invoice_number = $invoiceNumber;
                $invoice->period_start = $startDate->format('Y-m-d');
                $invoice->period_end = $endDate->format('Y-m-d');
                $invoice->total_scans = $transactions->count();
                $invoice->total_amount = $transactions->sum('amount');
                $invoice->status = 'pending';
                $invoice->due_date = $endDate->copy()->addDays(30)->format('Y-m-d');
                $invoice->save();

                // Generate invoice items
                foreach ($transactions->groupBy('meal_date') as $date => $dayTransactions) {
                    $invoice->items()->create([
                        'date' => $date,
                        'scans' => $dayTransactions->count(),
                        'amount' => $dayTransactions->sum('amount'),
                        'rate' => 70, // Ksh 70 per meal
                        'description' => 'Meal transactions for ' . Carbon::parse($date)->format('F j, Y')
                    ]);
                }

                // Send invoice emails
                $this->sendInvoiceEmails($invoice, $vendor);

                $invoiceCount++;
                Log::info("Invoice generated for vendor {$vendor->id}: {$invoiceNumber}");

            } catch (\Exception $e) {
                $errors[] = "Vendor {$vendor->id}: " . $e->getMessage();
                Log::error("Failed to generate invoice for vendor {$vendor->id}: " . $e->getMessage());
            }
        }

        Log::info("Bi-weekly invoice generation completed. Generated: {$invoiceCount}, Errors: " . count($errors));

        return [
            'generated' => $invoiceCount,
            'errors' => $errors
        ];
    }

    /**
     * Send invoice emails to recipients
     */
    private function sendInvoiceEmails(VendorInvoice $invoice, $vendor)
    {
        $recipients = [
            'isaacnmuteru@gmail.com',
            'info@driftplus.co.ke',
            'info@vibeeplug.com',
            'info@quickofficepointe.co.ke'
        ];

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient)->send(new VendorInvoiceGenerated($invoice, $vendor));
                Log::info("Invoice email sent to {$recipient} for invoice {$invoice->invoice_number}");
            } catch (\Exception $e) {
                Log::error("Failed to send invoice email to {$recipient}: " . $e->getMessage());
            }
        }

        // Also send to vendor
        try {
            Mail::to($vendor->email)->send(new VendorInvoiceGenerated($invoice, $vendor, true));
            Log::info("Invoice email sent to vendor {$vendor->email} for invoice {$invoice->invoice_number}");
        } catch (\Exception $e) {
            Log::error("Failed to send invoice email to vendor {$vendor->email}: " . $e->getMessage());
        }
    }
}
