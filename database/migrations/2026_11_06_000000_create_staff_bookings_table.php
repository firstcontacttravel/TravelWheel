<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A booking staff made from the admin for a customer. The booking itself
 * stays in its product's own table, with its own payment status; this records
 * who made it and how it's being paid.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_bookings', function (Blueprint $table) {
            $table->id();
            $table->morphs('bookable');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            // Lets the customer pay through a link, without signing in
            $table->string('pay_token', 64)->unique();
            $table->timestamp('link_sent_at')->nullable();
            // A payment Finance or the CEO recorded by hand
            $table->string('recorded_method', 30)->nullable();
            $table->string('recorded_reference')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_bookings');
    }
};
