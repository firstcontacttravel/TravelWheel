<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('visa_role')->nullable()->after('is_admin')->index();
        });

        Schema::table('visa_applications', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->dateTime('assigned_at')->nullable()->after('assigned_to');
            $table->date('decision_date')->nullable();
            $table->string('decision_reference')->nullable();
            $table->dateTime('issued_at')->nullable();
            $table->date('visa_valid_from')->nullable();
            $table->date('visa_valid_until')->nullable();
            $table->text('no_document_reason')->nullable();
            $table->index(['assigned_to', 'status']);
        });

        Schema::table('visa_application_documents', function (Blueprint $table) {
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
        });

        Schema::table('visa_additional_document_requests', function (Blueprint $table) {
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_note')->nullable();
        });

        Schema::create('visa_internal_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visa_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['visa_application_id', 'created_at']);
        });

        Schema::create('visa_issued_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visa_application_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('issued_at');
            $table->dateTime('superseded_at')->nullable();
            $table->timestamps();
            $table->unique(['visa_application_id', 'version']);
        });

        Schema::create('visa_audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visa_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type')->index();
            $table->string('summary');
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['visa_application_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visa_audit_events');
        Schema::dropIfExists('visa_issued_documents');
        Schema::dropIfExists('visa_internal_notes');
        Schema::table('visa_additional_document_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn('review_note');
        });
        Schema::table('visa_application_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['reviewed_at', 'review_note']);
        });
        Schema::table('visa_applications', function (Blueprint $table) {
            $table->dropIndex(['assigned_to', 'status']);
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropColumn(['assigned_at', 'decision_date', 'decision_reference', 'issued_at', 'visa_valid_from', 'visa_valid_until', 'no_document_reason']);
        });
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('visa_role'));
    }
};
