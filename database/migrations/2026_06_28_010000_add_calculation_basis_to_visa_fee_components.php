<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visa_fee_components', function (Blueprint $table) {
            $table->string('calculation_basis')->default('per_traveler')->after('traveler_type');
        });
    }

    public function down(): void
    {
        Schema::table('visa_fee_components', function (Blueprint $table) {
            $table->dropColumn('calculation_basis');
        });
    }
};
