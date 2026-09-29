<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fuel consumption per vehicle type, so the fuel rate per minute follows
     * the pump price: fuel/min = pump price × litres per hour ÷ 60.
     * Backfilled from the existing per-minute rates, which were set against
     * a ₦1,400 pump price; fuel/min is then recalculated at the current pump
     * price, so any rise already entered takes effect.
     */
    private const REFERENCE_PUMP_PRICE = 1400;

    public function up(): void
    {
        Schema::table('transport_rates', function (Blueprint $table) {
            $table->decimal('fuel_litres_per_hour', 8, 3)->default(0)->after('fuel_pump_price');
        });

        foreach (DB::table('transport_rates')->get() as $rate) {
            $litresPerHour = round($rate->transfer_fuel_rate_per_minute * 60 / self::REFERENCE_PUMP_PRICE, 3);

            DB::table('transport_rates')->where('id', $rate->id)->update([
                'fuel_litres_per_hour' => $litresPerHour,
                'transfer_fuel_rate_per_minute' => (int) round($rate->fuel_pump_price * $litresPerHour / 60),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('transport_rates', function (Blueprint $table) {
            $table->dropColumn('fuel_litres_per_hour');
        });
    }
};
