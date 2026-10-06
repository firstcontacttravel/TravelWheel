<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Workflow phase 1: one work item per booking or application, and its
     * history.
     *
     * The booking's own status columns stay the source of truth. A work item
     * follows them (stage) and adds what they never had: an owner, a
     * department queue, a priority and a due time. state is the coarse
     * reading of the stage — open (someone should act), waiting (on the
     * customer or a supplier), done, cancelled — so queues filter on one
     * column instead of knowing every service's vocabulary.
     */
    public function up(): void
    {
        Schema::create('work_items', function (Blueprint $table) {
            $table->id();
            $table->morphs('subject');
            $table->string('service', 40)->index();
            $table->string('stage', 60);
            $table->string('state', 20)->default('open');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('priority', 10)->default('normal');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['subject_type', 'subject_id']);
            $table->index(['state', 'department_id']);
            $table->index(['state', 'owner_id']);
        });

        Schema::create('work_item_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_item_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            // Null for changes the system made (a payment webhook, a supplier).
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from')->nullable();
            $table->string('to')->nullable();
            $table->text('body')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['work_item_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_item_events');
        Schema::dropIfExists('work_items');
    }
};
