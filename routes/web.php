<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubDepartmentController;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('welcome');
});

// Authentication Routes
Auth::routes();
Auth::routes(['verify' => true]);
// Profile Routes (accessible without complete profile)
Route::middleware(['auth'])->prefix('profile')->group(function () {
    Route::get('/', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/update', [ProfileController::class, 'update'])->name('profile.update');
});

// Public routes
Route::get('/home', [HomeController::class, 'index'])->name('home');

// Admin Routes (protected by profile complete middleware)
// Admin Routes (protected by profile complete middleware)
Route::middleware(['auth', 'verified', 'admin', 'profile.complete'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
    Route::get('/verifications', [ProfileController::class, 'pendingVerifications'])->name('verifications');

    // Department Routes
    Route::resource('departments', DepartmentController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::post('departments/{department}/toggle-status', [DepartmentController::class, 'toggleStatus'])->name('departments.toggle-status');

    // Sub-department Routes
    Route::resource('sub-departments', SubDepartmentController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::post('sub-departments/{sub_department}/toggle-status', [SubDepartmentController::class, 'toggleStatus'])->name('sub-departments.toggle-status');
    Route::get('sub-departments/by-department/{department}', [SubDepartmentController::class, 'byDepartment'])->name('sub-departments.by-department');

    // Employee Routes
    Route::resource('employees', EmployeeController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::post('employees/{employee}/generate-qr', [EmployeeController::class, 'generateQrCode'])->name('employees.generate-qr');
    Route::post('employees/{employee}/toggle-status', [EmployeeController::class, 'toggleStatus'])->name('employees.toggle-status');
    Route::get('employees/by-department/{department}', [EmployeeController::class, 'byDepartment'])->name('employees.by-department');
    Route::get('employees/by-sub-department/{subDepartment}', [EmployeeController::class, 'bySubDepartment'])->name('employees.by-sub-department');
  // Employee Import/Export Routes
    Route::get('employees/import', [EmployeeController::class, 'import'])->name('employees.import');
    Route::post('employees/import', [EmployeeController::class, 'processImport'])->name('employees.process-import');
    Route::get('employees/export', [EmployeeController::class, 'export'])->name('employees.export');
    Route::get('employees/qr-codes', [EmployeeController::class, 'qrCodes'])->name('employees.qr-codes');
// Analytics Routes
Route::get('/analytics', [AdminController::class, 'analytics'])->name('analytics');
Route::get('/analytics/data', [AdminController::class, 'getAnalyticsData'])->name('analytics.data');
Route::get('/vendor/{vendor}/details', [AdminController::class, 'getVendorDetails'])->name('vendor.details');
Route::get('employees/{employee}/qr-data', [EmployeeController::class, 'getQrData'])->name('employees.qr-data');
    Route::post('/verify/{profile}', [ProfileController::class, 'verify'])->name('verify-vendor');
});
// Vendor Routes (protected by profile complete and verification middleware)
// Vendor Routes
Route::middleware(['auth', 'verified', 'vendor', 'profile.complete'])->prefix('vendor')->name('vendor.')->group(function () {
    Route::get('/dashboard', [VendorController::class, 'index'])->name('dashboard');
    Route::get('/scan', [VendorController::class, 'scan'])->name('scan');
    Route::post('/scan', [VendorController::class, 'processScan'])->name('process-scan');
    Route::get('/scan-history', [VendorController::class, 'getScanHistory'])->name('scan-history');
    Route::get('/dashboard-stats', [VendorController::class, 'getDashboardStats'])->name('dashboard-stats');
});
