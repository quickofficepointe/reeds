<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code')->unique();
            $table->string('payroll_no')->nullable();
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->foreignId('sub_department_id')->nullable()->constrained('sub_departments')->onDelete('set null');
            $table->string('employment_type')->default('Regular');
            $table->string('title')->nullable();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->date('date_of_joining')->nullable();
            $table->boolean('on_probation')->default(false);
            $table->boolean('on_contract')->default(false);
            $table->string('icard_number')->unique()->nullable();
            $table->enum('gender', ['Male', 'Female', 'Other'])->nullable();
            $table->date('birth_date')->nullable();
            $table->string('marital_status')->nullable();
            $table->date('anniversary_date')->nullable();
            $table->string('religion')->nullable();
            $table->string('mother_tongue')->nullable();
            $table->string('nationality')->nullable();
            $table->string('ethnicity')->nullable();
            $table->string('tribe')->nullable();
            $table->string('designation')->nullable();
            $table->string('category')->nullable();
            $table->string('qr_code')->unique()->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes for better performance
            $table->index(['employee_code']);
            $table->index(['department_id']);
            $table->index(['sub_department_id']);
            $table->index(['is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
