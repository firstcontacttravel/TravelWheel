<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_invoice_sequences', function (Blueprint $table) {
            // For example invoice:2026. Locked while a number is taken, so none repeat.
            $table->string('name', 30)->primary();
            $table->unsignedBigInteger('last_number')->default(0);
        });

        Schema::create('company_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('flight_booking_id')->nullable()->constrained()->restrictOnDelete();
            // invoice or credit_note
            $table->string('type', 20)->index();
            $table->string('number', 30)->unique();
            $table->foreignId('credited_invoice_id')->nullable()->constrained('company_invoices')->restrictOnDelete();
            $table->foreignId('company_wallet_entry_id')->nullable()->constrained()->restrictOnDelete();
            // The same event can't produce two documents.
            $table->string('idempotency_key', 100)->unique();
            $table->string('currency', 3)->default('NGN');
            $table->decimal('total', 14, 2);
            $table->decimal('vat_percent', 5, 2)->nullable();
            $table->decimal('vat_amount', 14, 2)->default(0);
            // A fixed copy: later changes to rates, names or the booking don't alter it.
            $table->json('lines');
            $table->json('company_details');
            $table->json('booking_details')->nullable();
            $table->string('reason', 500)->nullable();
            $table->timestamp('issued_at')->index();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->timestamp('suspended_at')->nullable();
        });

        DB::table('companies')->where('status', 'suspended')->update(['suspended_at' => now()]);

        // SeerBit already adds its own fee at checkout, so TravelWheel's starts at 0.
        // Anything the CEO has already set is left alone.
        if (! DB::table('app_settings')->where('key', 'corporate.card_topup_fee_percent')->exists()) {
            DB::table('app_settings')->insert([
                'key' => 'corporate.card_topup_fee_percent',
                'value' => '0',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('suspended_at');
        });

        Schema::dropIfExists('company_invoices');
        Schema::dropIfExists('company_invoice_sequences');
    }
};
