<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_users', function (Blueprint $table) {
            // The stand-in that staff act through when booking for a company.
            // It can never sign in, and it doesn't appear on the team page.
            $table->boolean('is_system')->default(false)->index();
        });

        Schema::table('flight_bookings', function (Blueprint $table) {
            // The member of staff who really made a booking for a company.
            $table->foreignId('booked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('flight_bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('booked_by_user_id');
        });

        Schema::table('company_users', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });
    }
};
