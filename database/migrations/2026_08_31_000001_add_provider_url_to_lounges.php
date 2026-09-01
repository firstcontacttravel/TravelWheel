<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('lounges', 'provider_url')) {
            return;
        }

        Schema::table('lounges', function (Blueprint $table): void {
            $table->text('provider_url')->nullable()->after('provider_currency');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('lounges', 'provider_url')) {
            return;
        }

        Schema::table('lounges', function (Blueprint $table): void {
            $table->dropColumn('provider_url');
        });
    }
};
