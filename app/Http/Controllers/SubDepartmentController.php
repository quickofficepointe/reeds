<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\MealTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        return view('reeds.vendor.scan');
    }

    /**
     * Process QR scan - IMPROVED VERSION
     */
   // In VendorController.php - processScan method
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

    // Updated validation
    $request->validate([
        'qr_code' => 'required|string|min:10|max:1000'
    ]);

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

        // METHOD 1: Try to extract employee code from text QR (PRIMARY METHOD)
        if (strpos($qrCode, 'REEDS AFRICA CONSULT') !== false) {
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

        // METHOD 2: Try direct QR code match (encoded version)
        if (!$employee) {
            $employee = Employee::where('qr_code', $qrCode)
                ->where('is_active', true)
                ->with('department')
                ->first();
            if ($employee) {
                $extractionMethod = 'direct_match';
            }
        }

        // METHOD 3: Try display text match
        if (!$employee) {
            $employee = Employee::where('qr_code_display', $qrCode)
                ->where('is_active', true)
                ->with('department')
                ->first();
            if ($employee) {
                $extractionMethod = 'display_match';
            }
        }

        if (!$employee) {
            Log::warning('No employee found for QR code', [
                'qr_code_length' => strlen($qrCode),
                'qr_code_type' => $this->detectQrCodeType($qrCode),
                'qr_code_preview' => substr($qrCode, 0, 30),
                'extracted_code' => $employeeCode ?? 'none'
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Employee not found. Please ensure you\'re scanning a valid employee QR code.'
            ], 404);
        }

        Log::info('Employee found', [
            'employee_id' => $employee->id,
            'employee_name' => $employee->formal_name,
            'employee_code' => $employee->employee_code,
            'method' => $extractionMethod
        ]);

        // Check if employee can be fed
        $canFeed = $employee->canBeFedNow();
        if (!$canFeed['can_be_fed']) {
            Log::warning('Employee cannot be fed', [
                'employee_id' => $employee->id,
                'reason' => $canFeed['reason']
            ]);
            return response()->json([
                'success' => false,
                'message' => $canFeed['reason']
            ], 400);
        }

        // Check for duplicate meal today
        $existingMeal = MealTransaction::where('employee_id', $employee->id)
            ->whereDate('meal_date', now()->format('Y-m-d'))
            ->first();

        if ($existingMeal) {
            Log::warning('Duplicate meal attempt', [
                'employee_id' => $employee->id,
                'existing_transaction' => $existingMeal->transaction_code
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Employee has already been fed today'
            ], 400);
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
            'exception' => $e
        ]);

        return response()->json([
            'success' => false,
            'message' => 'An error occurred while processing the scan. Please try again.'
        ], 500);
    }
}

/**
 * Extract employee code from text QR code - IMPROVED VERSION
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
    public function getScanHistory(Request $request)
    {
        $vendor = Auth::user();
        $date = $request->get('date', today()->format('Y-m-d'));

        $query = MealTransaction::with('employee.department')
            ->where('vendor_id', $vendor->id);

        if ($date !== 'all') {
            $query->whereDate('meal_date', $date);
        }

        $transactions = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'transactions' => $transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'transaction_code' => $transaction->transaction_code,
                    'employee' => [
                        'formal_name' => $transaction->employee->formal_name,
                        'employee_code' => $transaction->employee->employee_code,
                        'department' => [
                            'name' => $transaction->employee->department->name ?? 'N/A'
                        ]
                    ],
                    'amount' => $transaction->amount,
                    'meal_time' => $transaction->meal_time,
                    'meal_date' => $transaction->meal_date,
                    'created_at' => $transaction->created_at->format('H:i:s'),
                ];
            })
        ]);
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
}
