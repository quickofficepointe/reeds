<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MealTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'employee_id',
        'transaction_code',
        'amount',
        'meal_date',
        'meal_time',
        'qr_code_scanned',
        'scan_data',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'meal_date' => 'date',
        'scan_data' => 'array',
    ];

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Generate unique transaction code
     */
    public static function generateTransactionCode(): string
    {
        do {
            $code = 'TXN' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (static::where('transaction_code', $code)->exists());

        return $code;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->transaction_code)) {
                $transaction->transaction_code = static::generateTransactionCode();
            }

            if (empty($transaction->meal_time)) {
                $transaction->meal_time = now()->format('H:i:s');
            }
        });
    }

    /**
     * Scope for today's transactions
     */
    public function scopeToday($query)
    {
        return $query->whereDate('meal_date', today());
    }

    /**
     * Scope for vendor's transactions
     */
    public function scopeByVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }
}
