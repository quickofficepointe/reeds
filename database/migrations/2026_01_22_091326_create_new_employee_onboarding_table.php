<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('new_employee_onboarding', function (Blueprint $table) {
            $table->id();

            // ✅ ADDED: Token for continuation
            $table->string('token')->unique()->nullable();

            // --- BASIC BIO DATA ---
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('personal_phone')->nullable();
            $table->string('personal_email')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['Male', 'Female', 'Other'])->nullable();

            // Employment Details
            $table->string('designation');
            $table->date('date_of_joining');
            $table->string('employment_type')->default('Regular');
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->foreignId('sub_department_id')->nullable()->constrained('sub_departments')->onDelete('set null');

            // --- IDENTIFICATION NUMBERS ---
            $table->string('national_id_number')->nullable();
            $table->string('passport_number')->nullable();
            $table->string('nssf_number')->nullable();
            $table->string('sha_number')->nullable();
            $table->string('kra_pin')->nullable();

            // --- STATUTORY DOCUMENTS ---
        $table->string('national_id_photo', 500)->nullable();
$table->string('passport_photo', 500)->nullable();
$table->string('nssf_card_photo', 500)->nullable();
$table->string('sha_card_photo', 500)->nullable();
$table->string('kra_certificate_photo', 500)->nullable();
$table->string('passport_size_photo', 500)->nullable();


            // --- NEXT OF KIN ---
            $table->string('next_of_kin_name');
            $table->string('next_of_kin_relationship');
            $table->string('next_of_kin_phone');
            $table->string('next_of_kin_email')->nullable();
            $table->text('next_of_kin_address')->nullable();

            // --- PROCESSING FIELDS ---
            $table->enum('status', [
                'draft', 'submitted', 'verified', 'processed', 'rejected'
            ])->default('draft');

            $table->string('assigned_employee_code')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users');
            $table->timestamp('processed_at')->nullable();

            // Location and Unit
            $table->string('location')->default('Mombasa');
            $table->foreignId('unit_id')->nullable()->constrained()->onDelete('set null');

            // Notes
            $table->text('hr_notes')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['status']);
            $table->index(['token']); // ✅ ADDED
            $table->index(['location']);
            $table->index(['department_id']);
            $table->index(['unit_id']);
            $table->index(['personal_email']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('new_employee_onboarding');
    }
};
