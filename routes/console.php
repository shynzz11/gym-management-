<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('membership:check-expiry')
    ->dailyAt('00:01')
    ->withoutOverlapping();

Schedule::command('membership:activate-scheduled')
    ->dailyAt('00:05')
    ->withoutOverlapping();

Schedule::command('payroll:generate-periods')
    ->dailyAt('00:10')
    ->withoutOverlapping();
