<?php

use App\Support\FlightDisplay;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('flight_bookings')
            ->where('trip_type', 'multi')
            ->select(['id', 'route', 'flight_snapshot'])
            ->orderBy('id')
            ->chunkById(100, function ($bookings): void {
                foreach ($bookings as $booking) {
                    $snapshot = is_string($booking->flight_snapshot)
                        ? json_decode($booking->flight_snapshot, true)
                        : (array) $booking->flight_snapshot;

                    if (! is_array($snapshot)) {
                        continue;
                    }

                    $route = FlightDisplay::route($snapshot);

                    if ($route !== '' && $route !== $booking->route) {
                        DB::table('flight_bookings')
                            ->where('id', $booking->id)
                            ->update(['route' => $route]);
                    }
                }
            });
    }

    public function down(): void
    {
        // This data repair is intentionally irreversible.
    }
};
