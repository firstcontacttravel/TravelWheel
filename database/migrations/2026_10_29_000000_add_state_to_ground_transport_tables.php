<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Car Hire and Pick up 'n' Drop off run per state. Each fleet car belongs to
 * a state (every existing car is in Lagos, the only state served so far),
 * and each booking records the state it was made for.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fleet_cars', function (Blueprint $table) {
            $table->string('state', 40)->default('lagos')->after('service_type')->index();
        });

        Schema::table('car_hires', function (Blueprint $table) {
            $table->string('state', 40)->nullable()->after('category');
        });

        Schema::table('transfers', function (Blueprint $table) {
            $table->string('state', 40)->nullable()->after('category');
        });

        // Every booking so far was made while only Lagos was served
        DB::table('car_hires')->update(['state' => 'lagos']);
        DB::table('transfers')->update(['state' => 'lagos']);
    }

    public function down(): void
    {
        Schema::table('fleet_cars', function (Blueprint $table) {
            $table->dropIndex(['state']);
            $table->dropColumn('state');
        });

        Schema::table('car_hires', function (Blueprint $table) {
            $table->dropColumn('state');
        });

        Schema::table('transfers', function (Blueprint $table) {
            $table->dropColumn('state');
        });
    }
};
