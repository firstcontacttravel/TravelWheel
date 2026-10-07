<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Workflow phase 5: deadlines.
     *
     * stage_entered_at starts each step's clock. warned_at, breached_at and
     * ceo_alerted_at record that each alert went out, so the five-minute
     * check sends each one once; all three reset when the stage moves.
     *
     * Items already open start their clock now rather than from whenever
     * they last moved: otherwise the first check after deploying would
     * declare months-old work overdue at once and flood everyone, the CEO
     * included, with alerts nobody could act on.
     */
    public function up(): void
    {
        Schema::table('work_items', function (Blueprint $table) {
            $table->timestamp('stage_entered_at')->nullable()->after('due_at');
            $table->timestamp('warned_at')->nullable()->after('stage_entered_at');
            $table->timestamp('breached_at')->nullable()->after('warned_at');
            $table->timestamp('ceo_alerted_at')->nullable()->after('breached_at');
            $table->index(['state', 'due_at']);
        });

        DB::table('work_items')->update(['stage_entered_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('work_items', function (Blueprint $table) {
            $table->dropIndex(['state', 'due_at']);
            $table->dropColumn(['stage_entered_at', 'warned_at', 'breached_at', 'ceo_alerted_at']);
        });
    }
};
