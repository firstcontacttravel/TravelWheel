<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('lounges', 'provider_airport_iata')) {
            return;
        }

        Schema::table('lounges', function (Blueprint $table): void {
            $table->string('provider_airport_iata', 3)->nullable()->after('provider_lounge_id');
            $table->index(['provider', 'provider_airport_iata']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('lounges', 'provider_airport_iata')) {
            return;
        }

        Schema::table('lounges', function (Blueprint $table): void {
            $table->dropIndex(['provider', 'provider_airport_iata']);
            $table->dropColumn('provider_airport_iata');
        });
    }
};
