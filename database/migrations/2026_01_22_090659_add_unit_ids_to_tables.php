<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add unit_id to users table
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('unit_id')
                  ->nullable()
                  ->after('role')
                  ->constrained('units')
                  ->onDelete('set null');

            $table->index(['unit_id', 'role']);
        });

        // 2. Add unit_id, email, and phone to employees table
        Schema::table('employees', function (Blueprint $table) {
            // Add unit_id
            $table->foreignId('unit_id')
                  ->nullable()
                  ->after('sub_department_id')
                  ->constrained('units')
                  ->onDelete('set null');

            // Add email (nullable, unique if possible)
            $table->string('email')->nullable()->after('last_name');

            // Add phone (nullable)
            $table->string('phone', 20)->nullable()->after('email');

            // Add indexes for better performance
            $table->index(['unit_id', 'is_active']);
            $table->index(['unit_id', 'department_id']);
            $table->index('email'); // Optional: if you'll search by email
            $table->index('phone'); // Optional: if you'll search by phone
        });

        // 3. Add unit_id to meal_transactions table
        Schema::table('meal_transactions', function (Blueprint $table) {
            $table->foreignId('unit_id')
                  ->nullable()
                  ->after('vendor_id')
                  ->constrained('units')
                  ->onDelete('set null');

            $table->index(['unit_id', 'meal_date']);
            $table->index(['unit_id', 'vendor_id', 'meal_date']);
        });
    }

    public function down(): void
    {
        // Drop in reverse order

        // 3. Remove from meal_transactions
        Schema::table('meal_transactions', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn('unit_id');
            $table->dropIndex(['unit_id', 'meal_date']);
            $table->dropIndex(['unit_id', 'vendor_id', 'meal_date']);
        });

        // 2. Remove from employees
        Schema::table('employees', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['unit_id', 'is_active']);
            $table->dropIndex(['unit_id', 'department_id']);
            $table->dropIndex('email');
            $table->dropIndex('phone');

            // Drop columns
            $table->dropForeign(['unit_id']);
            $table->dropColumn('unit_id');
            $table->dropColumn('email');
            $table->dropColumn('phone');
        });

        // 1. Remove from users
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn('unit_id');
            $table->dropIndex(['unit_id', 'role']);
        });
    }
};
