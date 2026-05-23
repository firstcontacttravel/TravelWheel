<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticketing_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flight_booking_id')->constrained('flight_bookings')->cascadeOnDelete();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('previous_booking_status')->nullable();
            $table->string('new_booking_status')->nullable();
            $table->string('ticket_status')->nullable();
            $table->string('airline_pnr')->nullable();
            $table->string('unique_id')->nullable();
            $table->text('message')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamps();

            $table->index(['action', 'created_at']);
            $table->index(['ticket_status']);
            $table->index(['airline_pnr']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticketing_records');
    }
};
