<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('cac_number', 30)->index();
            $table->string('contact_name');
            $table->string('contact_email')->index();
            $table->string('contact_phone', 30)->nullable();
            // requested, invited, active, suspended, rejected
            $table->string('status', 20)->default('requested')->index();
            // Why it was rejected or suspended. Internal, never shown to the company.
            $table->text('status_reason')->nullable();
            $table->foreignId('account_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Deliberately not `users`: that table is staff, and a row in it can
        // reach the admin panel. Keeping these apart means no corporate login
        // can ever become a staff login by a mistaken flag.
        Schema::create('company_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            // admin or booker
            $table->string('role', 20)->default('booker');
            // Admins always need a code; this is a Booker's own choice.
            $table->boolean('login_code_enabled')->default(false);
            $table->string('login_code_hash')->nullable();
            $table->timestamp('login_code_expires_at')->nullable();
            $table->unsignedTinyInteger('login_code_attempts')->default(0);
            $table->timestamp('login_code_sent_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('company_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('email')->index();
            $table->string('role', 20);
            // Only the hash is kept, so a database leak can't be used to join a company.
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('invited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('invited_by_company_user_id')->nullable()->constrained('company_users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('company_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->foreignId('company_user_id')->nullable()->after('user_id')->constrained('company_users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_user_id');
        });

        Schema::dropIfExists('company_password_reset_tokens');
        Schema::dropIfExists('company_invitations');
        Schema::dropIfExists('company_users');
        Schema::dropIfExists('companies');
    }
};
