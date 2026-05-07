<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flight_bookings', function (Blueprint $table) {
            $table->decimal('payment_charged_amount', 14, 2)->nullable()->after('payment_amount');
            $table->boolean('payment_receipt_sent')->default(false)->after('pending_email_sent');
        });
    }

    public function down(): void
    {
        Schema::table('flight_bookings', function (Blueprint $table) {
            $table->dropColumn([
                'payment_charged_amount',
                'payment_receipt_sent',
            ]);
        });
    }
};
