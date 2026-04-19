<?php
// database/migrations/2026_04_18_add_reward_fields_to_meal_transactions.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meal_transactions', function (Blueprint $table) {
            // Add reward tracking fields
            $table->boolean('is_security_reward')->default(false)->after('amount');
            $table->foreignId('reward_id')->nullable()->after('is_security_reward')->constrained('rewards')->onDelete('set null');
            $table->enum('meal_type', ['regular', 'reward'])->default('regular')->after('reward_id');

            // Add indexes for reward queries
            $table->index(['is_security_reward', 'meal_date']);
            $table->index(['reward_id']);
        });
    }

    public function down(): void
    {
        Schema::table('meal_transactions', function (Blueprint $table) {
            $table->dropForeign(['reward_id']);
            $table->dropColumn(['is_security_reward', 'reward_id', 'meal_type']);
            $table->dropIndex(['is_security_reward', 'meal_date']);
            $table->dropIndex(['reward_id']);
        });
    }
};
