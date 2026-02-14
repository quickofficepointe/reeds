<?php
// app/Models/VendorInvoiceItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorInvoiceItem extends Model
{
    use HasFactory;

    protected $table = 'vendor_invoice_items';

    protected $fillable = [
        'invoice_id',
        'date',
        'scans',
        'rate',
        'amount',
        'description',
        'transaction_ids'
    ];

    protected $casts = [
        'date' => 'date',
        'rate' => 'decimal:2',
        'amount' => 'decimal:2',
        'transaction_ids' => 'array'
    ];

    protected $appends = [
        'formatted_amount',
        'formatted_rate',
        'day_name'
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(VendorInvoice::class, 'invoice_id');
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return 'Ksh ' . number_format($this->amount, 2);
    }

    /**
     * Get formatted rate
     */
    public function getFormattedRateAttribute(): string
    {
        return 'Ksh ' . number_format($this->rate, 2);
    }

    /**
     * Get day name
     */
    public function getDayNameAttribute(): string
    {
        return $this->date->format('l');
    }
}
