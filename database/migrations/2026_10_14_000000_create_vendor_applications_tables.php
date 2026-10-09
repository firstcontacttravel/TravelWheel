<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_applications', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('status')->default('received')->index();

            // Company & business information
            $table->string('registered_name');
            $table->string('trading_name')->nullable();
            $table->string('registration_number');
            $table->string('tax_id')->nullable();
            $table->string('country');
            $table->text('address');
            $table->string('website')->nullable();
            $table->string('business_email');
            $table->string('business_phone');
            $table->string('year_established')->nullable();
            $table->text('locations_served');

            // Contacts: the authorised person, plus the people the team calls day to day
            $table->string('contact_name');
            $table->string('contact_title');
            $table->string('contact_email');
            $table->string('contact_phone');
            $table->string('operations_name')->nullable();
            $table->string('operations_email')->nullable();
            $table->string('operations_phone')->nullable();
            $table->string('accounts_name')->nullable();
            $table->string('accounts_email')->nullable();
            $table->string('accounts_phone')->nullable();

            // Business type, services and the answers for each service
            $table->json('business_types');
            $table->string('business_type_other')->nullable();
            $table->json('services');
            $table->string('service_other')->nullable();
            $table->json('service_details')->nullable();
            $table->boolean('works_with_other_platforms')->default(false);
            $table->text('other_platforms_details')->nullable();

            // How they take bookings and get paid
            $table->json('booking_channels');
            $table->string('booking_email');
            $table->string('confirmation_time')->nullable();
            $table->string('rate_model');
            $table->string('settlement_currency');
            $table->string('payment_terms')->nullable();
            $table->text('bank_name')->nullable();       // encrypted
            $table->text('account_name')->nullable();    // encrypted
            $table->text('account_number')->nullable();  // encrypted
            $table->text('references')->nullable();

            // Declaration
            $table->string('declarant_name');
            $table->string('declarant_title');
            $table->timestamp('declared_at');
            $table->string('submitted_ip', 45)->nullable();

            // Internal review
            $table->timestamp('documents_verified_at')->nullable();
            $table->foreignId('documents_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('compliance_completed_at')->nullable();
            $table->foreignId('compliance_completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('vendor_code')->nullable()->unique();
            $table->json('approved_services')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('decision_note')->nullable();
            $table->string('risk_rating')->nullable();
            $table->date('next_review_on')->nullable();
            $table->text('internal_notes')->nullable();

            $table->timestamps();
        });

        Schema::create('vendor_application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_application_id')->constrained()->cascadeOnDelete();
            $table->string('service')->nullable();  // null = company document
            $table->string('type');
            $table->string('label');
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->date('expires_on')->nullable();
            $table->string('status')->default('pending');
            $table->text('review_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_application_documents');
        Schema::dropIfExists('vendor_applications');
    }
};
