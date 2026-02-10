<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\VendorInvoice;
use App\Mail\InvoiceReminder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendInvoiceReminders extends Command
{
    protected $signature = 'invoices:send-reminders';
    protected $description = 'Send reminder emails for invoices due soon';

    public function handle()
    {
        $this->info('Checking for invoices due soon...');

        $dueSoon = VendorInvoice::where('status', 'pending')
            ->where('due_date', '<=', now()->addDays(7))
            ->where('due_date', '>', now())
            ->with('vendor')
            ->get();

        $this->info("Found {$dueSoon->count()} invoices due soon.");

        $sentCount = 0;
        $errors = [];

        foreach ($dueSoon as $invoice) {
            try {
                if ($invoice->vendor && $invoice->vendor->email) {
                    Mail::to($invoice->vendor->email)->send(new InvoiceReminder($invoice));
                    $sentCount++;
                    $this->info("Reminder sent for invoice {$invoice->invoice_number} to {$invoice->vendor->email}");
                    Log::info("Invoice reminder sent for {$invoice->invoice_number}");
                }
            } catch (\Exception $e) {
                $errorMsg = "Failed to send reminder for invoice {$invoice->invoice_number}: " . $e->getMessage();
                $errors[] = $errorMsg;
                $this->error($errorMsg);
                Log::error($errorMsg);
            }
        }

        $this->info("Sent {$sentCount} reminders.");

        if (!empty($errors)) {
            $this->error("Errors encountered:");
            foreach ($errors as $error) {
                $this->error("- $error");
            }
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
