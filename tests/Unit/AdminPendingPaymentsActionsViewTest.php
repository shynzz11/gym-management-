<?php

test('admin pending payments page includes approve and deny actions with reason workflow', function () {
    $routes = file_get_contents(__DIR__.'/../../routes/admin.php');
    $controller = file_get_contents(__DIR__.'/../../app/Http/Controllers/Admin/PaymentController.php');
    $service = file_get_contents(__DIR__.'/../../app/Services/PaymentService.php');
    $page = file_get_contents(__DIR__.'/../../resources/js/Pages/Admin/Payments/Index.vue');

    expect($routes)
        ->toContain("Route::patch('payments/{payment}/status', [PaymentController::class, 'updateStatus'])->name('payments.status');");

    expect($controller)
        ->toContain('public function updateStatus(UpdatePaymentStatusRequest $request, Payment $payment): RedirectResponse')
        ->toContain("Gate::authorize('view_pending_payments');")
        ->toContain('Payment approved successfully.')
        ->toContain('Payment denied successfully.');

    expect($service)
        ->toContain('public function updatePendingStatus(Payment $payment, string $decision, ?string $denialReason = null): Payment')
        ->toContain('private function applyDeny(Payment $payment, ?string $denialReason): void')
        ->toContain("'denial_reason' => ");

    expect($page)
        ->toContain('<ConfirmationModal')
        ->toContain('showDenyReasonModal')
        ->toContain('denialReason')
        ->toContain('openApproveConfirm(payment)')
        ->toContain('openDenyReasonDialog(payment)')
        ->toContain("submitPaymentStatusAction('approve')")
        ->toContain("submitPaymentStatusAction('deny')")
        ->toContain('admin.payments.status.url(paymentId)')
        ->toContain("{{ __('Action') }}")
        ->toContain("__('Approve')")
        ->toContain("{{ __('Deny') }}");
});
