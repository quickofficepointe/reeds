<?php
// app/Mail/OverdueInvoiceNotification.php

namespace App\Mail;

use App\Models\VendorInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OverdueInvoiceNotification extends Mailable
{
    use Queueable, SerializesModels;

    public VendorInvoice $invoice;
    public int $daysOverdue;

    /**
     * Create a new message instance.
     */
    public function __construct(VendorInvoice $invoice)
    {
        $this->invoice = $invoice;
        $this->daysOverdue = now()->diffInDays($invoice->due_date, false);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "OVERDUE INVOICE: #{$this->invoice->invoice_number} - {$this->daysOverdue} days overdue",
            tags: ['admin', 'overdue', 'urgent'],
            metadata: [
                'invoice_id' => $this->invoice->id,
                'vendor_id' => $this->invoice->vendor_id,
                'days_overdue' => $this->daysOverdue,
                'amount' => $this->invoice->total_amount,
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.overdue-invoice-notification',
            with: [
                'invoice' => $this->invoice,
                'vendor' => $this->invoice->vendor,
                'daysOverdue' => $this->daysOverdue,
                'dueDate' => $this->invoice->due_date->format('F j, Y'),
                'totalAmount' => 'Ksh ' . number_format($this->invoice->total_amount, 2),
                'invoiceNumber' => $this->invoice->invoice_number,
                'vendorName' => $this->invoice->vendor->name,
                'vendorPhone' => $this->invoice->vendor->profile->phone_number ?? 'Not provided',
                'vendorEmail' => $this->invoice->vendor->email,
                'period' => $this->invoice->period_start->format('M d, Y') . ' - ' . $this->invoice->period_end->format('M d, Y'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
