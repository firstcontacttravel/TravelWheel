<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lounges', function (Blueprint $table) {
            $table->string('service', 50)->nullable()->after('airport');
        });
    }

    public function down(): void
    {
        Schema::table('lounges', function (Blueprint $table) {
            $table->dropColumn('service');
        });
    }
};
