<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained()->onDelete('cascade');
            $table->foreignId('car_hire_id')->nullable()->constrained('car_hires')->onDelete('cascade');
            $table->foreignId('transfer_id')->nullable()->constrained('transfers')->onDelete('cascade');
            $table->enum('booking_type', ['car_hire', 'transfer']);
            $table->string('car_model');
            $table->string('car_colour');
            $table->string('plate_number');
            $table->json('car_images')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('email_sent_at')->nullable();
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_assignments');
    }
};
