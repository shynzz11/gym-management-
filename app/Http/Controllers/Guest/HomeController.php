<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index(): \Inertia\Response
    {
        return Inertia::render('Guest/LandingPage', [
            'plans' => Plan::query()
                ->where('is_active', true)
                ->latest()
                ->get(),
            'fullWidth' => true,
        ]);
    }
}
