<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_hires', function (Blueprint $table) {
            $table->id();
            $table->string('car_type');
            $table->string('category');
            $table->string('car_model', 100)->nullable();
            $table->string('full_name');
            $table->string('email');
            $table->string('phone_number');
            $table->unsignedSmallInteger('passengers')->default(1);
            $table->string('pickup_location');
            $table->string('dropoff_location');
            $table->date('pickup_date');
            $table->string('pickup_time');
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->decimal('rental_hours', 6, 2)->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('payment_option');
            $table->string('payment_reference')->unique();
            $table->string('payment_status')->default('pending');
            $table->boolean('driver_assigned')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_hires');
    }
};
