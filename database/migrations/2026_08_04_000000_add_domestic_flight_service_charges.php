<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            'economy' => 20000,
            'premium_economy' => 30000,
            'business' => 40000,
            'first' => 50000,
        ];

        foreach ($defaults as $cabin => $amount) {
            DB::table('flight_service_charges')->insert([
                'route_category' => 'domestic',
                'cabin' => $cabin,
                'amount' => $amount,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('flight_service_charges')->where('route_category', 'domestic')->delete();
    }
};
