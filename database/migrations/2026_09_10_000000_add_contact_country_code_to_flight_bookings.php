<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // SkyLink's /api/flights/reserve requires country_code on primary_guest,
    // but _completeSkylinkReservation() runs later, from the SeerBit payment
    // callback — a separate request from book(), with no access to the
    // session's bookingContact any more. contact_email/contact_phone were
    // already persisted on the booking for exactly this reason; area_code
    // and country_code were not, so they were unavailable by reserve() time.
    // Found while fixing SkyLink's multi-passenger traveller mapping, which
    // had never been implemented at all (see FlightBookingController's
    // _buildSkylinkTravellers()).
    public function up(): void
    {
        Schema::table('flight_bookings', function (Blueprint $table) {
            $table->string('contact_area_code')->nullable()->after('contact_phone');
            $table->string('contact_country_code')->nullable()->after('contact_area_code');
        });
    }

    public function down(): void
    {
        Schema::table('flight_bookings', function (Blueprint $table) {
            $table->dropColumn(['contact_area_code', 'contact_country_code']);
        });
    }
};
