<?php
// database/migrations/xxxx_xx_xx_create_vendor_invoices_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vendor_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->onDelete('cascade');
            $table->string('invoice_number')->unique();
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('total_scans')->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->enum('status', ['draft', 'pending', 'paid', 'overdue', 'cancelled'])->default('pending');
            $table->date('invoice_date');
            $table->date('due_date');
            $table->boolean('is_test')->default(false);
            $table->text('notes')->nullable();
            $table->integer('cycle_number')->nullable(); // Bi-weekly cycle number
            $table->string('period_name')->nullable(); // e.g., "Feb 2-14, 2026"
            $table->timestamps();

            // Indexes for performance
            $table->index(['vendor_id', 'status']);
            $table->index(['vendor_id', 'invoice_date']);
            $table->index('due_date');
            $table->index('cycle_number');
        });

        Schema::create('vendor_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('vendor_invoices')->onDelete('cascade');
            $table->date('date');
            $table->integer('scans')->default(0);
            $table->decimal('rate', 8, 2)->default(65.00); // Updated to 65 Ksh
            $table->decimal('amount', 10, 2)->default(0);
            $table->text('description')->nullable();
            $table->json('transaction_ids')->nullable(); // Store transaction IDs for reference
            $table->timestamps();

            $table->index('date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendor_invoice_items');
        Schema::dropIfExists('vendor_invoices');
    }
};
