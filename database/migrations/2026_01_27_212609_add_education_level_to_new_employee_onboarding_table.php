<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('new_employee_onboarding', function (Blueprint $table) {
            // Add the new column(s)
            $table->enum('education_level', [
                'High School',
                'Certificate',
                'Diploma',
                'Degree (Undergraduate)',
                'Masters',
                'PhD'
            ])->nullable()->after('gender');

            // Optional additional education fields
            $table->string('field_of_study')->nullable()->after('education_level');
            $table->string('institution')->nullable()->after('field_of_study');
            $table->string('year_completed', 4)->nullable()->after('institution');
                $table->string('cv_upload', 500)->nullable()->after('kra_certificate_photo');
        });
    }

   public function down(): void
{
    Schema::table('new_employee_onboarding', function (Blueprint $table) {
        if (Schema::hasColumn('new_employee_onboarding', 'education_level')) {
            $table->dropColumn('education_level');
        }

        if (Schema::hasColumn('new_employee_onboarding', 'field_of_study')) {
            $table->dropColumn('field_of_study');
        }

        if (Schema::hasColumn('new_employee_onboarding', 'institution')) {
            $table->dropColumn('institution');
        }

        if (Schema::hasColumn('new_employee_onboarding', 'year_completed')) {
            $table->dropColumn('year_completed');
        }

        if (Schema::hasColumn('new_employee_onboarding', 'cv_upload')) {
            $table->dropColumn('cv_upload');
        }
    });
}

};
