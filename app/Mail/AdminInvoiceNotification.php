<?php
// app/Mail/AdminInvoiceNotification.php

namespace App\Mail;

use App\Models\VendorInvoice;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

class AdminInvoiceNotification extends Mailable
{
    use Queueable, SerializesModels;

    public VendorInvoice $invoice;
    public User $vendor;

    /**
     * Create a new message instance.
     */
    public function __construct(VendorInvoice $invoice, User $vendor)
    {
        $this->invoice = $invoice;
        $this->vendor = $vendor;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->invoice->isOverdue()
            ? "OVERDUE INVOICE #{$this->invoice->invoice_number} from {$this->vendor->name}"
            : " Invoice #{$this->invoice->invoice_number} from {$this->vendor->name}";

        return new Envelope(
            subject: $subject,
            tags: ['admin', 'invoice', $this->invoice->status],
            metadata: [
                'invoice_id' => $this->invoice->id,
                'vendor_id' => $this->vendor->id,
                'amount' => $this->invoice->total_amount,
                'status' => $this->invoice->status,
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $period = $this->invoice->period_start->format('F j, Y') . ' to ' . $this->invoice->period_end->format('F j, Y');
        $dueDate = $this->invoice->due_date->format('F j, Y');
        $daysUntilDue = now()->diffInDays($this->invoice->due_date, false);
        $status = $this->invoice->status;
        $isOverdue = $this->invoice->isOverdue();

        // Calculate totals
        $totalScans = $this->invoice->total_scans;
        $totalAmount = $this->invoice->total_amount;
        $ratePerMeal = 65.00;

        // Get vendor contact info
        $vendorPhone = $this->vendor->profile->phone_number ?? 'Not provided';
        $vendorEmail = $this->vendor->email;
        $businessName = $this->vendor->profile->business_name ?? null;

        return new Content(
            view: 'emails.admin-invoice-notification',
            with: [
                // Invoice Details
                'invoice' => $this->invoice,
                'invoice_number' => $this->invoice->invoice_number,
                'period' => $period,
                'dueDate' => $dueDate,
                'daysUntilDue' => round($daysUntilDue),
                'status' => $status,
                'isOverdue' => $isOverdue,

                // Financial Details
                'totalScans' => $totalScans,
                'totalAmount' => $totalAmount,
                'formattedTotal' => 'Ksh ' . number_format($totalAmount, 2),
                'ratePerMeal' => 'Ksh ' . number_format($ratePerMeal, 2),

                // Vendor Information
                'vendor' => $this->vendor,
                'vendorName' => $this->vendor->name,
                'vendorPhone' => $vendorPhone,
                'vendorEmail' => $vendorEmail,
                'businessName' => $businessName,

                // Cycle Information
                'cycleNumber' => $this->invoice->cycle_number ?? '1',
                'periodName' => $this->invoice->period_name ?? $period,

                // Bank Details
                'bankDetails' => $this->invoice->vendor_bank_details,

                // Items for daily breakdown
                'items' => $this->invoice->items,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        // Generate PDF invoice and attach
        $pdf = Pdf::loadView('reeds.vendor.invoice-pdf', ['invoice' => $this->invoice]);

        return [
            Attachment::fromData(
                fn () => $pdf->output(),
                "invoice-{$this->invoice->invoice_number}.pdf"
            )->withMime('application/pdf'),
        ];
    }
}
