<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flight_bookings', function (Blueprint $table): void {
            // Internal/admin use only — never rendered to the customer (see
            // FlightMarkup/mapSearchResult's "source" field, which this mirrors).
            $table->string('supplier', 20)->default('travelnext')->after('fare_type');
            $table->index('supplier', 'flight_booking_supplier_idx');
        });
    }

    public function down(): void
    {
        Schema::table('flight_bookings', function (Blueprint $table): void {
            $table->dropIndex('flight_booking_supplier_idx');
            $table->dropColumn('supplier');
        });
    }
};
