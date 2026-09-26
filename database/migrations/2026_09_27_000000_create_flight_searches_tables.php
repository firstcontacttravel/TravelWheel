<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Parallel search keeps each search, and each API's answer to it, here
 * rather than in the session: the per-API requests run side by side and
 * without a session (see FlightSupplierSearchController), and a session
 * written by several concurrent requests loses whichever write lands first.
 *
 * Rows expire and are pruned hourly (model:prune).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flight_searches', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            // The validated search form.
            $table->json('criteria');
            $table->timestamp('created_at')->nullable();
            // Nullable only because MySQL/MariaDB in strict mode reject a NOT NULL
            // timestamp with no default; FlightSearchStore always sets it.
            $table->timestamp('expires_at')->nullable()->index();
        });

        Schema::create('flight_search_results', function (Blueprint $table): void {
            $table->id();
            $table->uuid('flight_search_id');
            $table->string('supplier', 30);
            // Marked-up, matchKey-tagged flights: gzip-compressed JSON, base64.
            // A round-trip result set runs to ~1 MB raw; compressed it stays
            // far below any database packet limit.
            $table->longText('flights');
            $table->json('meta')->nullable();
            $table->unsignedInteger('flight_count')->default(0);
            $table->timestamp('created_at')->nullable();
            // Nullable only because MySQL/MariaDB in strict mode reject a NOT NULL
            // timestamp with no default; FlightSearchStore always sets it.
            $table->timestamp('expires_at')->nullable()->index();

            $table->unique(['flight_search_id', 'supplier']);
            $table->foreign('flight_search_id')->references('id')->on('flight_searches')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flight_search_results');
        Schema::dropIfExists('flight_searches');
    }
};
