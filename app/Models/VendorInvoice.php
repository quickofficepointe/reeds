<?php
// app/Models/VendorInvoice.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class VendorInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'invoice_number',
        'period_start',
        'period_end',
        'total_scans',
        'total_amount',
        'status',
        'invoice_date',
        'due_date',
        'is_test',
        'notes',
        'cycle_number',
        'period_name'
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'invoice_date' => 'date',
        'due_date' => 'date',
        'total_amount' => 'decimal:2',
        'is_test' => 'boolean',
        'cycle_number' => 'integer'
    ];

    protected $appends = [
        'vendor_phone',
        'vendor_email',
        'vendor_business_name',
        'formatted_period',
        'formatted_total',
        'status_badge'
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(VendorInvoiceItem::class, 'invoice_id');
    }

    /**
     * Check if the invoice is overdue
     */
    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->due_date && Carbon::now()->gt($this->due_date);
    }

    /**
     * Get formatted period
     */
    public function getFormattedPeriodAttribute(): string
    {
        return $this->period_start->format('M d, Y') . ' - ' . $this->period_end->format('M d, Y');
    }

    /**
     * Get formatted total amount
     */
    public function getFormattedTotalAttribute(): string
    {
        return 'Ksh ' . number_format($this->total_amount, 2);
    }

    /**
     * Get vendor phone number from profile
     */
    public function getVendorPhoneAttribute(): ?string
    {
        return $this->vendor->profile->phone_number ?? null;
    }

    /**
     * Get vendor email
     */
    public function getVendorEmailAttribute(): string
    {
        return $this->vendor->email;
    }

    /**
     * Get vendor business name from profile
     */
    public function getVendorBusinessNameAttribute(): ?string
    {
        return $this->vendor->profile->business_name ?? null;
    }

    /**
     * Get vendor bank details
     */
    public function getVendorBankDetailsAttribute(): array
    {
        $profile = $this->vendor->profile;

        return [
            'bank_name' => $profile->bank_name ?? 'Cooperative Bank of Kenya',
            'account_name' => $profile->account_name ?? $this->vendor->name,
            'account_number' => $profile->account_number ?? 'To be provided',
            'bank_branch' => $profile->bank_branch ?? 'Head Office',
            'swift_code' => $profile->swift_code ?? 'KCOOKENA',
        ];
    }

    /**
     * Get status badge HTML
     */
    public function getStatusBadgeAttribute(): string
    {
        $colors = [
            'paid' => 'bg-green-100 text-green-800',
            'pending' => 'bg-yellow-100 text-yellow-800',
            'overdue' => 'bg-red-100 text-red-800',
            'draft' => 'bg-gray-100 text-gray-800',
            'cancelled' => 'bg-gray-100 text-gray-800'
        ];

        $color = $colors[$this->status] ?? 'bg-gray-100 text-gray-800';

        return '<span class="px-2 py-1 text-xs font-semibold rounded-full ' . $color . '">'
                . ucfirst($this->status) .
                '</span>';
    }

    /**
     * Get status text
     */
    public function getStatusTextAttribute(): string
    {
        return ucfirst($this->status);
    }

    /**
     * Mark invoice as paid
     */
    public function markAsPaid(): void
    {
        $this->status = 'paid';
        $this->save();
    }

    /**
     * Scope for pending invoices
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for paid invoices
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope for overdue invoices
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
            ->where('due_date', '<', Carbon::now());
    }

    /**
     * Scope for current cycle
     */
    public function scopeCurrentCycle($query)
    {
        $currentPeriod = app(\App\Services\InvoicePeriodService::class)->getCurrentPeriod();
        return $query->where('period_start', $currentPeriod['start']->format('Y-m-d'))
                    ->where('period_end', $currentPeriod['end']->format('Y-m-d'));
    }

    /**
     * Scope for invoices within date range
     */
    public function scopeInPeriod($query, $start, $end)
    {
        return $query->whereBetween('period_start', [$start, $end])
            ->orWhereBetween('period_end', [$start, $end]);
    }
}
