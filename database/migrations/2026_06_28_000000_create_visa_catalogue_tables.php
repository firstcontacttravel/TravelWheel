<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('countries')) {
            Schema::create('countries', function (Blueprint $table) {
                $table->id();
                $table->char('alpha2', 2)->unique();
                $table->char('alpha3', 3)->nullable()->unique();
                $table->string('name');
                $table->string('region')->nullable()->index();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        } else {
            Schema::table('countries', function (Blueprint $table) {
                if (! Schema::hasColumn('countries', 'alpha2')) {
                    $table->char('alpha2', 2)->nullable();
                }
                if (! Schema::hasColumn('countries', 'alpha3')) {
                    $table->char('alpha3', 3)->nullable();
                }
                if (! Schema::hasColumn('countries', 'region')) {
                    $table->string('region')->nullable();
                }
                if (! Schema::hasColumn('countries', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
            });

            $countryRows = DB::table('countries')->select(['id', 'code', 'alpha2'])->orderBy('id')->get();
            $countryRows
                ->groupBy(fn ($country): string => strtoupper((string) ($country->code ?: $country->alpha2 ?: 'row-'.$country->id)))
                ->each(function ($duplicates, string $alpha2): void {
                    if (strlen($alpha2) !== 2) {
                        return;
                    }

                    $canonical = $duplicates->first();
                    DB::table('countries')->where('id', $canonical->id)->update(['alpha2' => $alpha2]);

                    $duplicateIds = $duplicates->skip(1)->pluck('id');
                    if ($duplicateIds->isNotEmpty()) {
                        DB::table('countries')->whereIn('id', $duplicateIds)->update(['alpha2' => null, 'is_active' => false]);
                    }
                });

            Schema::table('countries', function (Blueprint $table) {
                if (! Schema::hasIndex('countries', 'countries_alpha2_unique')) {
                    $table->unique('alpha2', 'countries_alpha2_unique');
                }
                if (! Schema::hasIndex('countries', 'countries_alpha3_unique')) {
                    $table->unique('alpha3', 'countries_alpha3_unique');
                }
                if (! Schema::hasIndex('countries', 'countries_region_index')) {
                    $table->index('region', 'countries_region_index');
                }
                if (! Schema::hasIndex('countries', 'countries_is_active_index')) {
                    $table->index('is_active', 'countries_is_active_index');
                }
            });
        }

        Schema::create('country_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('country_country_group', function (Blueprint $table) {
            $table->foreignId('country_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->primary(['country_group_id', 'country_id']);
        });

        Schema::create('visa_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destination_country_id')->constrained('countries')->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('family')->default('standard')->index();
            $table->string('category')->index();
            $table->string('entry_type')->default('single');
            $table->string('publication_status')->default('draft')->index();
            $table->string('eligibility_mode')->default('all');
            $table->unsignedInteger('validity_days')->nullable();
            $table->unsignedInteger('maximum_stay_days')->nullable();
            $table->text('summary')->nullable();
            $table->text('description')->nullable();
            $table->text('processing_disclaimer')->nullable();
            $table->text('issuance_disclaimer')->nullable();
            $table->text('important_notes')->nullable();
            $table->dateTime('effective_from')->nullable()->index();
            $table->dateTime('effective_until')->nullable()->index();
            $table->dateTime('published_at')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['destination_country_id', 'publication_status']);
        });

        Schema::create('visa_eligibility_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visa_product_id')->constrained()->cascadeOnDelete();
            $table->string('rule_type')->index();
            $table->foreignId('country_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('country_group_id')->nullable()->constrained()->cascadeOnDelete();
            $table->json('conditions')->nullable();
            $table->text('public_message')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['visa_product_id', 'rule_type', 'is_active'], 'visa_eligibility_lookup');
        });

        Schema::create('visa_processing_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visa_product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('minimum_business_days');
            $table->unsignedInteger('maximum_business_days');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('visa_fee_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visa_product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visa_processing_option_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('fee_type')->index();
            $table->string('traveler_type')->default('all')->index();
            $table->char('currency', 3);
            $table->decimal('amount', 16, 2);
            $table->string('payee')->default('travelwheel')->index();
            $table->boolean('pay_online')->default(true);
            $table->json('conditions')->nullable();
            $table->dateTime('effective_from')->nullable();
            $table->dateTime('effective_until')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('visa_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visa_product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category')->default('supporting_document')->index();
            $table->string('scope')->default('traveler');
            $table->string('requirement_state')->default('required');
            $table->text('description')->nullable();
            $table->json('conditions')->nullable();
            $table->json('accepted_mime_types')->nullable();
            $table->unsignedInteger('maximum_file_size_kb')->default(10240);
            $table->unsignedInteger('minimum_validity_days')->nullable();
            $table->text('guidance')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('visa_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visa_product_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('section')->default('additional');
            $table->string('label');
            $table->text('help_text')->nullable();
            $table->string('input_type')->default('text');
            $table->string('scope')->default('application');
            $table->boolean('is_required')->default(false);
            $table->json('options')->nullable();
            $table->json('validation_rules')->nullable();
            $table->json('conditions')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['visa_product_id', 'key']);
        });

        Schema::create('visa_optional_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visa_product_id')->constrained()->cascadeOnDelete();
            $table->string('service_type')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('customer_disclaimer')->nullable();
            $table->char('currency', 3)->nullable();
            $table->decimal('amount', 16, 2)->nullable();
            $table->string('pricing_model')->default('fixed');
            $table->json('configuration')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visa_optional_services');
        Schema::dropIfExists('visa_questions');
        Schema::dropIfExists('visa_requirements');
        Schema::dropIfExists('visa_fee_components');
        Schema::dropIfExists('visa_processing_options');
        Schema::dropIfExists('visa_eligibility_rules');
        Schema::dropIfExists('visa_products');
        Schema::dropIfExists('country_country_group');
        Schema::dropIfExists('country_groups');
        if (Schema::hasColumn('countries', 'code')) {
            Schema::table('countries', function (Blueprint $table) {
                $table->dropUnique('countries_alpha2_unique');
                $table->dropUnique('countries_alpha3_unique');
                $table->dropIndex('countries_region_index');
                $table->dropIndex('countries_is_active_index');
                $table->dropColumn(['alpha2', 'alpha3', 'region', 'is_active']);
            });
        } else {
            Schema::dropIfExists('countries');
        }
    }
};
