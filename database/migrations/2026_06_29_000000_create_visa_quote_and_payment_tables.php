<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visa_exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->char('source_currency', 3);
            $table->char('target_currency', 3)->default('NGN');
            $table->decimal('rate', 18, 6);
            $table->string('source')->default('manual');
            $table->dateTime('effective_from');
            $table->dateTime('effective_until')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->index(['source_currency', 'target_currency', 'is_active'], 'visa_rate_lookup');
        });

        if (Schema::hasTable('exchange_rates')) {
            foreach (DB::table('exchange_rates')->get() as $rate) {
                DB::table('visa_exchange_rates')->insert([
                    'source_currency' => strtoupper($rate->currency), 'target_currency' => 'NGN',
                    'rate' => $rate->rate, 'source' => 'legacy_import', 'effective_from' => now(),
                    'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        Schema::create('visa_quotes', function (Blueprint $table) {
            $table->id();
            $table->ulid('reference')->unique();
            $table->foreignId('visa_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visa_product_id')->constrained()->restrictOnDelete();
            $table->foreignId('visa_processing_option_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('product_version');
            $table->string('status')->default('active')->index();
            $table->char('checkout_currency', 3)->default('NGN');
            $table->decimal('payable_total', 18, 2);
            $table->json('source_totals');
            $table->json('exchange_rate_snapshot');
            $table->string('pricing_fingerprint', 64);
            $table->dateTime('expires_at')->index();
            $table->dateTime('accepted_at')->nullable();
            $table->dateTime('superseded_at')->nullable();
            $table->timestamps();
            $table->index(['visa_application_id', 'status']);
        });

        Schema::create('visa_quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visa_quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visa_fee_component_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('visa_optional_service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('item_type')->index();
            $table->string('traveler_type')->nullable();
            $table->string('calculation_basis');
            $table->decimal('quantity', 10, 2);
            $table->char('source_currency', 3);
            $table->decimal('source_unit_amount', 18, 2);
            $table->decimal('source_total', 18, 2);
            $table->decimal('exchange_rate', 18, 6);
            $table->char('checkout_currency', 3);
            $table->decimal('checkout_unit_amount', 18, 2);
            $table->decimal('checkout_total', 18, 2);
            $table->string('payee');
            $table->boolean('pay_online');
            $table->json('metadata')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('visa_payments', function (Blueprint $table) {
            $table->id();
            $table->ulid('reference')->unique();
            $table->foreignId('visa_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visa_quote_id')->constrained()->restrictOnDelete();
            $table->string('provider')->default('seerbit')->index();
            $table->string('status')->default('initialized')->index();
            $table->decimal('expected_amount', 18, 2);
            $table->char('expected_currency', 3);
            $table->decimal('verified_amount', 18, 2)->nullable();
            $table->char('verified_currency', 3)->nullable();
            $table->string('idempotency_key', 64)->unique();
            $table->text('checkout_url')->nullable();
            $table->json('initialization_response')->nullable();
            $table->json('verification_response')->nullable();
            $table->string('failure_code')->nullable();
            $table->text('failure_message')->nullable();
            $table->dateTime('initiated_at')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->dateTime('failed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('visa_payment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visa_payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider')->default('seerbit');
            $table->string('event_hash', 64)->unique();
            $table->string('event_type');
            $table->json('payload');
            $table->string('processing_status')->default('received')->index();
            $table->text('processing_message')->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visa_payment_events');
        Schema::dropIfExists('visa_payments');
        Schema::dropIfExists('visa_quote_items');
        Schema::dropIfExists('visa_quotes');
        Schema::dropIfExists('visa_exchange_rates');
    }
};
