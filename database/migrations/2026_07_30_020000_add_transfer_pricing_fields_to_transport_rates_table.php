<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transport_rates', function (Blueprint $table): void {
            $table->unsignedInteger('transfer_base_regular')->default(0)->after('transfer_rate_per_km');
            $table->unsignedInteger('transfer_base_standard')->default(0)->after('transfer_base_regular');
            $table->unsignedInteger('transfer_base_executive')->default(0)->after('transfer_base_standard');
            $table->unsignedInteger('transfer_fuel_rate_per_minute')->default(0)->after('transfer_base_executive');
            $table->decimal('transfer_admin_fee_percent', 5, 2)->default(10.00)->after('transfer_fuel_rate_per_minute');
        });

        // Seed sensible starting values per vehicle type. Saloon matches the
        // client-provided example; other types are scaled up proportionally
        // to their existing car-hire base fares. Admin can adjust any of
        // these afterwards from the Rates admin screen.
        $defaults = [
            'saloon' => ['base' => [20000, 35000, 50000], 'fuel' => 217],
            'suv'    => ['base' => [30000, 50000, 70000], 'fuel' => 280],
            'van'    => ['base' => [35000, 58000, 80000], 'fuel' => 320],
            'bus'    => ['base' => [35000, 58000, 80000], 'fuel' => 350],
            'luxury' => ['base' => [45000, 70000, 100000], 'fuel' => 400],
        ];

        foreach ($defaults as $vehicleType => $rates) {
            DB::table('transport_rates')
                ->where('vehicle_type', $vehicleType)
                ->update([
                    'transfer_base_regular' => $rates['base'][0],
                    'transfer_base_standard' => $rates['base'][1],
                    'transfer_base_executive' => $rates['base'][2],
                    'transfer_fuel_rate_per_minute' => $rates['fuel'],
                    'transfer_admin_fee_percent' => 10.00,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('transport_rates', function (Blueprint $table): void {
            $table->dropColumn([
                'transfer_base_regular',
                'transfer_base_standard',
                'transfer_base_executive',
                'transfer_fuel_rate_per_minute',
                'transfer_admin_fee_percent',
            ]);
        });
    }
};
