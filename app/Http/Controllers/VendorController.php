<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\MealTransaction;
use App\Models\VendorInvoice;
use App\Models\Reward;
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

    public function index()
    {
        try {
            $vendor = Auth::user();
            $today = now()->format('Y-m-d');

            $todayTransactions = MealTransaction::where('vendor_id', $vendor->id)
                ->whereDate('meal_date', $today)
                ->get();

            $todayStats = (object)[
                'total_meals' => $todayTransactions->count(),
                'total_amount' => $todayTransactions->sum(function($meal) {
                    $scanData = $meal->scan_data;
                    $isReward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
                    return $isReward ? 200.00 : 65.00;
                })
            ];

            $totalTransactions = MealTransaction::where('vendor_id', $vendor->id)->get();

            $totalStats = (object)[
                'total_meals' => $totalTransactions->count(),
                'total_amount' => $totalTransactions->sum(function($meal) {
                    $scanData = $meal->scan_data;
                    $isReward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
                    return $isReward ? 200.00 : 65.00;
                })
            ];

            $recentTransactions = MealTransaction::with('employee.department')
                ->where('vendor_id', $vendor->id)
                ->whereDate('meal_date', $today)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get()
                ->map(function($transaction) {
                    $scanData = $transaction->scan_data;
                    $transaction->is_reward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
                    return $transaction;
                });

            return view('reeds.vendor.index', compact('todayStats', 'totalStats', 'recentTransactions'));

        } catch (\Exception $e) {
            Log::error('Error loading vendor dashboard: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to load dashboard. Please try again.');
        }
    }

    public function scan()
    {
        try {
            $vendor = Auth::user();

            if (!$vendor->unit_id) {
                return redirect()->route('vendor.dashboard')
                    ->with('error', 'You are not assigned to any unit. Please contact administrator.');
            }

            $recentScans = MealTransaction::with(['employee.department', 'employee.unit'])
                ->where('vendor_id', $vendor->id)
                ->whereDate('meal_date', today())
                ->latest()
                ->take(10)
                ->get()
                ->map(function($transaction) {
                    $scanData = $transaction->scan_data;
                    $transaction->is_reward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
                    return $transaction;
                });

            return view('reeds.vendor.scan', compact('vendor', 'recentScans'));

        } catch (\Exception $e) {
            Log::error('Error loading scan page: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to load scan page.');
        }
    }

    /**
     * Process QR scan - FIXED VERSION with reward handling and 65 KES regular meals
     */
    public function processScan(Request $request)
    {
        Log::info('QR Scan Request Received', [
            'vendor_id' => Auth::id(),
            'qr_code_length' => strlen($request->qr_code),
            'qr_code_preview' => substr($request->qr_code, 0, 100)
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
            $employee = null;

            // Try direct employee code match
            $employee = Employee::where('employee_code', $qrCode)
                ->where('is_active', true)
                ->with(['department', 'unit'])
                ->first();

            // Try QR code match
            if (!$employee) {
                $employee = Employee::where('qr_code', $qrCode)
                    ->where('is_active', true)
                    ->with(['department', 'unit'])
                    ->first();
            }

            // Try text extraction from QR
            if (!$employee && strpos($qrCode, 'REEDS AFRICA CONSULT') !== false) {
                $employeeCode = $this->extractEmployeeCodeFromText($qrCode);
                if ($employeeCode) {
                    $employee = Employee::where('employee_code', $employeeCode)
                        ->where('is_active', true)
                        ->with(['department', 'unit'])
                        ->first();
                }
            }

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found. Please ensure you\'re scanning a valid employee QR code.',
                    'error_type' => 'employee_not_found'
                ], 200);
            }

            // Check for reward
            $reward = Reward::where('employee_id', $employee->id)
                ->whereDate('reward_date', today())
                ->where('status', 'pending')
                ->first();

            $hasReward = $reward && !$reward->is_expired;

            $canFeed = $employee->canBeFedNow();
            if (!$canFeed['can_be_fed']) {
                return response()->json([
                    'success' => false,
                    'message' => $canFeed['reason'],
                    'error_type' => 'feeding_hours'
                ], 200);
            }

            $existingMeal = $employee->getTodayMealTransaction();
            if ($existingMeal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee has already been fed today at ' . $existingMeal->meal_time,
                    'error_type' => 'duplicate_meal'
                ], 200);
            }

            // Create transaction based on reward or regular
            if ($hasReward) {
                // REWARD MEAL - 200 KES
                $transaction = MealTransaction::create([
                    'vendor_id' => $vendor->id,
                    'employee_id' => $employee->id,
                    'amount' => 200.00,
                    'meal_date' => today(),
                    'meal_time' => now()->format('H:i:s'),
                    'qr_code_scanned' => $qrCode,
                    'scan_data' => json_encode([
                        'is_reward' => true,
                        'reward_id' => $reward->id,
                        'reward_amount' => 200.00,
                        'regular_amount' => 65.00,
                        'scanned_at' => now()->toDateTimeString(),
                        'employee_name' => $employee->formal_name,
                        'employee_code' => $employee->employee_code,
                        'department' => $employee->department->name ?? 'N/A',
                        'unit_name' => $employee->unit->name ?? 'N/A',
                        'unit_id' => $employee->unit_id,
                        'department_id' => $employee->department_id,
                        'reward_reason' => $reward->reason,
                        'reward_date' => $reward->reward_date->format('Y-m-d'),
                        'vendor_name' => $vendor->name,
                        'vendor_id' => $vendor->id
                    ])
                ]);

                $reward->markAsClaimed($transaction->id);
                $message = '🎖️ REWARD: Employee awarded 200 KES! Meal recorded successfully.';
                $amount = 200.00;
                $isReward = true;

            } else {
                // REGULAR MEAL - 65 KES
                $transaction = MealTransaction::create([
                    'vendor_id' => $vendor->id,
                    'employee_id' => $employee->id,
                    'amount' => 65.00,
                    'meal_date' => today(),
                    'meal_time' => now()->format('H:i:s'),
                    'qr_code_scanned' => $qrCode,
                    'scan_data' => json_encode([
                        'is_reward' => false,
                        'scanned_at' => now()->toDateTimeString(),
                        'employee_name' => $employee->formal_name,
                        'employee_code' => $employee->employee_code,
                        'department' => $employee->department->name ?? 'N/A',
                        'unit_name' => $employee->unit->name ?? 'N/A',
                        'unit_id' => $employee->unit_id,
                        'department_id' => $employee->department_id,
                        'vendor_name' => $vendor->name,
                        'vendor_id' => $vendor->id,
                        'amount' => 65.00
                    ])
                ]);
                $message = 'Meal recorded successfully (65 KES).';
                $amount = 65.00;
                $isReward = false;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
                'is_reward' => $isReward,
                'amount' => $amount,
                'transaction' => [
                    'code' => $transaction->transaction_code,
                    'employee_name' => $employee->formal_name,
                    'employee_code' => $employee->employee_code,
                    'department' => $employee->department->name ?? 'N/A',
                    'amount' => $amount,
                    'time' => $transaction->meal_time,
                    'date' => $transaction->meal_date,
                    'is_reward' => $isReward
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('QR Scan Processing Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'A system error occurred while processing the scan. Please try again.',
                'error_type' => 'system_error',
                'debug_message' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    private function extractEmployeeCodeFromText(string $qrText): ?string
    {
        if (preg_match('/Employee No:\s*([A-Za-z0-9]+)/i', $qrText, $matches)) {
            return trim($matches[1]);
        }

        $lines = explode("\n", $qrText);
        for ($i = 0; $i < count($lines) - 1; $i++) {
            if (strpos($lines[$i], 'Employee No:') !== false && isset($lines[$i + 1])) {
                $potentialCode = trim($lines[$i + 1]);
                if (preg_match('/^[A-Za-z0-9]{3,20}$/', $potentialCode)) {
                    return $potentialCode;
                }
            }
        }

        if (preg_match('/Employee No[^A-Za-z0-9]*([A-Za-z0-9]{3,20})/i', $qrText, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    public function getScanHistory(Request $request)
    {
        try {
            $vendor = Auth::user();
            $date = $request->get('date', today()->format('Y-m-d'));

            $query = MealTransaction::where('vendor_id', $vendor->id)
                ->with(['employee.department', 'employee.unit']);

            if ($date && $date !== 'all') {
                $query->whereDate('meal_date', $date);
            }

            $perPage = $request->get('per_page', 50);
            $transactions = $query->orderBy('created_at', 'desc')->paginate($perPage);

            $formattedTransactions = $transactions->map(function ($transaction) {
                $employee = $transaction->employee;
                $scanData = $transaction->scan_data;
                $isReward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
                $effectiveAmount = $isReward ? 200.00 : 65.00;

                return [
                    'id' => $transaction->id,
                    'transaction_code' => $transaction->transaction_code ?? 'N/A',
                    'employee' => [
                        'formal_name' => $employee->formal_name ?? 'N/A',
                        'employee_code' => $employee->employee_code ?? 'N/A',
                        'department' => ['name' => $employee->department->name ?? 'N/A'],
                        'unit' => ['name' => $employee->unit->name ?? 'N/A']
                    ],
                    'amount' => floatval($effectiveAmount),
                    'meal_time' => $transaction->meal_time ?? 'N/A',
                    'meal_date' => $transaction->meal_date ? $transaction->meal_date->format('Y-m-d') : 'N/A',
                    'is_reward' => $isReward,
                    'created_at' => $transaction->created_at ? $transaction->created_at->format('H:i:s') : 'N/A',
                ];
            });

            $totalScans = $formattedTransactions->count();
            $totalRevenue = $formattedTransactions->sum('amount');

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
            Log::error('Error in getScanHistory: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load history. Please try again.'
            ], 500);
        }
    }

    public function getDashboardStats()
    {
        try {
            $vendor = Auth::user();
            $today = now()->format('Y-m-d');

            $todayTransactions = MealTransaction::where('vendor_id', $vendor->id)
                ->whereDate('meal_date', $today)
                ->get();

            $totalTransactions = MealTransaction::where('vendor_id', $vendor->id)->get();

            return response()->json([
                'success' => true,
                'today' => [
                    'scans' => $todayTransactions->count(),
                    'revenue' => $todayTransactions->sum(function($meal) {
                        $scanData = $meal->scan_data;
                        $isReward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
                        return $isReward ? 200.00 : 65.00;
                    })
                ],
                'total' => [
                    'scans' => $totalTransactions->count(),
                    'revenue' => $totalTransactions->sum(function($meal) {
                        $scanData = $meal->scan_data;
                        $isReward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
                        return $isReward ? 200.00 : 65.00;
                    })
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

    public function history()
    {
        return view('reeds.vendor.history');
    }

    public function performance()
    {
        return view('reeds.vendor.performance');
    }

    public function invoices()
    {
        return view('reeds.vendor.invoices');
    }

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

    public function invoicesData(Request $request)
    {
        try {
            $vendor = Auth::user();
            $query = VendorInvoice::with('vendor.profile')->withCount('items')
                ->where('vendor_id', $vendor->id)->orderBy('created_at', 'desc');

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
                'pending_invoices' => VendorInvoice::where('vendor_id', $vendor->id)->where('status', 'pending')->count(),
                'paid_invoices' => VendorInvoice::where('vendor_id', $vendor->id)->where('status', 'paid')->count(),
                'overdue_invoices' => VendorInvoice::where('vendor_id', $vendor->id)->where('status', 'overdue')->count(),
                'total_revenue' => VendorInvoice::where('vendor_id', $vendor->id)->where('status', 'paid')->sum('total_amount'),
                'total_pending_amount' => VendorInvoice::where('vendor_id', $vendor->id)->where('status', 'pending')->sum('total_amount'),
                'total_overdue_amount' => VendorInvoice::where('vendor_id', $vendor->id)->where('status', 'overdue')->sum('total_amount')
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
            Log::error('Error loading invoices: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load invoices. Please try again.'
            ], 500);
        }
    }

    public function getInvoiceDetails($id)
    {
        try {
            $vendor = Auth::user();
            $invoice = VendorInvoice::with(['items', 'vendor.profile'])
                ->where('id', $id)->where('vendor_id', $vendor->id)->firstOrFail();

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

        } catch (\Exception $e) {
            Log::error('Error loading invoice details: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load invoice details.'
            ], 500);
        }
    }

    public function viewInvoice($id)
    {
        try {
            $vendor = Auth::user();
            $invoice = VendorInvoice::with(['items', 'vendor.profile'])
                ->where('id', $id)->where('vendor_id', $vendor->id)->firstOrFail();
            return view('reeds.vendor.invoice-view', compact('invoice'));
        } catch (\Exception $e) {
            return redirect()->route('vendor.invoices')->with('error', 'Invoice not found.');
        }
    }

    public function downloadInvoice($id)
    {
        try {
            $vendor = Auth::user();
            $invoice = VendorInvoice::with(['items', 'vendor.profile'])
                ->where('id', $id)->where('vendor_id', $vendor->id)->firstOrFail();
            $pdf = Pdf::loadView('reeds.vendor.invoice-pdf', compact('invoice'));
            return $pdf->download('invoice-' . $invoice->invoice_number . '.pdf');
        } catch (\Exception $e) {
            return redirect()->route('vendor.invoices')->with('error', 'Failed to generate PDF.');
        }
    }

    public function generateTestInvoice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'period' => 'required|in:current,previous,custom,first_period',
            'start_date' => 'required_if:period,custom|date|nullable',
            'end_date' => 'required_if:period,custom|date|after_or_equal:start_date|nullable'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed'], 422);
        }

        try {
            $vendor = Auth::user();
            if ($request->period === 'first_period') {
                $invoice = $this->invoiceService->generateFirstPeriodTestInvoice($vendor);
                return response()->json(['success' => true, 'message' => 'Test invoice generated', 'invoice' => ['id' => $invoice->id, 'invoice_number' => $invoice->invoice_number]]);
            }
            if ($request->period === 'custom') {
                $startDate = Carbon::parse($request->start_date);
                $endDate = Carbon::parse($request->end_date);
                $invoice = $this->invoiceService->generateTestInvoice($vendor, $startDate, $endDate);
            } else {
                $invoice = $this->invoiceService->generateTestInvoice($vendor);
            }
            return response()->json(['success' => true, 'message' => 'Test invoice generated', 'invoice' => ['id' => $invoice->id, 'invoice_number' => $invoice->invoice_number]]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to generate test invoice.'], 500);
        }
    }

    public function getInvoiceStats()
    {
        try {
            $vendor = Auth::user();
            $stats = [
                'pending_count' => VendorInvoice::where('vendor_id', $vendor->id)->where('status', 'pending')->count(),
                'pending_amount' => VendorInvoice::where('vendor_id', $vendor->id)->where('status', 'pending')->sum('total_amount'),
                'paid_count' => VendorInvoice::where('vendor_id', $vendor->id)->where('status', 'paid')->count(),
                'paid_amount' => VendorInvoice::where('vendor_id', $vendor->id)->where('status', 'paid')->sum('total_amount'),
                'overdue_count' => VendorInvoice::where('vendor_id', $vendor->id)->where('status', 'overdue')->count(),
                'overdue_amount' => VendorInvoice::where('vendor_id', $vendor->id)->where('status', 'overdue')->sum('total_amount'),
                'last_invoice_date' => VendorInvoice::where('vendor_id', $vendor->id)->latest()->value('invoice_date'),
                'next_due_date' => VendorInvoice::where('vendor_id', $vendor->id)->where('status', 'pending')->orderBy('due_date')->value('due_date')
            ];
            return response()->json(['success' => true, 'stats' => $stats]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to load statistics.'], 500);
        }
    }

    public function generateBiWeeklyInvoices()
    {
        Log::warning('generateBiWeeklyInvoices called via HTTP - should use command line');
        $results = $this->invoiceService->generateAllInvoices();
        return response()->json(['success' => true, 'message' => 'Invoice generation completed', 'results' => $results]);
    }
}
