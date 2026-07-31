<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insurance_purchases', function (Blueprint $table): void {
            $table->string('nin', 20)->nullable()->after('passport_no');
        });
    }

    public function down(): void
    {
        Schema::table('insurance_purchases', function (Blueprint $table): void {
            $table->dropColumn('nin');
        });
    }
};
