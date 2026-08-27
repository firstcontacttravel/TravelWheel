<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('travel_flex_applications', function (Blueprint $table) {
            $table->string('fees_status')->default('not_due')->after('deposit_paid_at');
            $table->string('fees_reference')->nullable()->after('fees_status');
            $table->timestamp('fees_paid_at')->nullable()->after('fees_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('travel_flex_applications', function (Blueprint $table) {
            $table->dropColumn(['fees_status', 'fees_reference', 'fees_paid_at']);
        });
    }
};
