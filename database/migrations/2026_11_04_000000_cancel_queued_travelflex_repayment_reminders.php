<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Reminders queued under the old every-day-forever rule must not go out
     * after the deploy. The outbox only ever picks up pending and failed rows.
     */
    public function up(): void
    {
        DB::table('notification_outboxes')
            ->where('kind', 'travelflex_repayment')
            ->whereNull('sent_at')
            ->update([
                'status' => 'cancelled',
                'failed_at' => null,
                'last_error' => 'Cancelled when the repayment reminder schedule changed.',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Left cancelled: putting these back would send out-of-date reminders.
    }
};
