<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The automatic cut-off (circuit breaker) for each flight API: its state,
 * and optional per-API thresholds. A null threshold uses the default in
 * config/flights.php. See App\Services\Flights\FlightSupplierBreaker.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flight_supplier_settings', function (Blueprint $table): void {
            // Off here means the API is never paused automatically, only by hand.
            $table->boolean('auto_cutoff')->default(true)->after('re_enable_at');

            // 'closed' — calls flow normally. 'open' — paused until
            // breaker_retry_at; after that, trial calls decide.
            $table->string('breaker_state', 12)->default('closed')->after('auto_cutoff');
            $table->timestamp('breaker_opened_at')->nullable()->after('breaker_state');
            $table->timestamp('breaker_retry_at')->nullable()->after('breaker_opened_at');
            $table->text('breaker_reason')->nullable()->after('breaker_retry_at');
            $table->unsignedTinyInteger('breaker_trial_successes')->default(0)->after('breaker_reason');

            $table->unsignedSmallInteger('cutoff_min_calls')->nullable()->after('breaker_trial_successes');
            $table->unsignedTinyInteger('cutoff_failure_percent')->nullable()->after('cutoff_min_calls');
            $table->unsignedSmallInteger('cutoff_window_minutes')->nullable()->after('cutoff_failure_percent');
            $table->unsignedSmallInteger('cutoff_pause_minutes')->nullable()->after('cutoff_window_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('flight_supplier_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'auto_cutoff',
                'breaker_state',
                'breaker_opened_at',
                'breaker_retry_at',
                'breaker_reason',
                'breaker_trial_successes',
                'cutoff_min_calls',
                'cutoff_failure_percent',
                'cutoff_window_minutes',
                'cutoff_pause_minutes',
            ]);
        });
    }
};
