<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lounge_service', function (Blueprint $table): void {
            // No DB-level FK: lounges.id (signed int, utf8) and this table
            // (utf8mb4) don't satisfy MySQL's exact-type-match requirement for
            // a constraint, and nothing else in this schema uses one either —
            // integrity is fine at the application level for a plain lookup.
            $table->integer('lounge_id')->nullable()->after('id');
            // 'loungepair' (or null for a locally managed lounge) — lets admin
            // spot bookings that still need to be placed on the provider's own
            // site, since we don't have a booking API for them.
            $table->string('provider', 50)->nullable()->after('lounge_name');
            $table->text('provider_url')->nullable()->after('provider');
        });
    }

    public function down(): void
    {
        Schema::table('lounge_service', function (Blueprint $table): void {
            $table->dropColumn(['lounge_id', 'provider', 'provider_url']);
        });
    }
};
