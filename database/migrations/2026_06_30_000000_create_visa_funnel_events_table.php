<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visa_funnel_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('journey_id')->index();
            $table->foreignId('visa_application_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('visa_product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event')->index();
            $table->json('metadata')->nullable();
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->timestamps();
            $table->index(['event', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visa_funnel_events');
    }
};
