<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visa_travelers', function (Blueprint $table) {
            $table->string('applicant_type')->nullable()->after('traveler_type')->index();
        });
    }

    public function down(): void
    {
        Schema::table('visa_travelers', function (Blueprint $table) {
            $table->dropColumn('applicant_type');
        });
    }
};
