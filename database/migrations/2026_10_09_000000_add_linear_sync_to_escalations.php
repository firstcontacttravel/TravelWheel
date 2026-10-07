<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Workflow phase 3: escalations mirrored to Linear.
     *
     * linear_requested records the decision made when escalating (always for
     * IT, a choice otherwise), so a retry after Linear was down knows to try
     * again. linear_identifier is the human number, e.g. IT-12.
     *
     * linear_webhook_receipts holds each delivery Linear sends, so a retried
     * delivery is applied once.
     */
    public function up(): void
    {
        Schema::table('escalations', function (Blueprint $table) {
            $table->boolean('linear_requested')->default(false)->after('closed_at');
            $table->string('linear_identifier', 20)->nullable()->after('linear_issue_id');
            $table->index('linear_issue_id');
        });

        Schema::create('linear_webhook_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_id')->unique();
            $table->string('type', 40);
            $table->string('action', 20);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('linear_webhook_receipts');

        Schema::table('escalations', function (Blueprint $table) {
            $table->dropIndex(['linear_issue_id']);
            $table->dropColumn(['linear_requested', 'linear_identifier']);
        });
    }
};
