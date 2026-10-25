<?php

use App\Http\Controllers\Customer\DashboardController;
use App\Http\Controllers\Customer\PaymentController;
use App\Http\Controllers\Customer\ProfileController;
use App\Http\Controllers\Customer\RegistrationController;
use App\Http\Controllers\Customer\SubscriptionController;
use App\Http\Controllers\Customer\TransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'customer'])->prefix('customer')->name('customer.')->group(function () {
    Route::get('/registration/success', [RegistrationController::class, 'success'])->name('registration.success');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/subscriptions', [SubscriptionController::class, 'store'])->name('subscriptions.store');
    Route::post('/payments/checkout', [PaymentController::class, 'checkout'])->name('payments.checkout');
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Notifications
    Route::get('/notifications', [App\Http\Controllers\Customer\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/recent', [App\Http\Controllers\Customer\NotificationController::class, 'recent'])->name('notifications.recent');
    Route::post('/notifications/{id}/read', [App\Http\Controllers\Customer\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [App\Http\Controllers\Customer\NotificationController::class, 'markAllAsRead'])->name('notifications.read_all');
    Route::delete('/notifications/{id}', [App\Http\Controllers\Customer\NotificationController::class, 'destroy'])->name('notifications.destroy');
});

Route::prefix('customer')->name('customer.')->group(function () {
    Route::get('/payments/stripe/{subscription}/success', [PaymentController::class, 'stripeSuccess'])->name('payments.stripe.success');
    Route::get('/payments/stripe/{subscription}/cancel', [PaymentController::class, 'stripeCancel'])->name('payments.stripe.cancel');
    Route::match(['GET', 'POST'], '/payments/sslcommerz/success', [PaymentController::class, 'sslSuccess'])->name('payments.ssl.success');
    Route::match(['GET', 'POST'], '/payments/sslcommerz/fail', [PaymentController::class, 'sslFail'])->name('payments.ssl.fail');
    Route::match(['GET', 'POST'], '/payments/sslcommerz/cancel', [PaymentController::class, 'sslCancel'])->name('payments.ssl.cancel');
});
