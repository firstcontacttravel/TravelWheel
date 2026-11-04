<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_flex_instalment_payments', function (Blueprint $table): void {
            $table->id();
            // Named by hand: the generated name is over MySQL's 64-character limit.
            $table->foreignId('travel_flex_application_id')
                ->constrained('travel_flex_applications', 'id', 'tf_instalment_payments_application_fk')
                ->restrictOnDelete();
            $table->unsignedInteger('instalment_index');
            $table->date('due_date');
            $table->string('label')->nullable();
            $table->decimal('amount', 15, 2);
            $table->date('paid_on');
            $table->string('payment_reference');
            $table->text('note')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            // A reversal keeps the row, so the money history stays intact.
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reversal_reason')->nullable();
            $table->timestamps();

            $table->index(['travel_flex_application_id', 'instalment_index'], 'tf_instalment_payments_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_flex_instalment_payments');
    }
};
