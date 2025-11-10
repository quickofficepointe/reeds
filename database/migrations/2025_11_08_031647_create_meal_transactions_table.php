<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('transaction_code')->unique();
            $table->decimal('amount', 8, 2)->default(70.00);
            $table->date('meal_date');
            $table->time('meal_time');
            $table->LongText('qr_code_scanned');
            $table->json('scan_data')->nullable();
            $table->timestamps();

            // Prevent duplicate meals per day per employee
            $table->unique(['employee_id', 'meal_date']);

            // Indexes for better performance
            $table->index(['vendor_id', 'meal_date']);
            $table->index(['employee_id', 'meal_date']);
            $table->index('transaction_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_transactions');
    }
};
