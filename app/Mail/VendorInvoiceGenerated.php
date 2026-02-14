<?php
// app/Mail/VendorInvoiceGenerated.php

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

class VendorInvoiceGenerated extends Mailable
{
    use Queueable, SerializesModels;

    public VendorInvoice $invoice;
    public User $vendor;
    public bool $isVendorCopy;

    /**
     * Create a new message instance.
     */
    public function __construct(VendorInvoice $invoice, User $vendor, bool $isVendorCopy = true)
    {
        $this->invoice = $invoice;
        $this->vendor = $vendor;
        $this->isVendorCopy = $isVendorCopy;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->isVendorCopy
            ? "Your Invoice #{$this->invoice->invoice_number} has been generated"
            : "Invoice #{$this->invoice->invoice_number} from {$this->vendor->name}";

        return new Envelope(
            subject: $subject,
            tags: ['invoice', 'vendor'],
            metadata: [
                'invoice_id' => $this->invoice->id,
                'invoice_number' => $this->invoice->invoice_number,
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $dueInDays = now()->diffInDays($this->invoice->due_date, false);

        return new Content(
            view: 'emails.vendor-invoice-generated',
            with: [
                'invoice' => $this->invoice,
                'vendor' => $this->vendor,
                'dueInDays' => round($dueInDays),
                'isVendorCopy' => $this->isVendorCopy,
                'period' => $this->invoice->period_start->format('M d, Y') . ' - ' . $this->invoice->period_end->format('M d, Y'),
                'totalAmount' => 'Ksh ' . number_format($this->invoice->total_amount, 2),
                'totalScans' => $this->invoice->total_scans,
                'ratePerMeal' => 'Ksh 65.00',
                'cycleNumber' => $this->invoice->cycle_number ?? 'N/A',
                'dueDate' => $this->invoice->due_date->format('F j, Y'),
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
            Attachment::fromData(fn () => $pdf->output(), "invoice-{$this->invoice->invoice_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
