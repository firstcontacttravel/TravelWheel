<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flight_bookings', function (Blueprint $table) {
            $table->string('payment_reference')->nullable()->unique()->after('payment_method');
            $table->string('payment_gateway')->nullable()->after('payment_reference');
            $table->string('payment_flow')->nullable()->after('payment_gateway');
            $table->decimal('payment_amount', 14, 2)->nullable()->after('payment_flow');
            $table->string('payment_currency', 10)->nullable()->after('payment_amount');
            $table->timestamp('payment_verified_at')->nullable()->after('payment_currency');
            $table->json('payment_gateway_response')->nullable()->after('payment_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('flight_bookings', function (Blueprint $table) {
            $table->dropUnique('flight_bookings_payment_reference_unique');
            $table->dropColumn([
                'payment_reference',
                'payment_gateway',
                'payment_flow',
                'payment_amount',
                'payment_currency',
                'payment_verified_at',
                'payment_gateway_response',
            ]);
        });
    }
};
