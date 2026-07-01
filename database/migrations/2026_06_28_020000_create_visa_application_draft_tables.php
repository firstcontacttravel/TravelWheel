<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visa_applications', function (Blueprint $table) {
            $table->id();
            $table->ulid('reference')->unique();
            $table->string('resume_token_hash', 64);
            $table->foreignId('visa_product_id')->constrained()->restrictOnDelete();
            $table->foreignId('visa_processing_option_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('product_version');
            $table->string('status')->default('draft')->index();
            $table->unsignedTinyInteger('current_step')->default(1);
            $table->unsignedTinyInteger('completed_step')->default(0);
            $table->foreignId('nationality_country_id')->constrained('countries')->restrictOnDelete();
            $table->foreignId('residence_country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->foreignId('destination_country_id')->constrained('countries')->restrictOnDelete();
            $table->date('arrival_date');
            $table->date('departure_date');
            $table->unsignedTinyInteger('adult_count')->default(1);
            $table->unsignedTinyInteger('child_count')->default(0);
            $table->unsignedTinyInteger('infant_count')->default(0);
            $table->string('contact_email')->nullable()->index();
            $table->boolean('declaration_accepted')->default(false);
            $table->dateTime('declaration_accepted_at')->nullable();
            $table->json('search_snapshot');
            $table->json('product_snapshot');
            $table->dateTime('last_activity_at')->index();
            $table->dateTime('expires_at')->index();
            $table->timestamps();
        });

        Schema::create('visa_travelers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visa_application_id')->constrained()->cascadeOnDelete();
            $table->ulid('reference')->unique();
            $table->string('traveler_type')->index();
            $table->unsignedTinyInteger('position');
            $table->string('title')->nullable();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('sex')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('place_of_birth')->nullable();
            $table->foreignId('nationality_country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('home_address')->nullable();
            $table->text('passport_number')->nullable();
            $table->string('passport_type')->nullable();
            $table->date('passport_issued_at')->nullable();
            $table->date('passport_expires_at')->nullable();
            $table->foreignId('passport_issuing_country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->timestamps();
            $table->unique(['visa_application_id', 'traveler_type', 'position']);
        });

        Schema::create('visa_application_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visa_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visa_traveler_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('visa_question_id')->constrained()->restrictOnDelete();
            $table->json('value')->nullable();
            $table->timestamps();
            $table->unique(['visa_application_id', 'visa_traveler_id', 'visa_question_id'], 'visa_answer_scope_unique');
        });

        Schema::create('visa_application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visa_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visa_traveler_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('visa_requirement_id')->constrained()->restrictOnDelete();
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->string('status')->default('uploaded')->index();
            $table->timestamps();
            $table->unique(['visa_application_id', 'visa_traveler_id', 'visa_requirement_id'], 'visa_document_scope_unique');
        });

        Schema::create('visa_application_service_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visa_application_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('visa_optional_service_id');
            $table->foreign('visa_optional_service_id', 'visa_app_service_option_fk')->references('id')->on('visa_optional_services')->restrictOnDelete();
            $table->boolean('selected')->default(false);
            $table->json('configuration')->nullable();
            $table->timestamps();
            $table->unique(['visa_application_id', 'visa_optional_service_id'], 'visa_app_service_unique');
        });

        Schema::create('visa_application_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visa_application_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('actor_type')->default('system');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visa_application_status_history');
        Schema::dropIfExists('visa_application_service_selections');
        Schema::dropIfExists('visa_application_documents');
        Schema::dropIfExists('visa_application_answers');
        Schema::dropIfExists('visa_travelers');
        Schema::dropIfExists('visa_applications');
    }
};
