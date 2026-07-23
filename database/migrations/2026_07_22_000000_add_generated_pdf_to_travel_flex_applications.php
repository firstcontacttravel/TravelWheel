<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_flex_applications', function (Blueprint $table): void {
            $table->string('generated_application_path')->nullable()->after('document_paths');
            $table->string('generated_application_sha256', 64)->nullable()->after('generated_application_path');
            $table->string('generated_application_version', 80)->nullable()->after('generated_application_sha256');
            $table->timestamp('generated_application_at')->nullable()->after('generated_application_version');
        });
    }

    public function down(): void
    {
        Schema::table('travel_flex_applications', function (Blueprint $table): void {
            $table->dropColumn([
                'generated_application_path',
                'generated_application_sha256',
                'generated_application_version',
                'generated_application_at',
            ]);
        });
    }
};
