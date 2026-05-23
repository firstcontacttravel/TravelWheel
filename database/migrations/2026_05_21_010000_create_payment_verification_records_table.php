<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_verification_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flight_booking_id')->constrained('flight_bookings')->cascadeOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('previous_payment_status')->nullable();
            $table->string('new_payment_status')->nullable();
            $table->string('payment_reference')->nullable();
            $table->decimal('amount_received', 14, 2)->nullable();
            $table->string('currency', 10)->nullable();
            $table->text('verification_note')->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamps();

            $table->index(['action', 'created_at']);
            $table->index(['payment_reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_verification_records');
    }
};
