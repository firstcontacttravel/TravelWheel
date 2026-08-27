<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_request', function (Blueprint $table): void {
            $table->id();
            $table->string('request_type');
            $table->string('booking_source');
            $table->string('name_on_ticket')->nullable();
            $table->string('airline_reference')->nullable();
            $table->string('airline_category')->nullable();
            $table->string('airline')->nullable();
            $table->string('trip_type')->nullable();
            $table->date('travel_date_oneway')->nullable();
            $table->date('departure_date')->nullable();
            $table->date('return_date')->nullable();
            $table->string('route_from')->nullable();
            $table->string('route_to')->nullable();
            $table->string('preferred_time')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('payment_option');
            $table->text('additional_info')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_request');
    }
};
