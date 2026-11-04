<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // A copy kept for speed and for locking. The ledger is the record.
            $table->decimal('wallet_balance', 14, 2)->default(0);
            // How far below zero the balance may go. Nothing sets this at launch,
            // so a wallet can't go below zero until credit terms are built.
            $table->decimal('credit_limit', 14, 2)->default(0);
        });

        Schema::create('company_wallet_top_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('reference', 40)->unique();
            // bank_transfer or card
            $table->string('method', 20);
            // pending, confirmed, rejected or failed
            $table->string('status', 20)->default('pending')->index();
            // What reaches the wallet. For a bank transfer Finance may correct
            // it to what actually arrived.
            $table->decimal('amount', 14, 2);
            // Card only: added on top and never credited to the wallet. The
            // percentage is kept because the setting can change later.
            $table->decimal('fee_percent', 5, 2)->default(0);
            $table->decimal('fee_amount', 14, 2)->default(0);
            $table->decimal('total_charged', 14, 2);
            $table->string('bank_reference', 100)->nullable();
            $table->foreignId('requested_by_company_user_id')->nullable()->constrained('company_users')->nullOnDelete();
            $table->foreignId('decided_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamps();
        });

        Schema::create('company_wallet_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            // top_up, booking_debit, booking_return, refund, payout or adjustment
            $table->string('type', 20);
            // Positive adds to the balance, negative takes from it.
            $table->decimal('amount', 14, 2);
            $table->decimal('balance_after', 14, 2);
            $table->foreignId('company_wallet_top_up_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('flight_booking_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason', 500)->nullable();
            // The same event can't post twice, however often it is retried.
            $table->string('idempotency_key', 100)->unique();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_wallet_entries');
        Schema::dropIfExists('company_wallet_top_ups');

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['wallet_balance', 'credit_limit']);
        });
    }
};
