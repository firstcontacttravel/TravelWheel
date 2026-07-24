<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flight_bookings', function (Blueprint $table): void {
            $table->timestamp('payment_initializing_at')->nullable()->after('payment_verified_at');
            $table->timestamp('ticketing_started_at')->nullable()->after('ticket_ordered_at');
            $table->timestamp('last_reconciled_at')->nullable()->after('ticketing_started_at');
            $table->text('reconciliation_note')->nullable()->after('last_reconciled_at');

            $table->index(['payment_status', 'created_at'], 'flight_payment_status_created_idx');
            $table->index(['booking_status', 'created_at'], 'flight_booking_status_created_idx');
            $table->index(['ticket_ordered', 'payment_status'], 'flight_ticket_payment_idx');
            $table->index('tkt_time_limit', 'flight_hold_deadline_idx');
        });

        Schema::create('notification_outboxes', function (Blueprint $table): void {
            $table->id();
            $table->string('kind', 80);
            $table->string('recipient');
            $table->json('cc')->nullable();
            $table->nullableMorphs('related');
            $table->json('payload')->nullable();
            $table->string('unique_key')->nullable()->unique();
            $table->string('status', 30)->default('pending')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('available_at')->nullable()->index();
            $table->timestamp('last_attempted_at')->nullable();
            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamp('failed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });

        Schema::create('system_heartbeats', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_heartbeats');
        Schema::dropIfExists('notification_outboxes');

        Schema::table('flight_bookings', function (Blueprint $table): void {
            $table->dropIndex('flight_payment_status_created_idx');
            $table->dropIndex('flight_booking_status_created_idx');
            $table->dropIndex('flight_ticket_payment_idx');
            $table->dropIndex('flight_hold_deadline_idx');
            $table->dropColumn([
                'payment_initializing_at',
                'ticketing_started_at',
                'last_reconciled_at',
                'reconciliation_note',
            ]);
        });
    }
};
