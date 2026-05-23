<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_ticketing_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flight_booking_id')->constrained('flight_bookings')->cascadeOnDelete();
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('operation_type');
            $table->string('unique_id')->nullable()->index();
            $table->string('ptr_unique_id')->nullable()->index();
            $table->string('status')->default('pending')->index();
            $table->text('error_message')->nullable();
            $table->text('admin_note')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->json('preflight_trip_details')->nullable();
            $table->timestamps();

            $table->index(['operation_type', 'status']);
            $table->index(['flight_booking_id', 'operation_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_ticketing_requests');
    }
};
