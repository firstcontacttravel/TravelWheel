<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Hold and pay later" for an API that can't hold seats (SkyLink). The hold
 * is ours alone: nothing is reserved with the supplier until payment.
 * hold_type tells these apart from TravelFlex's platform holds, which follow
 * their own approval flow.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flight_bookings', function (Blueprint $table) {
            $table->string('hold_type', 30)->nullable()->after('tkt_time_limit');
            $table->timestamp('hold_reminder_sent_at')->nullable()->after('hold_type');
        });
    }

    public function down(): void
    {
        Schema::table('flight_bookings', function (Blueprint $table) {
            $table->dropColumn(['hold_type', 'hold_reminder_sent_at']);
        });
    }
};
