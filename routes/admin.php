<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\Auth\AuthController;
use App\Http\Controllers\Admin\BusinessLogicController;
use App\Http\Controllers\Admin\BusinessSettingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmailSettingsController;
use App\Http\Controllers\Admin\LegalController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PaymentSettingsController;
use App\Http\Controllers\Admin\PayrollController;
use App\Http\Controllers\Admin\PlanAccessTypeController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\PlanFacilityController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest:admin')->prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'storeLogin']);
    Route::get('register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('register', [AuthController::class, 'storeRegister']);
});

Route::middleware(['auth:admin', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    // Account Management
    Route::resource('staff', UserController::class)->parameters(['staff' => 'user'])->middleware('supreme');
    Route::get('customers', [UserController::class, 'customers'])->name('customers.index')->middleware('supreme');
    Route::get('customers/create', [UserController::class, 'createCustomer'])->name('customers.create')->middleware('supreme');
    Route::post('customers', [UserController::class, 'storeCustomer'])->name('customers.store')->middleware('supreme');
    Route::get('customers/{user}/edit', [UserController::class, 'editCustomer'])->name('customers.edit')->middleware('supreme');
    Route::put('customers/{user}', [UserController::class, 'updateCustomer'])->name('customers.update')->middleware('supreme');
    Route::patch('staff/{user}/status', [UserController::class, 'updateStatus'])->name('staff.status')->middleware('supreme');
    Route::resource('roles', RoleController::class)->middleware('supreme');

    // GymOS Core Extensions
    Route::resource('plans', PlanController::class)->except(['create', 'edit', 'show']);
    Route::patch('plans/{plan}/toggle-active', [PlanController::class, 'toggleActive'])->name('plans.toggle-active');

    Route::name('plans.')->prefix('plans')->group(function () {
        Route::resource('access-types', PlanAccessTypeController::class)->except(['create', 'edit', 'show']);
        Route::patch('access-types/{accessType}/toggle-active', [PlanAccessTypeController::class, 'toggleActive'])->name('access-types.toggle-active');
        Route::resource('facilities', PlanFacilityController::class)->except(['create', 'edit', 'show']);
    }
    );

    Route::resource('subscriptions', SubscriptionController::class)->only(['index', 'store', 'destroy']);
    Route::patch('subscriptions/{subscription}/status', [SubscriptionController::class, 'updateStatus'])->name('subscriptions.status');
    Route::get('payments/pending', [PaymentController::class, 'pending'])->name('payments.pending');
    Route::patch('payments/{payment}/status', [PaymentController::class, 'updateStatus'])->name('payments.status');
    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('payroll', [PayrollController::class, 'index'])->name('payroll.index');
    Route::post('payroll/staff', [PayrollController::class, 'storeStaff'])->name('payroll.staff.store');
    Route::put('payroll/staff/{payrollStaff}', [PayrollController::class, 'updateStaff'])->name('payroll.staff.update');
    Route::patch('payroll/staff/{payrollStaff}/status', [PayrollController::class, 'updateStaffStatus'])->name('payroll.staff.status');
    Route::patch('payroll/periods/{payrollPeriod}/paid', [PayrollController::class, 'markPaid'])->name('payroll.periods.paid');
    Route::patch('payroll/periods/{payrollPeriod}/due', [PayrollController::class, 'markDue'])->name('payroll.periods.due');
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');

    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity_logs.index');
    Route::get('/activity-logs/{activityLog}', [ActivityLogController::class, 'show'])->name('activity_logs.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/business/branding', [BusinessSettingController::class, 'index'])->name('business.branding');
    Route::post('/business/branding', [BusinessSettingController::class, 'update'])->name('business.update');

    Route::get('/business/legal', [LegalController::class, 'index'])->name('legal.index');
    Route::post('/business/legal', [LegalController::class, 'update'])->name('legal.update');

    Route::get('/business/settings', [BusinessLogicController::class, 'index'])->name('business.settings.index');
    Route::post('/business/settings', [BusinessLogicController::class, 'update'])->name('business.settings.update');

    Route::get('/business/email', [EmailSettingsController::class, 'index'])->name('business.email.index');
    Route::post('/business/email', [EmailSettingsController::class, 'update'])->name('business.email.update');
    Route::post('/business/email/test', [EmailSettingsController::class, 'sendTest'])->name('business.email.test');
    Route::get('/business/payment-settings', [PaymentSettingsController::class, 'index'])->name('business.payment-settings.index');
    Route::post('/business/payment-settings', [PaymentSettingsController::class, 'update'])->name('business.payment-settings.update');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/recent', [NotificationController::class, 'recent'])->name('notifications.recent');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read_all');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
});
