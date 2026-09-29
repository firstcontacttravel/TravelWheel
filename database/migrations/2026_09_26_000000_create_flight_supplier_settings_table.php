<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flight_supplier_settings', function (Blueprint $table): void {
            $table->id();
            // Matches config('flights.suppliers') and flight_bookings.supplier.
            $table->string('key', 30)->unique();
            $table->boolean('enabled')->default(false);
            // 1 is tried first.
            $table->unsignedSmallInteger('priority');
            $table->text('disabled_reason')->nullable();
            $table->foreignId('disabled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disabled_at')->nullable();
            // Set when an admin switches an API off for a fixed time.
            $table->timestamp('re_enable_at')->nullable();
            $table->timestamps();
        });

        Schema::create('flight_supplier_events', function (Blueprint $table): void {
            $table->id();
            $table->string('supplier_key', 30);
            // enabled, disabled, re_enabled_on_schedule, moved, registered
            $table->string('action', 30);
            $table->text('reason')->nullable();
            // Null when the system did it (a scheduled re-enable, a new API).
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('details')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['supplier_key', 'created_at'], 'flight_supplier_events_key_created_idx');
        });

        $now = now();

        // TravelNext has always been on. SkyLink takes whatever its old
        // SKYLINK_ENABLED switch says at the moment of deploy — the switch
        // moves into the admin without changing what customers see.
        $rows = [
            ['key' => 'travelnext', 'enabled' => true, 'priority' => 1],
            ['key' => 'skylink', 'enabled' => (bool) config('services.skylink.enabled', false), 'priority' => 2],
        ];

        foreach ($rows as $row) {
            DB::table('flight_supplier_settings')->insert($row + ['created_at' => $now, 'updated_at' => $now]);
            DB::table('flight_supplier_events')->insert([
                'supplier_key' => $row['key'],
                'action' => 'registered',
                'reason' => $row['enabled']
                    ? 'Moved into the admin, switched on.'
                    : 'Moved into the admin, switched off (it was off before).',
                'details' => json_encode(['enabled' => $row['enabled'], 'priority' => $row['priority']]),
                'created_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('flight_supplier_events');
        Schema::dropIfExists('flight_supplier_settings');
    }
};
