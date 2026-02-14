<?php
// app/Http/Controllers/VendorController.php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\MealTransaction;
use App\Models\VendorInvoice;
use App\Services\InvoiceGenerationService;
use App\Services\InvoicePeriodService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\VendorInvoiceGenerated;
use Carbon\Carbon;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;

class VendorController extends Controller
{
    protected $invoiceService;
    protected $periodService;

    public function __construct(InvoiceGenerationService $invoiceService, InvoicePeriodService $periodService)
    {
        $this->invoiceService = $invoiceService;
        $this->periodService = $periodService;
    }

    /**
     * Display vendor dashboard
     */
    public function index()
    {
        try {
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

        } catch (\Exception $e) {
            Log::error('Error loading vendor dashboard: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to load dashboard. Please try again.');
        }
    }

    /**
     * Show QR scanning page
     */
    public function scan()
    {
        try {
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

        } catch (\Exception $e) {
            Log::error('Error loading scan page: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to load scan page.');
        }
    }

    /**
     * Process QR scan
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

        if ($request->has('test')) {
            return response()->json([
                'success' => true,
                'message' => 'Scan endpoint is accessible',
                'test' => true
            ]);
        }

        $validator = Validator::make($request->all(), [
            'qr_code' => 'required|string|min:3|max:100'
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

            // METHOD 1: Direct employee code match
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

            // METHOD 2: Try QR code field match
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

            // METHOD 3: Extract from text QR
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
        if (preg_match('/Employee No:\s*([A-Za-z0-9]+)/i', $qrText, $matches)) {
            $code = trim($matches[1]);
            Log::info('Extracted employee code using pattern 1:', ['code' => $code]);
            return $code;
        }

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
    public function getScanHistory(Request $request)
    {
        try {
            $vendor = Auth::user();
            $date = $request->get('date', today()->format('Y-m-d'));

            Log::info('Fetching scan history for vendor', [
                'vendor_id' => $vendor->id,
                'date' => $date
            ]);

            $query = MealTransaction::where('vendor_id', $vendor->id)
                ->with(['employee.department', 'employee.unit']);

            if ($date && $date !== 'all') {
                $query->whereDate('meal_date', $date);
            }

            $perPage = $request->get('per_page', 50);
            $transactions = $query->orderBy('created_at', 'desc')->paginate($perPage);

            Log::info('Found transactions', [
                'count' => $transactions->count(),
                'vendor_id' => $vendor->id,
                'date' => $date
            ]);

            $formattedTransactions = $transactions->map(function ($transaction) {
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

            $totalScans = $transactions->total();
            $totalRevenue = $transactions->sum('amount');

            return response()->json([
                'success' => true,
                'transactions' => $formattedTransactions,
                'stats' => [
                    'total_scans' => $totalScans,
                    'total_revenue' => floatval($totalRevenue),
                    'average_daily' => $totalScans
                ],
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getScanHistory: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load history. Please try again.'
            ], 500);
        }
    }

    /**
     * Get dashboard stats
     */
    public function getDashboardStats()
    {
        try {
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

        } catch (\Exception $e) {
            Log::error('Error getting dashboard stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard stats'
            ], 500);
        }
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

    /**
     * Get periods data
     */
    public function getPeriods()
    {
        try {
            $periods = $this->periodService->getAllPeriodsFor2026();

            return response()->json([
                'success' => true,
                'current' => $this->periodService->getCurrentPeriod(),
                'previous' => $this->periodService->getPreviousPeriod(),
                'next' => $this->periodService->getNextPeriod(),
                'all_periods' => $periods
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting periods: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load periods'
            ], 500);
        }
    }

    /**
     * Get invoices data with pagination and filtering
     */
    public function invoicesData(Request $request)
    {
        try {
            $vendor = Auth::user();

            $query = VendorInvoice::with('vendor.profile')
                ->withCount('items')
                ->where('vendor_id', $vendor->id)
                ->orderBy('created_at', 'desc');

            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->has('date_from') && $request->has('date_to')) {
                $query->whereBetween('invoice_date', [$request->date_from, $request->date_to]);
            }

            if ($request->has('cycle') && $request->cycle !== 'all') {
                $query->where('cycle_number', $request->cycle);
            }

            if ($request->has('search') && !empty($request->search)) {
                $query->where('invoice_number', 'like', '%' . $request->search . '%');
            }

            $perPage = $request->get('per_page', 10);
            $invoices = $query->paginate($perPage);

            $stats = [
                'pending_invoices' => VendorInvoice::where('vendor_id', $vendor->id)
                    ->where('status', 'pending')->count(),
                'paid_invoices' => VendorInvoice::where('vendor_id', $vendor->id)
                    ->where('status', 'paid')->count(),
                'overdue_invoices' => VendorInvoice::where('vendor_id', $vendor->id)
                    ->where('status', 'overdue')->count(),
                'total_revenue' => VendorInvoice::where('vendor_id', $vendor->id)
                    ->where('status', 'paid')->sum('total_amount'),
                'total_pending_amount' => VendorInvoice::where('vendor_id', $vendor->id)
                    ->where('status', 'pending')->sum('total_amount'),
                'total_overdue_amount' => VendorInvoice::where('vendor_id', $vendor->id)
                    ->where('status', 'overdue')->sum('total_amount')
            ];

            return response()->json([
                'success' => true,
                'invoices' => $invoices->map(function($invoice) {
                    return [
                        'id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'period_start' => $invoice->period_start->format('Y-m-d'),
                        'period_end' => $invoice->period_end->format('Y-m-d'),
                        'period' => $invoice->formatted_period,
                        'total_amount' => floatval($invoice->total_amount),
                        'formatted_total' => $invoice->formatted_total,
                        'total_scans' => $invoice->total_scans,
                        'status' => $invoice->status,
                        'status_text' => $invoice->status_text,
                        'status_badge' => $invoice->status_badge,
                        'invoice_date' => $invoice->invoice_date->format('Y-m-d'),
                        'due_date' => $invoice->due_date->format('Y-m-d'),
                        'is_test' => $invoice->is_test,
                        'cycle' => $invoice->cycle_number,
                        'period_name' => $invoice->period_name,
                        'item_count' => $invoice->items_count,
                        'created_at' => $invoice->created_at->format('Y-m-d H:i:s'),
                        'is_overdue' => $invoice->isOverdue()
                    ];
                }),
                'stats' => $stats,
                'pagination' => [
                    'current_page' => $invoices->currentPage(),
                    'last_page' => $invoices->lastPage(),
                    'per_page' => $invoices->perPage(),
                    'total' => $invoices->total()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading invoices: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load invoices. Please try again.'
            ], 500);
        }
    }

    /**
     * Get invoice details for modal view
     */
    public function getInvoiceDetails($id)
    {
        try {
            $vendor = Auth::user();

            $invoice = VendorInvoice::with(['items', 'vendor.profile'])
                ->where('id', $id)
                ->where('vendor_id', $vendor->id)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'invoice' => [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'period_start' => $invoice->period_start->format('F j, Y'),
                    'period_end' => $invoice->period_end->format('F j, Y'),
                    'total_amount' => floatval($invoice->total_amount),
                    'formatted_total' => $invoice->formatted_total,
                    'total_scans' => $invoice->total_scans,
                    'status' => $invoice->status,
                    'status_text' => $invoice->status_text,
                    'status_badge' => $invoice->status_badge,
                    'invoice_date' => $invoice->invoice_date->format('F j, Y'),
                    'due_date' => $invoice->due_date->format('F j, Y'),
                    'is_test' => $invoice->is_test,
                    'notes' => $invoice->notes,
                    'cycle' => $invoice->cycle_number,
                    'period_name' => $invoice->period_name,
                    'vendor_phone' => $invoice->vendor_phone,
                    'vendor_email' => $invoice->vendor_email,
                    'vendor_business_name' => $invoice->vendor_business_name,
                    'bank_details' => $invoice->vendor_bank_details,
                    'items' => $invoice->items->map(function($item) {
                        return [
                            'date' => $item->date->format('M j, Y'),
                            'day' => $item->day_name,
                            'description' => $item->description,
                            'scans' => $item->scans,
                            'rate' => floatval($item->rate),
                            'formatted_rate' => $item->formatted_rate,
                            'amount' => floatval($item->amount),
                            'formatted_amount' => $item->formatted_amount
                        ];
                    })
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found or you do not have permission to view it.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error loading invoice details: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load invoice details.'
            ], 500);
        }
    }

    /**
     * View invoice
     */
    public function viewInvoice($id)
    {
        try {
            $vendor = Auth::user();

            $invoice = VendorInvoice::with(['items', 'vendor.profile'])
                ->where('id', $id)
                ->where('vendor_id', $vendor->id)
                ->firstOrFail();

            return view('reeds.vendor.invoice-view', compact('invoice'));

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('vendor.invoices')
                ->with('error', 'Invoice not found or you do not have permission to view it.');
        } catch (\Exception $e) {
            Log::error('Error viewing invoice: ' . $e->getMessage());
            return redirect()->route('vendor.invoices')
                ->with('error', 'Failed to load invoice.');
        }
    }

    /**
     * Download invoice PDF
     */
    public function downloadInvoice($id)
    {
        try {
            $vendor = Auth::user();

            $invoice = VendorInvoice::with(['items', 'vendor.profile'])
                ->where('id', $id)
                ->where('vendor_id', $vendor->id)
                ->firstOrFail();

            $pdf = Pdf::loadView('reeds.vendor.invoice-pdf', compact('invoice'));

            return $pdf->download('invoice-' . $invoice->invoice_number . '.pdf');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('vendor.invoices')
                ->with('error', 'Invoice not found or you do not have permission to download it.');
        } catch (\Exception $e) {
            Log::error('PDF generation failed: ' . $e->getMessage());
            return redirect()->route('vendor.invoices')
                ->with('error', 'Failed to generate PDF. Please try again.');
        }
    }

    /**
     * Generate test invoice with validation
     */
    public function generateTestInvoice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'period' => 'required|in:current,previous,custom,first_period',
            'start_date' => 'required_if:period,custom|date|nullable',
            'end_date' => 'required_if:period,custom|date|after_or_equal:start_date|nullable'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $vendor = Auth::user();

            // Special case for first period test (Feb 2-14, 2026)
            if ($request->period === 'first_period') {
                $invoice = $this->invoiceService->generateFirstPeriodTestInvoice($vendor);

                return response()->json([
                    'success' => true,
                    'message' => 'First period test invoice generated successfully (Feb 2-14, 2026)',
                    'invoice' => [
                        'id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number
                    ]
                ]);
            }

            // Check if test invoice already exists for this period
            if ($request->period === 'custom') {
                $startDate = Carbon::parse($request->start_date);
                $endDate = Carbon::parse($request->end_date);

                $existingTest = VendorInvoice::where('vendor_id', $vendor->id)
                    ->where('is_test', true)
                    ->whereDate('period_start', $startDate)
                    ->whereDate('period_end', $endDate)
                    ->first();

                if ($existingTest) {
                    return response()->json([
                        'success' => false,
                        'message' => 'A test invoice already exists for this period. Please use a different date range.'
                    ], 422);
                }

                $invoice = $this->invoiceService->generateTestInvoice($vendor, $startDate, $endDate);
            } else {
                $invoice = $this->invoiceService->generateTestInvoice($vendor);
            }

            return response()->json([
                'success' => true,
                'message' => 'Test invoice generated successfully',
                'invoice' => [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Test invoice generation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate test invoice. Please try again.'
            ], 500);
        }
    }

    /**
     * Get invoice statistics for dashboard
     */
    public function getInvoiceStats()
    {
        try {
            $vendor = Auth::user();

            $stats = [
                'pending_count' => VendorInvoice::where('vendor_id', $vendor->id)
                    ->where('status', 'pending')->count(),
                'pending_amount' => VendorInvoice::where('vendor_id', $vendor->id)
                    ->where('status', 'pending')->sum('total_amount'),
                'paid_count' => VendorInvoice::where('vendor_id', $vendor->id)
                    ->where('status', 'paid')->count(),
                'paid_amount' => VendorInvoice::where('vendor_id', $vendor->id)
                    ->where('status', 'paid')->sum('total_amount'),
                'overdue_count' => VendorInvoice::where('vendor_id', $vendor->id)
                    ->where('status', 'overdue')->count(),
                'overdue_amount' => VendorInvoice::where('vendor_id', $vendor->id)
                    ->where('status', 'overdue')->sum('total_amount'),
                'last_invoice_date' => VendorInvoice::where('vendor_id', $vendor->id)
                    ->latest()->value('invoice_date'),
                'next_due_date' => VendorInvoice::where('vendor_id', $vendor->id)
                    ->where('status', 'pending')
                    ->orderBy('due_date')
                    ->value('due_date')
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching invoice stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load invoice statistics'
            ], 500);
        }
    }

    /**
     * Automated invoice generation command (to be run via scheduler)
     */
    public function generateBiWeeklyInvoices()
    {
        Log::warning('generateBiWeeklyInvoices called via HTTP - should use command line');

        $results = $this->invoiceService->generateAllInvoices();

        return response()->json([
            'success' => true,
            'message' => 'Invoice generation completed',
            'results' => $results
        ]);
    }
}
