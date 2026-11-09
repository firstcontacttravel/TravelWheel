<?php

namespace App\Console\Commands;

use App\Models\FlightBooking;
use App\Services\DurableMailService;
use App\Services\Flights\FlightPlatformHold;
use Illuminate\Console\Command;
use Throwable;

/**
 * One reminder per "hold and pay later" booking, a few hours before it
 * closes. A hold placed less than an hour before that window (a short hold
 * set in config) is skipped: the customer has just had the first email.
 */
class RemindPayLaterHolds extends Command
{
    protected $signature = 'flights:remind-pay-later-holds {--limit=100}';

    protected $description = 'Email customers whose "hold and pay later" booking closes soon';

    public function handle(DurableMailService $mail): int
    {
        $hours = max(1, (int) config('flights.platform_hold.pay_later_reminder_hours', 3));
        $sent = 0;

        FlightBooking::query()
            ->where('hold_type', FlightPlatformHold::PAY_LATER)
            ->where('booking_status', 'on_hold')
            ->where('payment_status', 'pending')
            ->whereNull('hold_reminder_sent_at')
            ->whereBetween('tkt_time_limit', [now()->addMinutes(30), now()->addHours($hours)])
            ->where('created_at', '<', now()->subHour())
            ->limit(max(1, (int) $this->option('limit')))
            ->get()
            ->each(function (FlightBooking $booking) use ($mail, &$sent): void {
                if (blank($booking->contact_email)) {
                    return;
                }

                // Marked first, so a mail failure can't send it twice; the
                // outbox keeps and retries an undelivered one.
                $booking->update(['hold_reminder_sent_at' => now()]);

                try {
                    $mail->sendNowOrStore(
                        DurableMailService::FLIGHT_PENDING,
                        (string) $booking->contact_email,
                        $booking,
                        ['method' => 'pay_later_reminder'],
                        "flight-pending:{$booking->id}:pay_later_reminder",
                    );
                    $sent++;
                } catch (Throwable $exception) {
                    report($exception);
                }
            });

        $this->line("pay-later reminders: {$sent}");

        return self::SUCCESS;
    }
}
