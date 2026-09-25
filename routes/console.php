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
    ->withoutOverlapping(120);

Schedule::command('operations:heartbeat scheduler')
    ->everyMinute()
    ->withoutOverlapping(2);

Schedule::command('notifications:process-outbox --limit=5')
    ->everyMinute()
    ->withoutOverlapping(5);

Schedule::command('queue:work --stop-when-empty --sleep=1 --tries=3 --timeout=180 --max-time=50')
    ->everyMinute()
    ->withoutOverlapping(5);

Schedule::command('flights:reconcile --limit=200')
    ->everyFiveMinutes()
    ->withoutOverlapping(10);

Schedule::command('reports:sync')
    ->everyFiveMinutes()
    ->withoutOverlapping(15);

Schedule::command('reports:send-scheduled')
    ->hourly()
    ->timezone('Africa/Lagos')
    ->withoutOverlapping(120);

// The SkyLink JWT caches for 13 minutes against a 15-minute lifetime; refresh
// it just inside that window so a customer's search never pays for the login
// round trip on top of SkyLink's own ~7s average.
Schedule::command('skylink:warm-token')
    ->everyTenMinutes()
    ->withoutOverlapping(5);
