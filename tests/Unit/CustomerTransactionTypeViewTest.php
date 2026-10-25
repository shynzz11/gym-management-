<?php

test('customer transactions view shows new and renew transaction type badges', function () {
    $transactionsPage = file_get_contents(__DIR__.'/../../resources/js/Pages/Customer/Pages/Transactions.vue');
    $dashboardPage = file_get_contents(__DIR__.'/../../resources/js/Pages/Customer/Pages/Dashboard.vue');

    expect($transactionsPage)
        ->toContain("{{ __('Type') }}")
        ->toContain('formatTransactionType')
        ->toContain('transactionTypeClass')
        ->toContain('payment.transaction_type')
        ->toContain('paymentStatusClass')
        ->toContain('paymentStatusTooltip')
        ->toContain('<AppTooltip :text="paymentStatusTooltip(payment)">')
        ->not->toContain(':title="paymentStatusTooltip(payment)"');

    expect($dashboardPage)
        ->toContain('paymentStatusClass')
        ->toContain('paymentStatusTooltip')
        ->toContain(':title="paymentStatusTooltip(payment)"');
});
