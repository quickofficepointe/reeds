<?php
// app/Mail/InvoiceGenerationSummary.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class InvoiceGenerationSummary extends Mailable
{
    use Queueable, SerializesModels;

    public array $summary;

    /**
     * Create a new message instance.
     */
    public function __construct(array $summary)
    {
        $this->summary = $summary;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $totalGenerated = $this->summary['generated'] ?? 0;
        $hasErrors = !empty($this->summary['errors']);

        $subject = $hasErrors
            ? "Invoice Generation Summary: {$totalGenerated} generated with errors"
            : "Invoice Generation Summary: {$totalGenerated} invoices generated successfully";

        return new Envelope(
            subject: $subject,
            tags: ['admin', 'summary', 'invoice-generation'],
            metadata: [
                'generated' => $totalGenerated,
                'skipped' => $this->summary['skipped'] ?? 0,
                'error_count' => count($this->summary['errors'] ?? []),
                'date' => $this->summary['date'] ?? Carbon::now()->format('Y-m-d H:i:s'),
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-generation-summary',
            with: [
                'summary' => $this->summary,
                'generated' => $this->summary['generated'] ?? 0,
                'skipped' => $this->summary['skipped'] ?? 0,
                'total' => $this->summary['total'] ?? 0,
                'errors' => $this->summary['errors'] ?? [],
                'date' => Carbon::parse($this->summary['date'] ?? now())->format('F j, Y \a\t g:i A'),
                'successRate' => $this->calculateSuccessRate(),
            ],
        );
    }

    /**
     * Calculate success rate
     */
    protected function calculateSuccessRate(): string
    {
        $total = $this->summary['total'] ?? 0;
        $generated = $this->summary['generated'] ?? 0;

        if ($total === 0) {
            return '0%';
        }

        $rate = ($generated / $total) * 100;
        return round($rate, 1) . '%';
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
