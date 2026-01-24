<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');

            // Next of Kin Information
            $table->string('next_of_kin_name')->nullable();
            $table->string('next_of_kin_relationship')->nullable();
            $table->string('next_of_kin_phone')->nullable();
            $table->string('next_of_kin_email')->nullable();
            $table->text('next_of_kin_address')->nullable();

            // Statutory Documents (file paths)
            $table->string('national_id_photo')->nullable();
            $table->string('passport_photo')->nullable();
            $table->string('passport_size_photo')->nullable();
            $table->string('nssf_card_photo')->nullable();
            $table->string('sha_card_photo')->nullable();
            $table->string('kra_certificate_photo')->nullable();

            // Verification Status
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('verification_notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['employee_id', 'is_verified']);
            $table->index('is_verified');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
    }
};
