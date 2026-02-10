<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
            $table->integer('total_scans');
            $table->decimal('total_amount', 10, 2);
            $table->enum('status', ['draft', 'pending', 'paid', 'overdue', 'cancelled'])->default('pending');
            $table->date('invoice_date')->default(DB::raw('CURRENT_DATE'));
            $table->date('due_date');
            $table->boolean('is_test')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('vendor_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('vendor_invoices')->onDelete('cascade');
            $table->date('date');
            $table->integer('scans');
            $table->decimal('rate', 8, 2);
            $table->decimal('amount', 10, 2);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendor_invoice_items');
        Schema::dropIfExists('vendor_invoices');
    }
};
