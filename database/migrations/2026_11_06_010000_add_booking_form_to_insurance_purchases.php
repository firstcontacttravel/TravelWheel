<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A policy staff book for a customer is issued once they pay, so every
 * traveller's details are kept with it until then (a family has a second adult
 * and children the other columns don't hold).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insurance_purchases', function (Blueprint $table): void {
            $table->json('booking_form')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('insurance_purchases', function (Blueprint $table): void {
            $table->dropColumn('booking_form');
        });
    }
};
