<?php

namespace Tests\Feature;

use App\Enums\VisaEligibilityMode;
use App\Enums\VisaPublicationStatus;
use App\Filament\Resources\VisaProducts\Pages\CreateVisaProduct;
use App\Filament\Resources\VisaProducts\VisaProductResource;
use App\Filament\Resources\VisaDestinations\VisaDestinationResource;
use App\Models\Country;
use App\Models\CountryGroup;
use App\Models\User;
use App\Models\VisaProduct;
use App\Models\VisaVendor;
use App\Services\VisaCataloguePublicationService;
use App\Services\VisaEligibilityService;
use App\Services\VisaFormWorkflow;
use Database\Seeders\CountrySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class VisaCatalogueTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_product_is_available_for_the_entire_effective_calendar_day(): void
    {
        $country = Country::query()->create(['alpha2' => 'IE', 'name' => 'Ireland']);
        $product = VisaProduct::query()->create([
            'destination_country_id' => $country->id,
            'name' => 'Ireland Study Visa',
            'slug' => 'ireland-study-visa',
            'category' => 'study',
            'publication_status' => 'published',
            'eligibility_mode' => 'all',
            'published_at' => now(),
            'effective_from' => today()->setTime(23, 59),
            'effective_until' => today()->setTime(0, 1),
        ]);

        $this->assertTrue(VisaProduct::query()->currentlyPublished()->whereKey($product)->exists());
    }

    public function test_administrator_can_open_the_guided_create_and_edit_forms(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $product = $this->completeProduct();

        $this->actingAs($admin)->get(VisaProductResource::getUrl('create'))->assertOk()->assertSee('Basics')->assertSee('Processing &amp; pricing', false)->assertSee('Visa type')->assertSee('Two entries');
        $this->actingAs($admin)->get(VisaProductResource::getUrl('edit', ['record' => $product]))->assertOk()->assertSee($product->name);
        $this->actingAs($admin)->get(VisaDestinationResource::getUrl('create'))->assertOk()->assertSee('Regional visa destination');
    }

    public function test_new_processing_option_can_be_linked_to_a_fee_before_first_save(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $destination = Country::query()->create(['alpha2' => 'GB', 'name' => 'United Kingdom']);
        $vendor = VisaVendor::query()->create(['name' => 'Test Vendor', 'email' => 'vendor@example.com', 'is_active' => true]);
        $code = (string) Str::uuid();
        $serviceCode = (string) Str::uuid();

        Livewire::actingAs($admin)->test(CreateVisaProduct::class)->fillForm([
            'destination_country_id' => $destination->id, 'visa_vendor_id' => $vendor->id, 'name' => 'Test Visitor Visa', 'slug' => 'test-visitor-visa', 'family' => 'standard', 'category' => 'tourist', 'entry_type' => 'single', 'eligibility_mode' => 'all',
            'processingOptions' => [['code' => $code, 'name' => 'Priority', 'minimum_business_days' => 2, 'maximum_business_days' => 3, 'is_active' => true]],
            'fees' => [['name' => 'Priority surcharge', 'fee_type' => 'processing', 'traveler_type' => 'all', 'calculation_basis' => 'per_application', 'processing_option_code' => $code, 'currency' => 'NGN', 'amount' => 25000, 'payee' => 'travelwheel', 'pay_online' => true, 'is_active' => true]],
            'optionalServices' => [['code' => $serviceCode, 'service_type' => 'insurance', 'name' => 'Travel insurance', 'pricing_model' => 'included', 'is_active' => true]],
            'requirements' => [['optional_service_code' => $serviceCode, 'name' => 'Insurance information form', 'category' => 'supporting_document', 'scope' => 'application', 'requirement_state' => 'required', 'maximum_file_size_kb' => 10240, 'is_active' => true]],
        ])->call('create')->assertHasNoFormErrors();

        $product = VisaProduct::query()->where('slug', 'test-visitor-visa')->firstOrFail();
        $this->assertSame($code, $product->processingOptions()->firstOrFail()->code);
        $this->assertSame($code, $product->fees()->firstOrFail()->processing_option_code);
        $this->assertSame($serviceCode, $product->requirements()->firstOrFail()->optional_service_code);
    }

    public function test_country_seeder_provides_the_complete_selectable_iso_directory(): void
    {
        $this->seed(CountrySeeder::class);

        $this->assertGreaterThanOrEqual(240, Country::query()->count());
        $this->assertDatabaseHas('countries', ['alpha2' => 'NG', 'name' => 'Nigeria', 'is_active' => true]);
        $this->assertDatabaseHas('countries', ['alpha2' => 'US', 'alpha3' => 'USA', 'is_active' => true]);
    }

    public function test_new_product_defaults_to_all_nationalities_and_draft(): void
    {
        $country = Country::query()->create(['alpha2' => 'NG', 'name' => 'Nigeria']);
        $product = VisaProduct::query()->create([
            'destination_country_id' => $country->id,
            'name' => 'Business VOA',
            'slug' => 'business-voa',
            'category' => 'business',
        ]);

        $this->assertSame(VisaEligibilityMode::All, $product->eligibility_mode);
        $this->assertSame(VisaPublicationStatus::Draft, $product->publication_status);
    }

    public function test_complete_product_can_be_published(): void
    {
        $product = $this->completeProduct();

        app(VisaCataloguePublicationService::class)->publish($product);

        $this->assertSame(VisaPublicationStatus::Published, $product->fresh()->publication_status);
        $this->assertNotNull($product->fresh()->published_at);
    }

    public function test_incomplete_product_cannot_be_published(): void
    {
        $country = Country::query()->create(['alpha2' => 'GH', 'name' => 'Ghana']);
        $product = VisaProduct::query()->create([
            'destination_country_id' => $country->id,
            'name' => 'Incomplete',
            'slug' => 'incomplete',
            'category' => 'tourist',
        ]);

        $this->expectException(ValidationException::class);
        app(VisaCataloguePublicationService::class)->publish($product);
    }

    public function test_rule_mode_requires_a_positive_inclusion(): void
    {
        $product = $this->completeProduct();
        $product->update(['eligibility_mode' => 'rules']);
        $product->eligibilityRules()->create(['rule_type' => 'exclude_country', 'country_id' => $product->destination_country_id]);

        $errors = app(VisaCataloguePublicationService::class)->errors($product->fresh());

        $this->assertContains('Rule-based eligibility requires at least one active country or country-group inclusion.', $errors);
    }

    public function test_country_groups_can_contain_any_active_country(): void
    {
        $nigeria = Country::query()->create(['alpha2' => 'NG', 'name' => 'Nigeria']);
        $canada = Country::query()->create(['alpha2' => 'CA', 'name' => 'Canada']);
        $group = CountryGroup::query()->create(['name' => 'Selected markets', 'slug' => 'selected-markets']);
        $group->countries()->sync([$nigeria->id, $canada->id]);

        $this->assertCount(2, $group->countries);
    }

    public function test_exclusion_overrides_group_inclusion(): void
    {
        $product = $this->completeProduct();
        $canada = Country::query()->create(['alpha2' => 'CA', 'name' => 'Canada']);
        $group = CountryGroup::query()->create(['name' => 'North America', 'slug' => 'north-america']);
        $group->countries()->attach($canada);
        $product->update(['eligibility_mode' => 'rules']);
        $product->eligibilityRules()->create(['rule_type' => 'include_group', 'country_group_id' => $group->id]);
        $product->eligibilityRules()->create(['rule_type' => 'exclude_country', 'country_id' => $canada->id, 'public_message' => 'Temporarily unavailable.']);

        $result = app(VisaEligibilityService::class)->evaluate($product->fresh(), $canada);

        $this->assertSame('ineligible', $result->status);
        $this->assertSame(['Temporarily unavailable.'], $result->messages);
    }

    public function test_all_nationalities_mode_is_eligible_without_rules(): void
    {
        $product = $this->completeProduct();
        $ghana = Country::query()->create(['alpha2' => 'GH', 'name' => 'Ghana']);

        $result = app(VisaEligibilityService::class)->evaluate($product, $ghana);

        $this->assertTrue($result->isEligible());
    }

    public function test_child_configuration_changes_increment_product_version(): void
    {
        $product = $this->completeProduct();
        $version = $product->version;

        $product->questions()->create([
            'key' => 'employer_name',
            'label' => 'Employer name',
            'input_type' => 'text',
        ]);

        $this->assertGreaterThan($version, $product->fresh()->version);
    }

    public function test_application_questions_require_no_manual_form_step_assignment(): void
    {
        $product = $this->completeProduct();
        $product->questions()->create(['key' => 'employer_name', 'label' => 'Employer name', 'input_type' => 'text', 'is_active' => true]);
        $product->update(['form_configuration' => app(VisaFormWorkflow::class)->defaults()]);

        $errors = app(VisaCataloguePublicationService::class)->errors($product->fresh());

        $this->assertSame([], $errors);
    }

    private function completeProduct(): VisaProduct
    {
        $country = Country::query()->create(['alpha2' => 'NG', 'name' => 'Nigeria']);
        $product = VisaProduct::query()->create([
            'destination_country_id' => $country->id,
            'name' => 'Business VOA',
            'slug' => 'business-voa',
            'category' => 'business',
            'entry_type' => 'single',
        ]);
        $product->processingOptions()->create(['name' => 'Standard', 'minimum_business_days' => 3, 'maximum_business_days' => 5]);
        $product->fees()->create(['name' => 'Service fee', 'fee_type' => 'service', 'currency' => 'NGN', 'amount' => 50000, 'payee' => 'travelwheel', 'pay_online' => true]);
        $product->requirements()->create(['name' => 'Passport data page', 'category' => 'passport']);

        return $product->fresh();
    }
}
