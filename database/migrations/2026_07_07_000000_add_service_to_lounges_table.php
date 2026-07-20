<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lounges') || Schema::hasColumn('lounges', 'service')) {
            return;
        }

        Schema::table('lounges', function (Blueprint $table) {
            $table->string('service', 50)->nullable()->after('airport');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('lounges') || ! Schema::hasColumn('lounges', 'service')) {
            return;
        }

        Schema::table('lounges', function (Blueprint $table) {
            $table->dropColumn('service');
        });
    }
};
