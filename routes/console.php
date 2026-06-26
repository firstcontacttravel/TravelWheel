<?php

use App\Services\TravelFlexApplicationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('travelflex:send-repayment-reminders', function () {
    $sent = app(TravelFlexApplicationService::class)->sendRepaymentReminders();

    $this->info("TravelFlex repayment reminders sent: {$sent}");
})->purpose('Send TravelFlex repayment reminder emails');

Schedule::command('travelflex:send-repayment-reminders')
    ->dailyAt('08:00')
    ->timezone('Africa/Lagos')
    ->withoutOverlapping();
