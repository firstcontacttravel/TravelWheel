<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flight_bookings', function (Blueprint $table) {
            $table->id();

            // ── Booking reference ──────────────────────────────────────────
            $table->string('unique_id')->nullable()->index();          // TR31072022 from API
            $table->string('fare_source_code')->nullable();
            $table->string('session_id')->nullable();

            // ── Fare & trip info ───────────────────────────────────────────
            $table->string('fare_type')->nullable();                   // WebFare | Public | Private
            $table->string('trip_type')->nullable();                   // oneway | return | multi
            $table->string('route')->nullable();                       // e.g. LOS → LHR
            $table->string('airline')->nullable();
            $table->string('cabin')->nullable();
            $table->string('currency', 10)->default('NGN');
            $table->decimal('total_price', 14, 2)->default(0);

            // ── Booking status ─────────────────────────────────────────────
            // booking_status: on_hold | confirmed | ticketed | failed | cancelled
            $table->string('booking_status')->default('on_hold');
            // payment_status: pending | awaiting_bank_transfer | paid | failed
            $table->string('payment_status')->default('pending');
            // payment_method: gateway | bank_transfer | flex | null
            $table->string('payment_method')->nullable();

            // ── Ticketing deadline (non-LCC hold bookings) ─────────────────
            $table->dateTime('tkt_time_limit')->nullable();

            // ── Ticket order result ────────────────────────────────────────
            $table->boolean('ticket_ordered')->default(false);
            $table->dateTime('ticket_ordered_at')->nullable();

            // ── Contact ────────────────────────────────────────────────────
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();

            // ── Passenger count ────────────────────────────────────────────
            $table->unsignedTinyInteger('adult_count')->default(1);
            $table->unsignedTinyInteger('child_count')->default(0);
            $table->unsignedTinyInteger('infant_count')->default(0);

            // ── Full API payloads (for debugging / audit) ──────────────────
            $table->json('booking_api_response')->nullable();
            $table->json('ticket_api_response')->nullable();
            $table->json('passengers_snapshot')->nullable();           // passenger data at time of booking
            $table->json('flight_snapshot')->nullable();               // mapped flight data

            // ── Bank transfer notification ─────────────────────────────────
            $table->string('bank_transfer_reference')->nullable();     // user-supplied ref
            $table->dateTime('bank_transfer_notified_at')->nullable(); // when user clicked "I have paid"

            // ── Notifications ──────────────────────────────────────────────
            $table->boolean('confirmation_email_sent')->default(false);
            $table->boolean('pending_email_sent')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flight_bookings');
    }
};