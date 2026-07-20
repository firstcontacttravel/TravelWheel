<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_flex_applications', function (Blueprint $table): void {
            if (! Schema::hasColumn('travel_flex_applications', 'applicant_type')) {
                $table->string('applicant_type')->default('individual')->after('unique_id')->index();
            }

            if (! Schema::hasColumn('travel_flex_applications', 'identity_details')) {
                $table->json('identity_details')->nullable()->after('bvn_metadata');
            }

            if (! Schema::hasColumn('travel_flex_applications', 'bank_details')) {
                $table->json('bank_details')->nullable()->after('employment_details');
            }

            if (! Schema::hasColumn('travel_flex_applications', 'next_of_kin_details')) {
                $table->json('next_of_kin_details')->nullable()->after('bank_details');
            }

            if (! Schema::hasColumn('travel_flex_applications', 'company_details')) {
                $table->json('company_details')->nullable()->after('next_of_kin_details');
            }

            if (! Schema::hasColumn('travel_flex_applications', 'representative_details')) {
                $table->json('representative_details')->nullable()->after('company_details');
            }

            if (! Schema::hasColumn('travel_flex_applications', 'agreement_acceptance')) {
                $table->json('agreement_acceptance')->nullable()->after('document_paths');
            }
        });
    }

    public function down(): void
    {
        Schema::table('travel_flex_applications', function (Blueprint $table): void {
            foreach ([
                'applicant_type',
                'identity_details',
                'bank_details',
                'next_of_kin_details',
                'company_details',
                'representative_details',
                'agreement_acceptance',
            ] as $column) {
                if (Schema::hasColumn('travel_flex_applications', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
