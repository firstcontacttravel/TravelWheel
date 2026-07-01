<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flight_bookings', function (Blueprint $table): void {
            $table->json('itinerary_snapshot')->nullable()->after('ticket_api_response');
        });
    }

    public function down(): void
    {
        Schema::table('flight_bookings', function (Blueprint $table): void {
            $table->dropColumn('itinerary_snapshot');
        });
    }
};
