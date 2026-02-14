<?php
// app/Services/InvoiceGenerationService.php

namespace App\Services;

use App\Models\User;
use App\Models\VendorInvoice;
use App\Models\VendorInvoiceItem;
use App\Models\MealTransaction;
use App\Mail\VendorInvoiceGenerated;
use App\Mail\InvoicePaymentReminder;
use App\Mail\AdminInvoiceNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class InvoiceGenerationService
{
    protected $mealRate = 65.00; // Ksh 65 per meal
    protected $periodService;
    protected $adminEmails = [
        'isaacnmuteru@gmail.com',
        'info@driftplus.co.ke',
        'info@vibeeplug.com',
        'info@quickofficepointe.co.ke'
    ];

    public function __construct(InvoicePeriodService $periodService)
    {
        $this->periodService = $periodService;
    }

    /**
     * Generate invoices for all vendors
     */
    public function generateAllInvoices($force = false)
    {
        Log::info('Starting invoice generation for all vendors', ['force' => $force]);

        $vendors = User::where('role', 'vendor')
            ->where('is_active', true)
            ->whereHas('profile', function($q) {
                $q->where('is_verified', true);
            })
            ->get();

        $results = [
            'total' => $vendors->count(),
            'generated' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        foreach ($vendors as $vendor) {
            try {
                $invoice = $this->generateVendorInvoice($vendor, $force);
                if ($invoice) {
                    $results['generated']++;
                } else {
                    $results['skipped']++;
                }
            } catch (\Exception $e) {
                $results['errors'][] = "Vendor {$vendor->id}: " . $e->getMessage();
                Log::error("Failed to generate invoice for vendor {$vendor->id}: " . $e->getMessage());
            }
        }

        // Send summary email to admins
        $this->sendGenerationSummaryToAdmins($results);

        Log::info('Invoice generation completed', $results);

        return $results;
    }

    /**
     * Generate invoice for a specific vendor
     */
    public function generateVendorInvoice(User $vendor, $force = false)
    {
        // Get current period
        $period = $this->periodService->getCurrentPeriod();

        return DB::transaction(function() use ($vendor, $period, $force) {
            // Check if invoice already exists
            $existingInvoice = VendorInvoice::where('vendor_id', $vendor->id)
                ->where('period_start', $period['start']->format('Y-m-d'))
                ->where('period_end', $period['end']->format('Y-m-d'))
                ->lockForUpdate()
                ->first();

            if ($existingInvoice && !$force) {
                Log::info("Invoice already exists for vendor {$vendor->id}", [
                    'period' => $period,
                    'invoice' => $existingInvoice->invoice_number
                ]);
                return null;
            }

            // Get transactions for the period
            $transactions = MealTransaction::where('vendor_id', $vendor->id)
                ->whereBetween('meal_date', [$period['start'], $period['end']])
                ->orderBy('meal_date')
                ->lockForUpdate()
                ->get();

            if ($transactions->isEmpty()) {
                Log::info("No transactions found for vendor {$vendor->id} in period", [
                    'period' => $period
                ]);
                return null;
            }

            // Calculate totals using the meal rate
            $totalScans = $transactions->count();
            $totalAmount = $totalScans * $this->mealRate;

            // Get next sequence number
            $sequence = $this->getNextSequenceNumber($vendor, $period['end']);
            $invoiceNumber = $this->periodService->generateInvoiceNumber(
                $vendor->id,
                $period['end'],
                $sequence
            );

            // Create the invoice
            $invoice = VendorInvoice::create([
                'vendor_id' => $vendor->id,
                'invoice_number' => $invoiceNumber,
                'period_start' => $period['start']->format('Y-m-d'),
                'period_end' => $period['end']->format('Y-m-d'),
                'invoice_date' => Carbon::now()->format('Y-m-d'),
                'due_date' => Carbon::now()->addDays(30)->format('Y-m-d'),
                'total_scans' => $totalScans,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'is_test' => false,
                'cycle_number' => $period['cycle_number'],
                'period_name' => $period['period_name'],
                'notes' => "Payment for meals provided during period: {$period['period_name']} (Cycle {$period['cycle_number']})"
            ]);

            // Create invoice items (group by day)
            $this->createInvoiceItems($invoice, $transactions);

            // Send all invoice emails
            $this->sendInvoiceEmails($invoice, $vendor);

            Log::info("Invoice generated successfully", [
                'invoice' => $invoiceNumber,
                'vendor' => $vendor->id,
                'scans' => $totalScans,
                'amount' => $totalAmount,
                'cycle' => $period['cycle_number'],
                'period' => $period['period_name']
            ]);

            return $invoice;
        });
    }

    /**
     * Send all invoice-related emails
     */
    protected function sendInvoiceEmails(VendorInvoice $invoice, User $vendor)
    {
        // 1. Send to admins (employer)
        $this->sendToAdmins($invoice, $vendor);

        // 2. Send to vendor (acknowledgement)
        $this->sendToVendor($invoice, $vendor);

        // 3. Schedule payment reminders (will be handled by cron)
        $this->scheduleReminders($invoice);
    }

    /**
     * Send invoice to admin emails
     */
    protected function sendToAdmins(VendorInvoice $invoice, User $vendor)
    {
        foreach ($this->adminEmails as $adminEmail) {
            try {
                Mail::to($adminEmail)->queue(new AdminInvoiceNotification($invoice, $vendor));
                Log::info("Admin invoice email queued", [
                    'admin' => $adminEmail,
                    'invoice' => $invoice->invoice_number
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to queue admin invoice email to {$adminEmail}: " . $e->getMessage());
            }
        }
    }

    /**
     * Send invoice to vendor
     */
    protected function sendToVendor(VendorInvoice $invoice, User $vendor)
    {
        try {
            Mail::to($vendor->email)->queue(new VendorInvoiceGenerated($invoice, $vendor));
            Log::info("Vendor invoice email queued", [
                'vendor' => $vendor->email,
                'invoice' => $invoice->invoice_number
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to queue vendor invoice email: " . $e->getMessage());
        }
    }

    /**
     * Schedule payment reminders
     */
    protected function scheduleReminders(VendorInvoice $invoice)
    {
        // Store reminder schedule in database or cache
        $reminderDays = [7, 3, 1]; // Send reminders 7, 3, and 1 day before due date

        foreach ($reminderDays as $days) {
            $reminderDate = $invoice->due_date->copy()->subDays($days);

            // You could store these in a reminders table or use Laravel's task scheduling
            Log::info("Payment reminder scheduled", [
                'invoice' => $invoice->invoice_number,
                'reminder_date' => $reminderDate->format('Y-m-d'),
                'days_before' => $days
            ]);
        }
    }

    /**
     * Send generation summary to admins
     */
    protected function sendGenerationSummaryToAdmins(array $results)
    {
        $summary = [
            'generated' => $results['generated'],
            'skipped' => $results['skipped'],
            'errors' => $results['errors'],
            'date' => Carbon::now()->format('Y-m-d H:i:s')
        ];

        foreach ($this->adminEmails as $adminEmail) {
            try {
                // You can create a dedicated Mailable for summaries
                Mail::to($adminEmail)->send(new \App\Mail\InvoiceGenerationSummary($summary));
            } catch (\Exception $e) {
                Log::error("Failed to send summary to {$adminEmail}: " . $e->getMessage());
            }
        }
    }

    /**
     * Send payment reminders for due invoices
     */
    public function sendPaymentReminders()
    {
        Log::info('Starting payment reminder process');

        $today = Carbon::now();
        $reminderDays = [7, 3, 1];

        foreach ($reminderDays as $days) {
            $targetDate = $today->copy()->addDays($days);

            $invoices = VendorInvoice::where('status', 'pending')
                ->whereDate('due_date', $targetDate)
                ->with('vendor.profile')
                ->get();

            foreach ($invoices as $invoice) {
                try {
                    Mail::to($invoice->vendor->email)->queue(
                        new InvoicePaymentReminder($invoice, $days)
                    );

                    Log::info("Payment reminder sent", [
                        'invoice' => $invoice->invoice_number,
                        'vendor' => $invoice->vendor->email,
                        'days_before_due' => $days
                    ]);
                } catch (\Exception $e) {
                    Log::error("Failed to send reminder for invoice {$invoice->invoice_number}: " . $e->getMessage());
                }
            }
        }

        // Handle overdue invoices
        $overdueInvoices = VendorInvoice::where('status', 'pending')
            ->where('due_date', '<', $today)
            ->with('vendor.profile')
            ->get();

        foreach ($overdueInvoices as $invoice) {
            try {
                // Mark as overdue
                $invoice->status = 'overdue';
                $invoice->save();

                // Send overdue notice
                Mail::to($invoice->vendor->email)->queue(
                    new InvoicePaymentReminder($invoice, 0, true)
                );

                // Also notify admins
                foreach ($this->adminEmails as $adminEmail) {
                    Mail::to($adminEmail)->queue(
                        new \App\Mail\OverdueInvoiceNotification($invoice)
                    );
                }

                Log::info("Overdue notice sent", [
                    'invoice' => $invoice->invoice_number,
                    'vendor' => $invoice->vendor->email
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to process overdue invoice {$invoice->invoice_number}: " . $e->getMessage());
            }
        }
    }

    /**
     * Get next sequence number for invoice
     */
    protected function getNextSequenceNumber(User $vendor, Carbon $periodEnd): int
    {
        $year = $periodEnd->format('Y');

        $latestInvoice = VendorInvoice::where('vendor_id', $vendor->id)
            ->whereYear('created_at', $year)
            ->lockForUpdate()
            ->latest('id')
            ->first();

        if ($latestInvoice) {
            // Try to extract sequence from invoice number
            preg_match('/-(\d+)$/', $latestInvoice->invoice_number, $matches);
            if (isset($matches[1])) {
                return (int) $matches[1] + 1;
            }
        }

        return 1;
    }

    /**
     * Create invoice items grouped by day
     */
    protected function createInvoiceItems(VendorInvoice $invoice, $transactions)
    {
        $grouped = $transactions->groupBy(function($transaction) {
            return $transaction->meal_date->format('Y-m-d');
        });

        foreach ($grouped as $date => $dayTransactions) {
            $scans = $dayTransactions->count();
            VendorInvoiceItem::create([
                'invoice_id' => $invoice->id,
                'date' => $date,
                'description' => 'Meal transactions for ' . Carbon::parse($date)->format('l, F j, Y'),
                'scans' => $scans,
                'rate' => $this->mealRate, // 65.00
                'amount' => $scans * $this->mealRate
            ]);
        }
    }

    /**
     * Generate test invoice for first period (Feb 2-14, 2026)
     */
    public function generateFirstPeriodTestInvoice(User $vendor)
    {
        // First period: Feb 2-14, 2026
        $startDate = Carbon::create(2026, 2, 2);
        $endDate = Carbon::create(2026, 2, 14);

        return DB::transaction(function() use ($vendor, $startDate, $endDate) {
            // Get all transactions for the full 2-week period
            $transactions = MealTransaction::where('vendor_id', $vendor->id)
                ->whereBetween('meal_date', [$startDate, $endDate])
                ->orderBy('meal_date')
                ->get();

            $totalScans = $transactions->count();
            $totalAmount = $totalScans * $this->mealRate; // 65 * scans

            $invoice = VendorInvoice::create([
                'vendor_id' => $vendor->id,
                'invoice_number' => 'TEST-' . Carbon::now()->format('Ymd-His') . '-' . uniqid(),
                'period_start' => $startDate->format('Y-m-d'),
                'period_end' => $endDate->format('Y-m-d'),
                'invoice_date' => Carbon::now()->format('Y-m-d'),
                'due_date' => Carbon::now()->addDays(30)->format('Y-m-d'),
                'total_scans' => $totalScans,
                'total_amount' => $totalAmount,
                'status' => 'draft',
                'is_test' => true,
                'cycle_number' => 1,
                'period_name' => 'Feb 2-14, 2026',
                'notes' => 'TEST INVOICE - First Period (Feb 2-14, 2026)'
            ]);

            // Create invoice items grouped by day
            $grouped = $transactions->groupBy(function($transaction) {
                return $transaction->meal_date->format('Y-m-d');
            });

            foreach ($grouped as $date => $dayTransactions) {
                $scans = $dayTransactions->count();
                VendorInvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'date' => $date,
                    'description' => 'Meal transactions for ' . Carbon::parse($date)->format('l, F j, Y'),
                    'scans' => $scans,
                    'rate' => $this->mealRate, // 65.00
                    'amount' => $scans * $this->mealRate
                ]);
            }

            // Send test invoice emails (optional)
            if (!app()->environment('testing')) {
                $this->sendInvoiceEmails($invoice, $vendor);
            }

            return $invoice;
        });
    }

    /**
     * Generate test invoice for any period
     */
    public function generateTestInvoice(User $vendor, $startDate = null, $endDate = null)
    {
        if (!$startDate) {
            $period = $this->periodService->getCurrentPeriod();
            $startDate = $period['start'];
            $endDate = $period['end'];
        }

        return DB::transaction(function() use ($vendor, $startDate, $endDate) {
            $transactions = MealTransaction::where('vendor_id', $vendor->id)
                ->whereBetween('meal_date', [$startDate, $endDate])
                ->orderBy('meal_date')
                ->lockForUpdate()
                ->get();

            $totalScans = $transactions->count();
            $totalAmount = $totalScans * $this->mealRate;

            $period = $this->periodService->calculatePeriodForDate($startDate);

            $invoice = VendorInvoice::create([
                'vendor_id' => $vendor->id,
                'invoice_number' => 'TEST-' . Carbon::now()->format('Ymd-His') . '-' . uniqid(),
                'period_start' => $startDate->format('Y-m-d'),
                'period_end' => $endDate->format('Y-m-d'),
                'invoice_date' => Carbon::now()->format('Y-m-d'),
                'due_date' => Carbon::now()->addDays(30)->format('Y-m-d'),
                'total_scans' => $totalScans,
                'total_amount' => $totalAmount,
                'status' => 'draft',
                'is_test' => true,
                'cycle_number' => $period['cycle_number'] ?? null,
                'period_name' => $period['period_name'] ?? null,
                'notes' => 'TEST INVOICE - NOT FOR PAYMENT'
            ]);

            $this->createInvoiceItems($invoice, $transactions);

            return $invoice;
        });
    }
}
