<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visa_processing_options', function (Blueprint $table) {
            $table->uuid('code')->nullable()->after('visa_product_id');
        });
        Schema::table('visa_fee_components', function (Blueprint $table) {
            $table->uuid('processing_option_code')->nullable()->after('visa_processing_option_id')->index();
        });
        Schema::table('visa_optional_services', function (Blueprint $table) {
            $table->uuid('code')->nullable()->after('visa_product_id');
        });
        Schema::table('visa_requirements', function (Blueprint $table) {
            $table->uuid('optional_service_code')->nullable()->after('visa_product_id')->index();
        });

        DB::table('visa_processing_options')->orderBy('id')->get()->each(function ($option): void {
            $code = (string) Str::uuid();
            DB::table('visa_processing_options')->where('id', $option->id)->update(['code' => $code]);
            DB::table('visa_fee_components')->where('visa_processing_option_id', $option->id)->update(['processing_option_code' => $code]);
        });
        DB::table('visa_optional_services')->whereNull('code')->orderBy('id')->get()->each(fn ($service) => DB::table('visa_optional_services')->where('id', $service->id)->update(['code' => (string) Str::uuid()]));

        Schema::table('visa_processing_options', function (Blueprint $table) {
            $table->uuid('code')->nullable(false)->change();
            $table->unique(['visa_product_id', 'code']);
        });
        Schema::table('visa_optional_services', function (Blueprint $table) {
            $table->uuid('code')->nullable(false)->change();
            $table->unique(['visa_product_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('visa_requirements', fn (Blueprint $table) => $table->dropColumn('optional_service_code'));
        Schema::table('visa_optional_services', function (Blueprint $table) {
            $table->dropUnique(['visa_product_id', 'code']);
            $table->dropColumn('code');
        });
        Schema::table('visa_fee_components', fn (Blueprint $table) => $table->dropColumn('processing_option_code'));
        Schema::table('visa_processing_options', function (Blueprint $table) {
            $table->dropUnique(['visa_product_id', 'code']);
            $table->dropColumn('code');
        });
    }
};
