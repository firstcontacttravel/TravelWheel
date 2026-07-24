<?php

namespace App\Console\Commands;

use App\Models\FlightBooking;
use App\Services\AdminTicketingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ReconcileFlightOperations extends Command
{
    protected $signature = 'flights:reconcile {--limit=200}';

    protected $description = 'Recover or flag stale flight payment, hold, and ticketing states';

    public function handle(): int
    {
        $lock = Cache::lock('flight-operations-reconciliation', 240);
        if (! $lock->get()) {
            $this->warn('Flight reconciliation is already running.');

            return self::SUCCESS;
        }

        $counts = [
            'expired_holds' => 0,
            'stale_initializations' => 0,
            'stuck_ticketing' => 0,
            'paid_unticketed_alerts' => 0,
            'verified_pending_alerts' => 0,
        ];

        try {
            $limit = max(1, (int) $this->option('limit'));

            FlightBooking::query()
                ->where('ticket_ordered', false)
                ->whereNotNull('tkt_time_limit')
                ->where('tkt_time_limit', '<', now())
                ->whereIn('booking_status', ['on_hold', 'pending_payment', 'ticketing_in_progress'])
                ->limit($limit)
                ->get()
                ->each(function (FlightBooking $booking) use (&$counts): void {
                    $booking->update([
                        'booking_status' => 'hold_expired_review',
                        'last_reconciled_at' => now(),
                        'reconciliation_note' => 'Airline hold expired before ticketing completed.',
                        'ticketing_started_at' => null,
                    ]);
                    $counts['expired_holds']++;
                });

            FlightBooking::query()
                ->whereNotNull('payment_initializing_at')
                ->where('payment_initializing_at', '<', now()->subMinutes(5))
                ->limit($limit)
                ->get()
                ->each(function (FlightBooking $booking) use (&$counts): void {
                    $booking->update([
                        'payment_initializing_at' => null,
                        'last_reconciled_at' => now(),
                        'reconciliation_note' => 'Stale payment initialization unlocked for safe retry.',
                    ]);
                    $counts['stale_initializations']++;
                });

            FlightBooking::query()
                ->where('booking_status', 'ticketing_in_progress')
                ->where('ticketing_started_at', '<', now()->subMinutes(10))
                ->limit($limit)
                ->get()
                ->each(function (FlightBooking $booking) use (&$counts): void {
                    $booking->update([
                        'booking_status' => 'ticketing_failed',
                        'ticketing_started_at' => null,
                        'last_reconciled_at' => now(),
                        'reconciliation_note' => 'Ticketing exceeded ten minutes and requires supplier-status review before retry.',
                    ]);
                    $counts['stuck_ticketing']++;
                    $this->alertSupport($booking, 'Ticketing became stuck and requires supplier-status review.');
                });

            FlightBooking::query()
                ->where('payment_status', 'paid')
                ->where('ticket_ordered', false)
                ->whereNotIn('booking_status', ['confirmed', 'cancelled', 'hold_expired_review'])
                ->limit($limit)
                ->get()
                ->each(function (FlightBooking $booking) use (&$counts): void {
                    $this->alertSupport($booking, 'Payment is marked paid but the booking is not ticketed.');
                    $booking->update(['last_reconciled_at' => now()]);
                    $counts['paid_unticketed_alerts']++;
                });

            FlightBooking::query()
                ->whereNotNull('payment_verified_at')
                ->whereNotIn('payment_status', ['paid', 'partially_paid'])
                ->limit($limit)
                ->get()
                ->each(function (FlightBooking $booking) use (&$counts): void {
                    $this->alertSupport($booking, 'Gateway verification exists but payment completion is not in a paid state.');
                    $booking->update([
                        'last_reconciled_at' => now(),
                        'reconciliation_note' => 'Verified payment requires completion review.',
                    ]);
                    $counts['verified_pending_alerts']++;
                });
        } finally {
            $lock->release();
        }

        foreach ($counts as $name => $count) {
            $this->line(str_replace('_', ' ', $name).": {$count}");
        }

        return self::SUCCESS;
    }

    private function alertSupport(FlightBooking $booking, string $message): void
    {
        try {
            app(AdminTicketingService::class)->sendFailureAlert($booking, $message);
        } catch (\Throwable) {
            // DurableMailService has already retained the failed notification.
        }
    }
}
