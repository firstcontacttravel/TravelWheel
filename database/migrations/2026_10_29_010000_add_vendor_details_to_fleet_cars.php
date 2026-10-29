<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who supplies each fleet car and what they charge us, from the vendor's
 * rate card. Admin only: none of this is shown to customers, and customer
 * prices still come from the Car Hire / Transfer rate settings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fleet_cars', function (Blueprint $table) {
            $table->string('colour', 100)->nullable()->after('car_name');
            // An approved vendor from the partner registration form, when there is one
            $table->foreignId('vendor_application_id')->nullable()->after('is_active')
                ->constrained('vendor_applications')->nullOnDelete();
            $table->string('vendor_name')->nullable()->after('vendor_application_id');
            $table->string('vendor_contact_name')->nullable()->after('vendor_name');
            $table->string('vendor_phone', 60)->nullable()->after('vendor_contact_name');
            $table->string('vendor_email')->nullable()->after('vendor_phone');
            // [{hours, price, note}] e.g. 10 hrs: 100,000
            $table->json('vendor_hourly_rates')->nullable()->after('vendor_email');
            // [{service, price}] e.g. International Wing pickup: 60,000
            $table->json('vendor_trip_rates')->nullable()->after('vendor_hourly_rates');
            $table->text('vendor_notes')->nullable()->after('vendor_trip_rates');
        });
    }

    public function down(): void
    {
        Schema::table('fleet_cars', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vendor_application_id');
            $table->dropColumn([
                'colour', 'vendor_name', 'vendor_contact_name', 'vendor_phone', 'vendor_email',
                'vendor_hourly_rates', 'vendor_trip_rates', 'vendor_notes',
            ]);
        });
    }
};
