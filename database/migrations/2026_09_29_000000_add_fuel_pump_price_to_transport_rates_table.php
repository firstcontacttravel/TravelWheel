<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Current fuel pump price (₦ per litre) shown to customers in the Car
     * Hire price breakdown. Same for every vehicle type — set from the
     * Transport Rates admin page, which updates all rows together.
     */
    public function up(): void
    {
        Schema::table('transport_rates', function (Blueprint $table) {
            $table->unsignedInteger('fuel_pump_price')->default(1400)->after('transfer_fuel_rate_per_minute');
        });
    }

    public function down(): void
    {
        Schema::table('transport_rates', function (Blueprint $table) {
            $table->dropColumn('fuel_pump_price');
        });
    }
};
