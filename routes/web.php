<?php

use App\Http\Controllers\AdminMealController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeOnboardingController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubDepartmentController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Auth;

// ===============================
// PUBLIC DOCUMENT ROUTES (NO AUTH REQUIRED)
// ===============================
Route::get('/d/{token}', [EmployeeController::class, 'redirectShortLink'])->name('short-link');

Route::prefix('documents')->name('documents.')->group(function () {
    Route::get('/upload/{token}', [EmployeeController::class, 'showDocumentUploadForm'])->name('upload');
    Route::post('/upload/{token}', [EmployeeController::class, 'processDocumentUpload'])->name('process-upload');
    Route::get('/success/{token}', [EmployeeController::class, 'showUploadSuccess'])->name('success');
});

// ===============================
// PUBLIC ONBOARDING ROUTES
// ===============================
Route::prefix('employee-onboarding')->group(function () {
    Route::get('/', [EmployeeOnboardingController::class, 'start'])->name('employee.onboarding.start');
    Route::post('/store', [EmployeeOnboardingController::class, 'store'])->name('employee.onboarding.store');
    Route::get('/confirmation/{token}', [EmployeeOnboardingController::class, 'showConfirmation'])->name('employee.onboarding.confirmation');
});

// ===============================
// HOME & AUTH ROUTES
// ===============================
Route::get('/', function () {
    return view('welcome');
});

Route::get('/home', [HomeController::class, 'index'])->name('home');

// Authentication Routes
Auth::routes();
Auth::routes(['verify' => true]);

// Profile Routes (accessible without complete profile)
Route::middleware(['auth'])->prefix('profile')->group(function () {
    Route::get('/', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/update', [ProfileController::class, 'update'])->name('profile.update');
});

// ===============================
// ADMIN ROUTES (REQUIRES AUTH)
// ===============================
Route::middleware(['auth', 'verified', 'admin', 'profile.complete'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

    // ====================================
    // DASHBOARD & GENERAL ADMIN
    // ====================================
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
    Route::get('/verifications', [ProfileController::class, 'pendingVerifications'])->name('verifications');
    Route::post('/verify/{profile}', [ProfileController::class, 'verify'])->name('verify-vendor');

    // Dashboard stats & quick actions
    Route::get('/dashboard/stats', [AdminController::class, 'getDashboardStats'])->name('dashboard.stats');
    Route::get('/quick-actions', [AdminController::class, 'getQuickActions'])->name('quick-actions');
    Route::get('/performance-comparison/{period?}', [AdminController::class, 'getPerformanceComparison'])->name('performance-comparison');
    Route::get('/system-health', [AdminController::class, 'getSystemHealth'])->name('system-health');

    // ====================================
    // USERS MANAGEMENT
    // ====================================
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [AdminController::class, 'usersIndex'])->name('index');
        Route::post('/', [AdminController::class, 'storeUser'])->name('store');
        Route::get('/{user}/edit', [AdminController::class, 'editUser'])->name('edit');
        Route::put('/{user}', [AdminController::class, 'updateUser'])->name('update');
        Route::delete('/{user}', [AdminController::class, 'destroyUser'])->name('destroy');
        Route::get('/{user}/assign-unit', [AdminController::class, 'assignUnitForm'])->name('assign-unit.form');
        Route::post('/{user}/assign-unit', [AdminController::class, 'assignUnit'])->name('assign-unit');
        Route::get('/{user}/reset-password', [AdminController::class, 'resetPasswordForm'])->name('reset-password.form');
        Route::post('/{user}/reset-password', [AdminController::class, 'resetPassword'])->name('reset-password');
    });

    // ====================================
    // ANALYTICS ROUTES
    // ====================================
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [AdminController::class, 'analytics'])->name('index');
        Route::get('/data', [AdminController::class, 'getAnalyticsData'])->name('data');
        Route::get('/unit/{unit}', [AdminController::class, 'getUnitAnalytics'])->name('unit');
        Route::get('/trends/30d', [AdminController::class, 'get30DayTrends'])->name('trends.30d');
        Route::get('/export/units', [AdminController::class, 'exportUnitAnalytics'])->name('export.units');
        Route::get('/export/unit/{unit}', [AdminController::class, 'exportSingleUnit'])->name('export.unit');
    });

    // ====================================
    // VENDOR ANALYTICS ROUTES
    // ====================================
    Route::prefix('vendor')->name('vendor.')->group(function () {
        Route::get('/{vendor}/details', [AdminController::class, 'getVendorDetails'])->name('details');
        Route::get('/{vendor}/analytics', [AdminController::class, 'getVendorAnalytics'])->name('analytics');
        Route::get('/{vendor}/analytics/export', [AdminController::class, 'exportVendorAnalytics'])->name('analytics.export');
        Route::post('/{vendor}/analytics/share', [AdminController::class, 'shareVendorAnalytics'])->name('analytics.share');
        Route::get('/{vendor}/analytics/month/{year}/{month}', [AdminController::class, 'getVendorMonthData'])->name('analytics.month.data');
    });

    // ====================================
    // REWARDS MANAGEMENT (ENHANCED - MULTIPLE UNITS)
    // ====================================
    Route::prefix('rewards')->name('rewards.')->group(function () {
        // Main views
        Route::get('/', [AdminController::class, 'rewardsIndex'])->name('index');

        // Single unit reward
        Route::post('/reward-today', [AdminController::class, 'rewardToday'])->name('reward-today');

        // Multiple units reward (NEW)
        Route::post('/multiple-units', [AdminController::class, 'rewardMultipleUnits'])->name('multiple-units');

        // Tomorrow's reward
        Route::post('/schedule-tomorrow', [AdminController::class, 'scheduleTomorrowReward'])->name('schedule-tomorrow');
        Route::post('/generate', [AdminController::class, 'generateTomorrowReward'])->name('generate');

        // Reward actions
        Route::post('/{reward}/resend-sms', [AdminController::class, 'resendRewardSms'])->name('resend-sms');
        Route::post('/{reward}/cancel', [AdminController::class, 'cancelReward'])->name('cancel');

        // Reports & exports
        Route::get('/export', [AdminController::class, 'exportRewardsReport'])->name('export');

        // API endpoints for AJAX
        Route::get('/stats', [AdminController::class, 'getRewardStats'])->name('stats');
        Route::get('/today', [AdminController::class, 'getTodayReward'])->name('today');
    });

    // Helper route for loading employees by unit (for rewards)
    Route::get('/units/{unit}/available-employees', [AdminController::class, 'getAvailableEmployeesForUnit'])
        ->name('units.available-employees');

    // ====================================
    // MEAL MANAGEMENT
    // ====================================
    Route::prefix('meals')->name('meals.')->group(function () {
        Route::get('/manual-entry', [AdminMealController::class, 'showManualEntryForm'])->name('manual-entry');
        Route::post('/manual-entry', [AdminMealController::class, 'processManualEntry'])->name('manual-entry.process');
        Route::post('/bulk-manual-entry', [AdminMealController::class, 'bulkManualEntry'])->name('bulk-manual-entry');
        Route::get('/feb2-report', [AdminMealController::class, 'getFeb2Report'])->name('feb2-report');
        Route::get('/recent-entries', [AdminMealController::class, 'getRecentEntries'])->name('recent-entries');
        Route::get('/unfed-employees', [AdminMealController::class, 'getUnfedEmployees'])->name('unfed-employees');
    });

    // ====================================
    // EMPLOYEE SCAN DATA EXPORT
    // ====================================
    Route::get('/employees/scan-data/export', [EmployeeController::class, 'exportScanData'])->name('employees.scan-data.export');
    Route::get('/employees/scan-data/download', [EmployeeController::class, 'generateScanDataReport'])->name('employees.scan-data.download');

    // ====================================
    // EMPLOYEE ONBOARDING (ADMIN)
    // ====================================
    Route::prefix('onboarding')->name('onboarding.')->group(function () {
        Route::get('/', [EmployeeOnboardingController::class, 'adminIndex'])->name('index');
        Route::get('/{id}', [EmployeeOnboardingController::class, 'adminShow'])->name('show');
        Route::post('/{id}/status', [EmployeeOnboardingController::class, 'adminUpdateStatus'])->name('update-status');
        Route::get('/{id}/download/{document}', [EmployeeOnboardingController::class, 'adminDownloadDocument'])->name('download');
        Route::get('/department/{department_id}', [EmployeeOnboardingController::class, 'getDepartmentApplications'])->name('department.applications');
        Route::get('/department/{department_id}/export', [EmployeeOnboardingController::class, 'exportDepartmentApplications'])->name('department.export');
    });

    // ====================================
    // DEPARTMENTS
    // ====================================
    Route::resource('departments', DepartmentController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::post('departments/{department}/toggle-status', [DepartmentController::class, 'toggleStatus'])->name('departments.toggle-status');

    // ====================================
    // SUB-DEPARTMENTS
    // ====================================
    Route::resource('sub-departments', SubDepartmentController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::post('sub-departments/{sub_department}/toggle-status', [SubDepartmentController::class, 'toggleStatus'])->name('sub-departments.toggle-status');
    Route::get('sub-departments/by-department/{department}', [SubDepartmentController::class, 'byDepartment'])->name('sub-departments.by-department');

    // ====================================
    // UNITS
    // ====================================
    Route::get('/units', [UnitController::class, 'index'])->name('units.index');
    Route::post('/units', [UnitController::class, 'store'])->name('units.store');
    Route::put('/units/{unit}', [UnitController::class, 'update'])->name('units.update');
    Route::delete('/units/{unit}', [UnitController::class, 'destroy'])->name('units.destroy');
    Route::post('/units/{unit}/toggle-status', [UnitController::class, 'toggleStatus'])->name('units.toggle-status');
    Route::get('/units/{unit}/edit-modal', [UnitController::class, 'editModal'])->name('units.edit-modal');

    // ====================================
    // EMPLOYEE MANAGEMENT
    // ====================================
    // Static routes (no parameters)
    Route::get('employees/import', [EmployeeController::class, 'import'])->name('employees.import');
    Route::post('employees/import', [EmployeeController::class, 'processImport'])->name('employees.process-import');
    Route::get('employees/export', [EmployeeController::class, 'export'])->name('employees.export');
    Route::get('employees/qr-codes', [EmployeeController::class, 'qrCodes'])->name('employees.qr-codes');
    Route::get('employees/search', [EmployeeController::class, 'search'])->name('employees.search');
    Route::get('employees/stats', [EmployeeController::class, 'getStats'])->name('employees.stats');

    // Document download routes
    Route::get('employees/{employee}/documents/{documentType}/download', [EmployeeController::class, 'downloadDocument'])->name('employees.documents.download');
    Route::get('employees/{employee}/documents/{documentType}/view', [EmployeeController::class, 'viewDocument'])->name('employees.documents.view');

    // Bulk operations
    Route::post('employees/bulk-regenerate-qr', [EmployeeController::class, 'bulkRegenerateQrCodes'])->name('employees.bulk-regenerate-qr');
    Route::post('employees/bulk-delete', [EmployeeController::class, 'bulkDelete'])->name('employees.bulk-delete');
    Route::post('employees/bulk-status-update', [EmployeeController::class, 'bulkStatusUpdate'])->name('employees.bulk-status-update');
    Route::post('employees/bulk-update-phones', [EmployeeController::class, 'bulkUpdatePhones'])->name('employees.bulk-update-phones');
    Route::post('employees/bulk-send-document-invitations', [EmployeeController::class, 'bulkSendDocumentInvitations'])->name('employees.bulk-send-document-invitations');

    // Filtering routes
    Route::get('employees/by-department/{department}', [EmployeeController::class, 'byDepartment'])->name('employees.by-department');
    Route::get('employees/by-sub-department/{subDepartment}', [EmployeeController::class, 'bySubDepartment'])->name('employees.by-sub-department');
    Route::get('employees/by-unit/{unit}', [EmployeeController::class, 'byUnit'])->name('employees.by-unit');

    // Employee document invitation routes
    Route::prefix('employees/{employee}/documents')->name('employees.documents.')->group(function () {
        Route::post('/send-invitation', [EmployeeController::class, 'sendDocumentInvitation'])->name('send-invitation');
        Route::post('/send-reminder', [EmployeeController::class, 'sendDocumentReminder'])->name('send-reminder');
    });

    // Invitation status routes
    Route::get('/invitation/status/{token}', [EmployeeController::class, 'getInvitationStatus'])->name('invitation.status');
    Route::post('/invitation/resend/{token}', [EmployeeController::class, 'resendInvitation'])->name('invitation.resend');

    // Individual employee routes
    Route::get('employees/create', [EmployeeController::class, 'create'])->name('employees.create');
    Route::get('employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
    Route::get('employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');

    // Employee actions
    Route::post('employees/{employee}/generate-qr', [EmployeeController::class, 'generateQrCode'])->name('employees.generate-qr');
    Route::post('employees/{employee}/toggle-status', [EmployeeController::class, 'toggleStatus'])->name('employees.toggle-status');
    Route::get('employees/{employee}/qr-data', [EmployeeController::class, 'getQrData'])->name('employees.qr-data');

    // Employee resource routes (LAST)
    Route::resource('employees', EmployeeController::class)->only(['index', 'store', 'update', 'destroy']);

    // ====================================
    // INVOICE MANAGEMENT
    // ====================================
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/', [AdminController::class, 'invoices'])->name('index');
        Route::get('/data', [AdminController::class, 'invoicesData'])->name('data');
        Route::get('/pending', [AdminController::class, 'pendingInvoices'])->name('pending');
        Route::get('/overdue', [AdminController::class, 'overdueInvoices'])->name('overdue');
        Route::get('/{id}', [AdminController::class, 'viewInvoice'])->name('view');
        Route::get('/{id}/download', [AdminController::class, 'downloadInvoice'])->name('download');
        Route::post('/{id}/mark-paid', [AdminController::class, 'markInvoiceAsPaid'])->name('mark-paid');
        Route::post('/{id}/send-reminder', [AdminController::class, 'sendInvoiceReminder'])->name('send-reminder');
        Route::post('/send-bulk-emails', [AdminController::class, 'sendBulkInvoiceEmails'])->name('send-bulk');
        Route::get('/email-recipients', [AdminController::class, 'getEmailRecipients'])->name('email-recipients');
        Route::post('/email-recipients/update', [AdminController::class, 'updateEmailRecipients'])->name('update-recipients');
    });

    // ====================================
    // TEST ROUTE (Debugging)
    // ====================================
    Route::get('/test/vendor/{vendorId}', function($vendorId) {
        try {
            $vendor = App\Models\User::with(['profile', 'unit'])->findOrFail($vendorId);
            return response()->json([
                'success' => true,
                'vendor' => [
                    'id' => $vendor->id,
                    'name' => $vendor->name,
                    'email' => $vendor->email,
                    'has_profile' => !is_null($vendor->profile),
                    'has_unit' => !is_null($vendor->unit),
                    'total_scans' => $vendor->mealTransactions()->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    });
});

// ===============================
// VENDOR ROUTES
// ===============================
Route::middleware(['auth', 'verified', 'vendor', 'profile.complete'])
    ->prefix('vendor')
    ->name('vendor.')
    ->group(function () {

    // Dashboard
    Route::get('/dashboard', [VendorController::class, 'index'])->name('dashboard');
    Route::get('/dashboard-stats', [VendorController::class, 'getDashboardStats'])->name('dashboard-stats');

    // QR Scanning
    Route::get('/scan', [VendorController::class, 'scan'])->name('scan');
    Route::post('/scan', [VendorController::class, 'processScan'])->name('process-scan');

    // History
    Route::get('/history', [VendorController::class, 'history'])->name('history');
    Route::get('/history/data', [VendorController::class, 'getScanHistory'])->name('history.data');
    Route::get('/history/range', [VendorController::class, 'getScanHistoryByRange'])->name('history.range');
    
    // Performance Analytics
    Route::get('/performance', [VendorController::class, 'performance'])->name('performance');
    Route::get('/analytics/daily', [VendorController::class, 'dailyAnalytics'])->name('analytics.daily');
    Route::get('/analytics/weekly', [VendorController::class, 'weeklyAnalytics'])->name('analytics.weekly');
    Route::get('/analytics/monthly', [VendorController::class, 'monthlyAnalytics'])->name('analytics.monthly');
    Route::get('/diagnose-analytics', [VendorController::class, 'diagnoseAnalytics'])->name('diagnose-analytics');

    // Invoices
    Route::get('/invoices', [VendorController::class, 'invoices'])->name('invoices');
    Route::get('/invoices/data', [VendorController::class, 'invoicesData'])->name('invoices.data');
    Route::get('/invoices/periods', [VendorController::class, 'getPeriods'])->name('invoices.periods');
    Route::get('/invoices/{id}/details', [VendorController::class, 'getInvoiceDetails'])->name('invoices.details');
    Route::get('/invoices/{id}/download', [VendorController::class, 'downloadInvoice'])->name('invoices.download');
    Route::post('/invoices/generate-test', [VendorController::class, 'generateTestInvoice'])->name('invoices.generate-test');
});
