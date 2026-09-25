<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // SkyLink bookings reserved before their ticketing deadline was written to
    // tkt_time_limit still carry it inside booking_api_response — as
    // ticketDeadline, SkyLink's offset-less Lagos-time string. Copy it across
    // so the admin deadline column, the expired-hold filter and the system
    // health check see those bookings too. Only fills rows still null, so it
    // never overwrites a deadline the application has since written.
    public function up(): void
    {
        $supplierZone = (string) config('services.skylink.timezone', 'Africa/Lagos');
        $appZone = (string) config('app.timezone', 'UTC');

        DB::table('flight_bookings')
            ->select(['id', 'booking_api_response'])
            ->where('supplier', 'skylink')
            ->whereNull('tkt_time_limit')
            ->whereNotNull('booking_api_response')
            ->chunkById(200, function ($rows) use ($supplierZone, $appZone): void {
                foreach ($rows as $row) {
                    $response = json_decode((string) $row->booking_api_response, true);
                    $deadline = is_array($response)
                        ? ($response['ticketDeadline'] ?? data_get($response, 'raw.ticket_deadline'))
                        : null;

                    if (! is_string($deadline) || trim($deadline) === '') {
                        continue;
                    }

                    try {
                        $at = Carbon::parse($deadline, $supplierZone)->setTimezone($appZone);
                    } catch (\Throwable) {
                        continue;
                    }

                    DB::table('flight_bookings')
                        ->where('id', $row->id)
                        ->update(['tkt_time_limit' => $at->format('Y-m-d H:i:s')]);
                }
            });
    }

    public function down(): void
    {
        // Deliberately irreversible: which rows this filled is not recorded,
        // and clearing tkt_time_limit would also wipe deadlines the
        // application has written since.
    }
};
