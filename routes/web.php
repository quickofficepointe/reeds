<?php

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
Route::prefix('documents')->name('documents.')->group(function () {
    Route::get('/upload/{token}', [EmployeeController::class, 'showDocumentUploadForm'])
        ->name('upload');
    Route::post('/upload/{token}', [EmployeeController::class, 'processDocumentUpload'])
        ->name('process-upload');
    Route::get('/success/{token}', [EmployeeController::class, 'showUploadSuccess'])
        ->name('success');
    Route::get('/d/{token}', [EmployeeController::class, 'redirectShortLink'])
        ->name('short-link');
});

// ===============================
// PUBLIC ONBOARDING ROUTES
// ===============================
Route::prefix('employee-onboarding')->group(function () {
    Route::get('/', [EmployeeOnboardingController::class, 'start'])
        ->name('employee.onboarding.start');
    Route::post('/store', [EmployeeController::class, 'store'])
        ->name('employee.onboarding.store');
    Route::get('/confirmation/{token}', [EmployeeOnboardingController::class, 'showConfirmation'])
        ->name('employee.onboarding.confirmation');
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

    // Dashboard & General Admin
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
    Route::get('/verifications', [ProfileController::class, 'pendingVerifications'])->name('verifications');
    Route::post('/verify/{profile}', [ProfileController::class, 'verify'])->name('verify-vendor');

    // Analytics
    Route::get('/analytics', [AdminController::class, 'analytics'])->name('analytics');
    Route::get('/analytics/data', [AdminController::class, 'getAnalyticsData'])->name('analytics.data');
    Route::get('/analytics/unit/{unit}', [AdminController::class, 'getUnitAnalytics'])->name('analytics.unit');
    Route::get('/vendor/{vendor}/details', [AdminController::class, 'getVendorDetails'])->name('vendor.details');

    // Employee Onboarding (Admin)
    Route::prefix('onboarding')->name('onboarding.')->group(function () {
        Route::get('/', [EmployeeOnboardingController::class, 'adminIndex'])->name('index');
        Route::get('/{id}', [EmployeeOnboardingController::class, 'adminShow'])->name('show');
        Route::post('/{id}/status', [EmployeeOnboardingController::class, 'adminUpdateStatus'])->name('update-status');
        Route::get('/{id}/download/{document}', [EmployeeOnboardingController::class, 'adminDownloadDocument'])->name('download');
        Route::get('/department/{department_id}', [EmployeeOnboardingController::class, 'getDepartmentApplications'])
            ->name('department.applications');
        Route::get('/department/{department_id}/export', [EmployeeOnboardingController::class, 'exportDepartmentApplications'])
            ->name('department.export');
    });

    // Departments
    Route::resource('departments', DepartmentController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::post('departments/{department}/toggle-status',
        [DepartmentController::class, 'toggleStatus']
    )->name('departments.toggle-status');

    // Sub-Departments
    Route::resource('sub-departments', SubDepartmentController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::post('sub-departments/{sub_department}/toggle-status',
        [SubDepartmentController::class, 'toggleStatus']
    )->name('sub-departments.toggle-status');
    Route::get('sub-departments/by-department/{department}',
        [SubDepartmentController::class, 'byDepartment']
    )->name('sub-departments.by-department');

    // Units
    Route::get('/units', [UnitController::class, 'index'])->name('units.index');
    Route::post('/units', [UnitController::class, 'store'])->name('units.store');
    Route::put('/units/{unit}', [UnitController::class, 'update'])->name('units.update');
    Route::delete('/units/{unit}', [UnitController::class, 'destroy'])->name('units.destroy');
    Route::post('/units/{unit}/toggle-status', [UnitController::class, 'toggleStatus'])->name('units.toggle-status');
    Route::get('/units/{unit}/edit-modal', [UnitController::class, 'editModal'])->name('units.edit-modal');

    // ====================================
    // EMPLOYEE ROUTES (Admin only)
    // ====================================

    // Static routes (no parameters)
    Route::get('employees/import', [EmployeeController::class, 'import'])->name('employees.import');
    Route::post('employees/import', [EmployeeController::class, 'processImport'])->name('employees.process-import');
    Route::get('employees/export', [EmployeeController::class, 'export'])->name('employees.export');
    Route::get('employees/qr-codes', [EmployeeController::class, 'qrCodes'])->name('employees.qr-codes');
    Route::get('employees/search', [EmployeeController::class, 'search'])->name('employees.search');
    Route::get('employees/stats', [EmployeeController::class, 'getStats'])->name('employees.stats');

    // Bulk operations
    Route::post('employees/bulk-regenerate-qr', [EmployeeController::class, 'bulkRegenerateQrCodes'])
        ->name('employees.bulk-regenerate-qr');
    Route::post('employees/bulk-delete', [EmployeeController::class, 'bulkDelete'])
        ->name('employees.bulk-delete');
    Route::post('employees/bulk-status-update', [EmployeeController::class, 'bulkStatusUpdate'])
        ->name('employees.bulk-status-update');
    Route::post('employees/bulk-update-phones', [EmployeeController::class, 'bulkUpdatePhones'])
        ->name('employees.bulk-update-phones');
    Route::post('employees/bulk-send-document-invitations', [EmployeeController::class, 'bulkSendDocumentInvitations'])
        ->name('employees.bulk-send-document-invitations');

    // Filtering routes
    Route::get('employees/by-department/{department}', [EmployeeController::class, 'byDepartment'])
        ->name('employees.by-department');
    Route::get('employees/by-sub-department/{subDepartment}', [EmployeeController::class, 'bySubDepartment'])
        ->name('employees.by-sub-department');
    Route::get('employees/by-unit/{unit}', [EmployeeController::class, 'byUnit'])
        ->name('employees.by-unit');

    // Employee document invitation routes (Admin only)
    Route::prefix('employees/{employee}/documents')->name('employees.documents.')->group(function () {
        Route::post('/send-invitation', [EmployeeController::class, 'sendDocumentInvitation'])
            ->name('send-invitation');
        Route::post('/send-reminder', [EmployeeController::class, 'sendDocumentReminder'])
            ->name('send-reminder');
    });

    // Invitation status routes (Admin only)
    Route::get('/invitation/status/{token}', [EmployeeController::class, 'getInvitationStatus'])
        ->name('invitation.status');
    Route::post('/invitation/resend/{token}', [EmployeeController::class, 'resendInvitation'])
        ->name('invitation.resend');

    // Individual employee routes
    Route::get('employees/create', [EmployeeController::class, 'create'])->name('employees.create');
    Route::get('employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
    Route::get('employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');

    // Employee actions
    Route::post('employees/{employee}/generate-qr', [EmployeeController::class, 'generateQrCode'])
        ->name('employees.generate-qr');
    Route::post('employees/{employee}/toggle-status', [EmployeeController::class, 'toggleStatus'])
        ->name('employees.toggle-status');
    Route::get('employees/{employee}/qr-data', [EmployeeController::class, 'getQrData'])
        ->name('employees.qr-data');

    // Resource routes LAST
    Route::resource('employees', EmployeeController::class)
        ->only(['index', 'store', 'update', 'destroy']);
});

// ===============================
// VENDOR ROUTES
// ===============================
Route::middleware(['auth', 'verified', 'vendor', 'profile.complete'])->prefix('vendor')->name('vendor.')->group(function () {
    Route::get('/dashboard', [VendorController::class, 'index'])->name('dashboard');
    Route::get('/scan', [VendorController::class, 'scan'])->name('scan');
    Route::post('/scan', [VendorController::class, 'processScan'])->name('process-scan');
    Route::get('/scan-history', [VendorController::class, 'getScanHistory'])->name('scan-history');
    Route::get('/dashboard-stats', [VendorController::class, 'getDashboardStats'])->name('dashboard-stats');
});
