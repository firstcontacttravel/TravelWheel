<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flight_bookings', function (Blueprint $table) {
            // Store selected extra services (baggage, meals, etc.) as JSON
            $table->json('extra_services_snapshot')->nullable()->after('flight_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('flight_bookings', function (Blueprint $table) {
            $table->dropColumn('extra_services_snapshot');
        });
    }
};
