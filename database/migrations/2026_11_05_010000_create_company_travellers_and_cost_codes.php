<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_travellers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('title', 10);
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100);
            $table->string('gender', 1);
            $table->string('nationality', 2);
            $table->string('email', 190)->nullable();
            $table->string('phone', 30)->nullable();
            // Encrypted, so text rather than date or string.
            $table->text('dob');
            $table->text('frequent_flyer_number')->nullable();
            $table->text('passport')->nullable();
            // Only what the pages need, so they never have to decrypt the passport.
            $table->string('passport_last4', 4)->nullable();
            $table->date('passport_expires_on')->nullable();
            $table->foreignId('created_by_company_user_id')->nullable()->constrained('company_users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'last_name']);
        });

        Schema::table('flight_bookings', function (Blueprint $table) {
            $table->string('cost_code', 100)->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('flight_bookings', function (Blueprint $table) {
            $table->dropIndex(['cost_code']);
            $table->dropColumn('cost_code');
        });

        Schema::dropIfExists('company_travellers');
    }
};
