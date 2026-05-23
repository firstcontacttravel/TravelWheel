<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_flex_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flight_booking_id')->nullable()->constrained('flight_bookings')->nullOnDelete();
            $table->string('booking_ref')->nullable()->index();
            $table->string('unique_id')->nullable()->index();
            $table->json('applicant_details')->nullable();
            $table->json('bvn_metadata')->nullable();
            $table->json('employment_details')->nullable();
            $table->json('document_paths')->nullable();
            $table->json('repayment_plan')->nullable();
            $table->decimal('down_payment', 14, 2)->nullable();
            $table->unsignedTinyInteger('down_percent')->nullable();
            $table->decimal('grand_total', 14, 2)->nullable();
            $table->decimal('total_interest', 14, 2)->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_status')->default('pending');
            $table->string('application_status')->default('submitted');
            $table->string('provider_status')->default('not_sent');
            $table->timestamp('provider_email_sent_at')->nullable();
            $table->text('provider_email_error')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_note')->nullable();
            $table->timestamps();

            $table->index(['application_status', 'created_at']);
            $table->index(['provider_status', 'created_at']);
            $table->index(['payment_status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_flex_applications');
    }
};
