<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Same route and cabin table as flight_service_charges. A row with no
        // company is the default corporate rate; a missing row falls back.
        Schema::create('company_service_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('route_category', 30);
            $table->string('cabin', 30);
            $table->decimal('amount', 14, 2);
            $table->timestamps();

            $table->unique(['company_id', 'route_category', 'cabin'], 'company_charge_unique');
        });

        // The per-API search requests run with no session, so this is the only
        // way they can know whose rate applies.
        Schema::table('flight_searches', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::table('flight_bookings', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('company_user_id')->nullable()->constrained('company_users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('flight_bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_user_id');
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('flight_searches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::dropIfExists('company_service_charges');
    }
};
