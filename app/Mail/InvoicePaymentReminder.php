<?php
// app/Mail/InvoicePaymentReminder.php

namespace App\Mail;

use App\Models\VendorInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoicePaymentReminder extends Mailable
{
    use Queueable, SerializesModels;

    public VendorInvoice $invoice;
    public int $daysRemaining;
    public bool $isOverdue;

    /**
     * Create a new message instance.
     */
    public function __construct(VendorInvoice $invoice, int $daysRemaining, bool $isOverdue = false)
    {
        $this->invoice = $invoice;
        $this->daysRemaining = $daysRemaining;
        $this->isOverdue = $isOverdue;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->isOverdue
            ? "URGENT: Invoice #{$this->invoice->invoice_number} is OVERDUE"
            : "Reminder: Invoice #{$this->invoice->invoice_number} due in {$this->daysRemaining} days";

        return new Envelope(
            subject: $subject,
            tags: ['reminder', 'invoice', $this->isOverdue ? 'overdue' : 'upcoming'],
            metadata: [
                'invoice_id' => $this->invoice->id,
                'due_date' => $this->invoice->due_date->format('Y-m-d'),
                'days_remaining' => $this->daysRemaining,
                'is_overdue' => $this->isOverdue,
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-payment-reminder',
            with: [
                'invoice' => $this->invoice,
                'vendor' => $this->invoice->vendor,
                'daysRemaining' => $this->daysRemaining,
                'isOverdue' => $this->isOverdue,
                'dueDate' => $this->invoice->due_date->format('F j, Y'),
                'totalAmount' => 'Ksh ' . number_format($this->invoice->total_amount, 2),
                'invoiceNumber' => $this->invoice->invoice_number,
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
