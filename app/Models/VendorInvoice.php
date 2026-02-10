<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'notes'
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'invoice_date' => 'date',
        'due_date' => 'date',
        'total_amount' => 'decimal:2',
        'is_test' => 'boolean'
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(VendorInvoiceItem::class, 'invoice_id');
    }

    public function getPeriodAttribute(): string
    {
        return $this->period_start->format('M d, Y') . ' - ' . $this->period_end->format('M d, Y');
    }

    public function getFormattedTotalAttribute(): string
    {
        return 'Ksh ' . number_format($this->total_amount, 2);
    }

    public function markAsPaid(): void
    {
        $this->status = 'paid';
        $this->save();
    }
}

class VendorInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'date',
        'scans',
        'rate',
        'amount',
        'description'
    ];

    protected $casts = [
        'date' => 'date',
        'rate' => 'decimal:2',
        'amount' => 'decimal:2'
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(VendorInvoice::class, 'invoice_id');
    }
}
