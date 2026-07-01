<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visa_portal_access_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visa_application_id')->constrained()->cascadeOnDelete();
            $table->string('email')->index();
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->dateTime('expires_at')->index();
            $table->dateTime('used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('visa_additional_document_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visa_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visa_traveler_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('visa_requirement_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->string('status')->default('open')->index();
            $table->dateTime('due_at')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disk')->nullable();
            $table->string('path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('visa_notification_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visa_application_id')->constrained()->cascadeOnDelete();
            $table->string('event_type')->index();
            $table->string('recipient');
            $table->string('subject');
            $table->json('payload')->nullable();
            $table->string('status')->default('queued')->index();
            $table->dateTime('queued_at')->nullable();
            $table->dateTime('resent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visa_notification_events');
        Schema::dropIfExists('visa_additional_document_requests');
        Schema::dropIfExists('visa_portal_access_codes');
    }
};
