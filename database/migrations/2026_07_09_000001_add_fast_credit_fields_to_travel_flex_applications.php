<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_flex_applications', function (Blueprint $table): void {
            $table->string('applicant_type')->default('individual')->after('unique_id')->index();
            $table->json('identity_details')->nullable()->after('bvn_metadata');
            $table->json('bank_details')->nullable()->after('employment_details');
            $table->json('next_of_kin_details')->nullable()->after('bank_details');
            $table->json('company_details')->nullable()->after('next_of_kin_details');
            $table->json('representative_details')->nullable()->after('company_details');
            $table->json('agreement_acceptance')->nullable()->after('document_paths');
        });
    }

    public function down(): void
    {
        Schema::table('travel_flex_applications', function (Blueprint $table): void {
            $table->dropColumn([
                'applicant_type',
                'identity_details',
                'bank_details',
                'next_of_kin_details',
                'company_details',
                'representative_details',
                'agreement_acceptance',
            ]);
        });
    }
};
