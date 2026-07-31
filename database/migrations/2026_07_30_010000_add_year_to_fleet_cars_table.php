<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fleet_cars', function (Blueprint $table): void {
            $table->unsignedSmallInteger('year')->nullable()->after('vehicle_type');
        });
    }

    public function down(): void
    {
        Schema::table('fleet_cars', function (Blueprint $table): void {
            $table->dropColumn('year');
        });
    }
};
