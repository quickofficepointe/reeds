<?php

namespace App\Mail;

use App\Models\VendorInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceReminder extends Mailable
{
    use Queueable, SerializesModels;

    public $invoice;

    public function __construct(VendorInvoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function build()
    {
        $dueIn = now()->diffInDays($this->invoice->due_date);

        return $this->subject("Reminder: Invoice #{$this->invoice->invoice_number} Due in {$dueIn} Days")
                    ->view('emails.invoice-reminder')
                    ->with([
                        'dueIn' => $dueIn,
                        'vendor' => $this->invoice->vendor
                    ]);
    }
}
