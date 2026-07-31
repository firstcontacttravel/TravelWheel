<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transfers', function (Blueprint $table): void {
            $table->string('category')->nullable()->after('vehicle_name');
            $table->unsignedInteger('duration_mins')->nullable()->after('distance_km');
            $table->decimal('base_fare', 12, 2)->nullable()->after('amount');
            $table->decimal('tear_wear_amount', 12, 2)->nullable()->after('base_fare');
            $table->decimal('fuel_amount', 12, 2)->nullable()->after('tear_wear_amount');
            $table->decimal('admin_fee_amount', 12, 2)->nullable()->after('fuel_amount');
        });
    }

    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table): void {
            $table->dropColumn([
                'category',
                'duration_mins',
                'base_fare',
                'tear_wear_amount',
                'fuel_amount',
                'admin_fee_amount',
            ]);
        });
    }
};
