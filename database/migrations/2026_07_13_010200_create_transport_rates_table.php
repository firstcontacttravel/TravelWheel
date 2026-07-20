<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_rates', function (Blueprint $table) {
            $table->id();
            $table->string('vehicle_type');
            $table->unsignedInteger('price_regular')->default(0);
            $table->unsignedInteger('price_standard')->default(0);
            $table->unsignedInteger('price_executive')->default(0);
            $table->unsignedInteger('fuel_rate_per_km')->default(1300);
            $table->unsignedInteger('hourly_rate')->default(5000);
            $table->unsignedInteger('transfer_rate_per_km')->default(350);
            $table->timestamps();
        });

        $defaults = [
            ['vehicle_type' => 'saloon', 'price_regular' => 15000, 'price_standard' => 20000, 'price_executive' => 30000, 'fuel_rate_per_km' => 1300, 'hourly_rate' =>  5000, 'transfer_rate_per_km' =>  350],
            ['vehicle_type' => 'suv',    'price_regular' => 30000, 'price_standard' => 40000, 'price_executive' => 60000, 'fuel_rate_per_km' => 1300, 'hourly_rate' =>  8000, 'transfer_rate_per_km' =>  500],
            ['vehicle_type' => 'van',    'price_regular' => 40000, 'price_standard' => 55000, 'price_executive' => 75000, 'fuel_rate_per_km' => 1300, 'hourly_rate' => 10000, 'transfer_rate_per_km' =>  650],
            ['vehicle_type' => 'bus',    'price_regular' => 40000, 'price_standard' => 55000, 'price_executive' => 75000, 'fuel_rate_per_km' => 1300, 'hourly_rate' => 15000, 'transfer_rate_per_km' =>  900],
            ['vehicle_type' => 'luxury', 'price_regular' => 40000, 'price_standard' => 55000, 'price_executive' => 75000, 'fuel_rate_per_km' => 1300, 'hourly_rate' => 20000, 'transfer_rate_per_km' => 1200],
        ];

        foreach ($defaults as $row) {
            DB::table('transport_rates')->insert(array_merge($row, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_rates');
    }
};
