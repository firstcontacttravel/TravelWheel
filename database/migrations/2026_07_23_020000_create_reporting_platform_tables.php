<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reporting_facts', function (Blueprint $table): void {
            $table->id();
            $table->string('source_type', 80);
            $table->unsignedBigInteger('source_id');
            $table->string('product', 50)->index();
            $table->string('sub_product', 100)->nullable()->index();
            $table->string('reference', 150)->nullable()->index();
            $table->string('customer_hash', 64)->nullable()->index();
            $table->char('currency', 3)->default('NGN');
            $table->decimal('gross_value', 18, 2)->default(0);
            $table->decimal('verified_collections', 18, 2)->default(0);
            $table->decimal('travelwheel_revenue', 18, 2)->default(0);
            $table->decimal('supplier_cost', 18, 2)->nullable();
            $table->decimal('tax_amount', 18, 2)->nullable();
            $table->decimal('gross_profit', 18, 2)->nullable();
            $table->boolean('financially_additive')->default(true)->index();
            $table->string('payment_status', 40)->default('unknown')->index();
            $table->string('fulfillment_status', 40)->default('unknown')->index();
            $table->string('payment_method', 80)->nullable()->index();
            $table->string('payment_gateway', 80)->nullable()->index();
            $table->string('provider', 120)->nullable()->index();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->dateTime('created_at_source')->index();
            $table->dateTime('paid_at')->nullable()->index();
            $table->dateTime('service_at')->nullable()->index();
            $table->dateTime('completed_at')->nullable()->index();
            $table->json('dimensions')->nullable();
            $table->json('data_quality')->nullable();
            $table->dateTime('last_synced_at')->index();
            $table->timestamps();

            $table->unique(['source_type', 'source_id'], 'reporting_facts_source_unique');
            $table->index(['product', 'created_at_source'], 'reporting_facts_product_created');
            $table->index(['payment_status', 'paid_at'], 'reporting_facts_payment_paid');
        });

        Schema::create('reporting_saved_views', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('section', 40)->default('overview');
            $table->json('filters');
            $table->boolean('is_shared')->default(false);
            $table->timestamps();
        });

        Schema::create('reporting_targets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('label', 150);
            $table->string('product', 50)->nullable()->index();
            $table->string('metric', 80)->index();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('target_value', 18, 2);
            $table->timestamps();
        });

        Schema::create('reporting_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('report_key', 80);
            $table->string('format', 10)->default('csv');
            $table->string('frequency', 20)->default('weekly');
            $table->json('recipients');
            $table->json('filters');
            $table->boolean('is_active')->default(true)->index();
            $table->dateTime('last_sent_at')->nullable();
            $table->dateTime('next_send_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('reporting_export_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('report_key', 80);
            $table->string('format', 10);
            $table->json('filters');
            $table->unsignedInteger('row_count')->default(0);
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->dateTime('exported_at')->index();
        });

        Schema::create('reporting_alerts', function (Blueprint $table): void {
            $table->id();
            $table->string('fingerprint', 64)->unique();
            $table->string('type', 80)->index();
            $table->string('severity', 20)->default('warning')->index();
            $table->string('product', 50)->nullable()->index();
            $table->string('metric', 80)->nullable();
            $table->decimal('observed_value', 18, 2)->nullable();
            $table->decimal('expected_value', 18, 2)->nullable();
            $table->text('message');
            $table->dateTime('detected_at')->index();
            $table->dateTime('resolved_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('acknowledged_at')->nullable();
            $table->timestamps();
        });

        Schema::create('reporting_sync_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('status', 20)->default('running')->index();
            $table->unsignedInteger('row_count')->default(0);
            $table->json('product_counts')->nullable();
            $table->json('errors')->nullable();
            $table->dateTime('started_at');
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reporting_sync_runs');
        Schema::dropIfExists('reporting_alerts');
        Schema::dropIfExists('reporting_export_audits');
        Schema::dropIfExists('reporting_schedules');
        Schema::dropIfExists('reporting_targets');
        Schema::dropIfExists('reporting_saved_views');
        Schema::dropIfExists('reporting_facts');
    }
};
