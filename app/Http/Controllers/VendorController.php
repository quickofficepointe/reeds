<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\MealTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
     * Process QR scan
     */
    public function processScan(Request $request)
    {
        $request->validate([
            'qr_code' => 'required|string'
        ]);

        $vendor = Auth::user();
        $qrCode = $request->qr_code;

        DB::beginTransaction();
        try {
            // Find employee by QR code
            $employee = Employee::where('qr_code', $qrCode)
                ->where('is_active', true)
                ->with('department')
                ->first();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid QR code or employee not active'
                ], 404);
            }

            // Check if employee can be fed
            $canFeed = $employee->canBeFedNow();
            if (!$canFeed['can_be_fed']) {
                return response()->json([
                    'success' => false,
                    'message' => $canFeed['reason']
                ], 400);
            }

            // Create meal transaction
            $transaction = $employee->recordMeal($vendor->id, $qrCode);

            DB::commit();

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
            \Log::error('QR Scan Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the scan. Please try again.'
            ], 500);
        }
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
            'transactions' => $transactions
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
