<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flight_service_charges', function (Blueprint $table): void {
            $table->id();
            $table->string('route_category', 40);
            $table->string('cabin', 40);
            $table->decimal('amount', 14, 2)->default(0);
            $table->timestamps();
            $table->unique(['route_category', 'cabin']);
        });

        $defaults = [
            'from_nigeria' => [
                'economy' => 30000,
                'premium_economy' => 60000,
                'business' => 100000,
                'first' => 150000,
            ],
            'touches_nigeria' => [
                'economy' => 70000,
                'premium_economy' => 120000,
                'business' => 200000,
                'first' => 300000,
            ],
            'not_nigeria' => [
                'economy' => 100000,
                'premium_economy' => 170000,
                'business' => 240000,
                'first' => 350000,
            ],
        ];

        foreach ($defaults as $routeCategory => $cabins) {
            foreach ($cabins as $cabin => $amount) {
                DB::table('flight_service_charges')->insert([
                    'route_category' => $routeCategory,
                    'cabin' => $cabin,
                    'amount' => $amount,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('flight_service_charges');
    }
};
