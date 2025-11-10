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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            $transaction->transaction_code = 'ML' . date('Ymd') . strtoupper(uniqid());
            $transaction->meal_time = now()->format('H:i:s');
        });
    }
}
