<?php

use App\Models\FlightSearch;
use App\Models\FlightSearchResult;
use App\Services\TravelFlexApplicationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('travelflex:send-repayment-reminders {--dry-run : List what would send, and send nothing}', function () {
    $service = app(TravelFlexApplicationService::class);

    if ($this->option('dry-run')) {
        $preview = $service->previewRepaymentReminders();
        $rows = fn (array $items): array => array_map(fn (array $row): array => [
            $row['application']->booking_ref,
            $row['instalment']['label'] ?? '-',
            $row['due_date']->format('Y-m-d'),
            $row['days_until_due'],
            $row['stage'] ?? '-',
        ], $items);
        $headers = ['Booking', 'Instalment', 'Due date', 'Days until due', 'Stage'];

        $this->info('Would send: '.count($preview['send']));
        $this->table($headers, $rows($preview['send']));
        $this->info('Would alert Fast Credit and TravelWheel: '.count($preview['alerts']));
        $this->table($headers, $rows($preview['alerts']));
        $this->info('No more reminders, already '.TravelFlexApplicationService::REMINDERS_STOP_AFTER_DAYS.'+ days overdue: '.count($preview['too_late']));
        $this->table($headers, $rows($preview['too_late']));

        return;
    }

    $sent = $service->sendRepaymentReminders();

    $this->info("TravelFlex repayment reminders sent: {$sent['reminders']}. Overdue alerts sent: {$sent['alerts']}.");
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

Schedule::command('flights:re-enable-suppliers')
    ->everyMinute()
    ->withoutOverlapping(2);

// Parallel searches and each API's results are kept for a couple of hours at
// most (config/flights.php); clear the expired ones so the tables stay small.
Schedule::command('model:prune', ['--model' => [FlightSearch::class, FlightSearchResult::class]])
    ->hourly()
    ->withoutOverlapping(30);

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

// Linear's free plan caps active issues; tell the CEO before escalations
// start failing to reach it. Does nothing without LINEAR_API_KEY.
Schedule::command('linear:status --warn')
    ->dailyAt('08:00')
    ->timezone('Africa/Lagos')
    ->withoutOverlapping(10);

// Workflow deadlines: due soon, missed, and long missed (to the CEO). Each
// alert goes out once per step.
Schedule::command('workflow:check-deadlines')
    ->everyFiveMinutes()
    ->withoutOverlapping(10);
