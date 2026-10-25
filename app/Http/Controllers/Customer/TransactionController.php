<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(): Response
    {
        $payments = Payment::query()
            ->whereHas('subscription', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->with('subscription.plan')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Customer/Pages/Transactions', [
            'payments' => $payments,
        ]);
    }
}
