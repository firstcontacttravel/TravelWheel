<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flight_bookings', function (Blueprint $table) {
            // Add booking_ref column as nullable first
            $table->string('booking_ref')->nullable()->after('id')->comment('Our internal booking reference (TW-XXXXXXXX)');
        });

        // Then add unique constraint (nullable unique allows multiple NULLs)
        Schema::table('flight_bookings', function (Blueprint $table) {
            $table->unique('booking_ref');
        });
    }

    public function down(): void
    {
        Schema::table('flight_bookings', function (Blueprint $table) {
            $table->dropUnique('flight_bookings_booking_ref_unique');
            $table->dropColumn('booking_ref');
        });
    }
};