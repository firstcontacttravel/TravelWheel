<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Workflow phase 2: escalations, and the in-app notification bell.
     *
     * An escalation asks another person or department to step in on a work
     * item. mode says what happens to ownership:
     *   help    — the owner keeps it; the other side resolves one problem and
     *             hands it back with a note (the default).
     *   handoff — ownership moves to whoever accepts.
     *
     * linear_* are filled in by phase 3.
     */
    public function up(): void
    {
        Schema::create('escalations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_item_id')->constrained()->cascadeOnDelete();
            $table->string('mode', 10);
            $table->string('status', 12)->default('open');
            $table->foreignId('raised_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('to_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('priority', 10)->default('normal');
            $table->text('reason');
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('response_note')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('linear_issue_id')->nullable();
            $table->string('linear_issue_url')->nullable();
            $table->timestamps();

            $table->index(['status', 'to_user_id']);
            $table->index(['status', 'to_department_id']);
            $table->index(['status', 'raised_by']);
        });

        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('escalations');
        Schema::dropIfExists('notifications');
    }
};
