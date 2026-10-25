<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class RegistrationController extends Controller
{
    public function success(): Response
    {
        $user = auth()->user();

        $latestSubscription = $user?->subscriptions()
            ->with('plan')
            ->latest('created_at')
            ->first();

        return Inertia::render('Customer/Pages/RegistrationSuccess', [
            'subscription' => $latestSubscription,
        ]);
    }
}
