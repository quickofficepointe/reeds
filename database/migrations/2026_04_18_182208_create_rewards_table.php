<?php
// database/migrations/2026_04_18_create_rewards_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('unit_id')->constrained('units')->onDelete('cascade');
            $table->date('reward_date');
            $table->decimal('amount', 10, 2)->default(200.00);
            $table->string('reason')->nullable();
            $table->enum('status', ['pending', 'claimed', 'expired'])->default('pending');
            $table->foreignId('meal_transaction_id')->nullable()->constrained('meal_transactions')->onDelete('set null');

            // SMS tracking fields
            $table->boolean('sms_sent')->default(false);
            $table->timestamp('sms_sent_at')->nullable();
            $table->string('sms_message_id')->nullable();
            $table->string('sms_status')->nullable();
            $table->text('sms_error')->nullable();

            $table->timestamps();

            // Unique constraint - only one reward per day
            $table->unique(['reward_date']);

            // Indexes for performance
            $table->index(['reward_date', 'status']);
            $table->index(['employee_id', 'reward_date']);
            $table->index('unit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rewards');
    }
};
