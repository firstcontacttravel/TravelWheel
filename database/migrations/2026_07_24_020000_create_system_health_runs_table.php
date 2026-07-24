<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_health_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('overall_status', 20)->index();
            $table->unsignedSmallInteger('healthy_count')->default(0);
            $table->unsignedSmallInteger('warning_count')->default(0);
            $table->unsignedSmallInteger('failed_count')->default(0);
            $table->unsignedInteger('duration_ms')->default(0);
            $table->json('results');
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['created_at', 'overall_status'], 'health_runs_created_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_health_runs');
    }
};
