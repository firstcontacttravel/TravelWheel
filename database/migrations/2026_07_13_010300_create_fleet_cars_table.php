<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_cars', function (Blueprint $table) {
            $table->id();
            $table->string('service_type');
            $table->string('vehicle_type');
            $table->string('category')->nullable();
            $table->string('car_name');
            $table->string('passengers')->nullable();
            $table->json('features')->nullable();
            $table->json('images')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_cars');
    }
};
