<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\VisaProduct;
use App\Models\VisaDestination;
use App\Models\VisaApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisaDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_visa_discovery_page_is_available(): void
    {
        $this->get(route('air.visa'))
            ->assertOk()
            ->assertSee('All Your Travel Needs In One Place')
            ->assertSee('Where do you want to go?')
            ->assertSee(route('visa.search'), false);
    }

    public function test_search_returns_published_standard_and_voa_products_with_estimates(): void
    {
        [$nationality, $destination] = $this->catalogueProduct('standard', 'Tourist visa');
        $this->additionalProduct($destination, 'voa', 'Business VOA');

        $this->post(route('visa.search'), $this->validSearch($nationality, $destination, ['adults' => 2, 'children' => 1]))
            ->assertRedirect(route('visa.search.loading'))
            ->assertSessionHas('pendingVisaSearch');

        $this->get(route('visa.search.loading'))
            ->assertOk()
            ->assertSee('Finding the right visa options for you')
            ->assertSee('Visa stamp passport.svg');

        $this->get(route('visa.search.run'))->assertRedirect(route('visa.results'));

        $this->get(route('visa.results'))
            ->assertOk()
            ->assertSee('Tourist visa')
            ->assertSee('Business VOA')
            ->assertSee('USD 250.00')
            ->assertSee('USD 20.00');
    }

    public function test_search_validates_dates_and_infant_to_adult_ratio(): void
    {
        [$nationality, $destination] = $this->catalogueProduct('standard', 'Tourist visa');

        $this->from(route('air.visa'))->post(route('visa.search'), $this->validSearch($nationality, $destination, [
            'arrival_date' => now()->subDay()->toDateString(),
            'departure_date' => now()->subDays(2)->toDateString(),
            'adults' => 1,
            'infants' => 2,
        ]))->assertRedirect(route('air.visa'))->assertSessionHasErrors(['arrival_date', 'departure_date', 'infants']);
    }

    public function test_unpublished_products_are_not_returned(): void
    {
        [$nationality, $destination] = $this->catalogueProduct('standard', 'Published visa');
        $this->additionalProduct($destination, 'standard', 'Hidden draft', false);
        $this->withSession(['pendingVisaSearch' => $this->validSearch($nationality, $destination)])
            ->get(route('visa.search.run'))
            ->assertRedirect(route('visa.results'));
        $this->get(route('visa.results'))->assertSee('Published visa')->assertDontSee('Hidden draft');
    }

    public function test_ineligible_products_and_other_nationality_fees_are_not_returned(): void
    {
        [$nationality, $destination, $product] = $this->catalogueProduct('voa', 'Nigeria VOA');
        $otherNationality = Country::query()->create(['alpha2' => 'CA', 'name' => 'Canada']);
        $product->update(['eligibility_mode' => 'rules']);
        $product->eligibilityRules()->create(['rule_type' => 'include_country', 'country_id' => $nationality->id]);
        $product->fees()->create([
            'name' => 'Canada-only fee',
            'fee_type' => 'visa',
            'traveler_type' => 'all',
            'calculation_basis' => 'per_traveler',
            'currency' => 'USD',
            'amount' => 999,
            'conditions' => ['nationality_country_id' => $otherNationality->id],
        ]);

        $this->withSession(['pendingVisaSearch' => $this->validSearch($nationality, $destination)])
            ->get(route('visa.search.run'))
            ->assertRedirect(route('visa.results'));

        $this->get(route('visa.results'))->assertSee('Nigeria VOA')->assertDontSee('USD 999.00');

        $this->withSession(['pendingVisaSearch' => $this->validSearch($otherNationality, $destination)])
            ->get(route('visa.search.run'));

        $this->get(route('visa.results'))->assertDontSee('Nigeria VOA');
    }

    public function test_loading_page_requires_a_pending_search(): void
    {
        $this->get(route('visa.search.loading'))->assertRedirect(route('air.visa'));
    }

    public function test_nigerian_business_visa_only_appears_for_foreign_passports_travelling_to_nigeria(): void
    {
        $canada = Country::query()->create(['alpha2' => 'CA', 'name' => 'Canada']);
        $nigeria = Country::query()->create(['alpha2' => 'NG', 'name' => 'Nigeria']);
        $ireland = Country::query()->create(['alpha2' => 'IE', 'name' => 'Ireland']);
        $businessVisa = $this->additionalProduct($nigeria, 'voa', 'Nigerian Business Visa');
        $businessVisa->update(['eligibility_mode' => 'all']);
        $staleWrongDestination = $this->additionalProduct($ireland, 'voa', 'Incorrect Ireland business visa');
        $staleWrongDestination->update(['eligibility_mode' => 'all']);

        $foreignToNigeria = app(\App\Services\VisaDiscoveryService::class)->search($canada, $nigeria, null, ['adult' => 1, 'child' => 0, 'infant' => 0]);
        $foreignElsewhere = app(\App\Services\VisaDiscoveryService::class)->search($canada, $ireland, null, ['adult' => 1, 'child' => 0, 'infant' => 0]);
        $nigerianToNigeria = app(\App\Services\VisaDiscoveryService::class)->search($nigeria, $nigeria, null, ['adult' => 1, 'child' => 0, 'infant' => 0]);

        $this->assertTrue($foreignToNigeria->contains('id', $businessVisa->id));
        $this->assertFalse($foreignElsewhere->contains('id', $staleWrongDestination->id));
        $this->assertFalse($nigerianToNigeria->contains('id', $businessVisa->id));
    }

    public function test_minor_parent_requirements_only_appear_when_the_search_includes_a_minor(): void
    {
        [$nationality, $destination, $product] = $this->catalogueProduct('standard', 'Family visa');
        $product->requirements()->create([
            'name' => "Father's data page",
            'category' => 'passport',
            'scope' => 'traveler',
            'requirement_state' => 'conditional',
            'conditions' => ['applicant_type' => 'minor_nigerian'],
        ]);

        $adultOnly = app(\App\Services\VisaDiscoveryService::class)
            ->search($nationality, $destination, null, ['adult' => 1, 'child' => 0, 'infant' => 0])
            ->firstWhere('id', $product->id);
        $withChild = app(\App\Services\VisaDiscoveryService::class)
            ->search($nationality, $destination, null, ['adult' => 1, 'child' => 1, 'infant' => 0])
            ->firstWhere('id', $product->id);

        $this->assertNotContains("Father's data page", array_column($adultOnly['requirements'], 'name'));
        $this->assertContains("Father's data page", array_column($withChild['requirements'], 'name'));
    }

    public function test_regional_destination_can_be_searched_directly_and_through_a_member_country(): void
    {
        $nationality = Country::query()->create(['alpha2' => 'NG', 'name' => 'Nigeria']);
        $france = Country::query()->create(['alpha2' => 'FR', 'name' => 'France']);
        $region = VisaDestination::query()->create(['name' => 'Schengen Area', 'slug' => 'schengen-area', 'is_active' => true]);
        $region->countries()->attach($france);
        $product = VisaProduct::query()->create([
            'visa_destination_id' => $region->id,
            'name' => 'Schengen Tourist Visa',
            'slug' => 'schengen-tourist-visa',
            'family' => 'standard',
            'category' => 'tourist',
            'entry_type' => 'multiple',
            'publication_status' => 'published',
            'published_at' => now(),
        ]);

        $direct = app(\App\Services\VisaDiscoveryService::class)->search($nationality, $region, null, ['adult' => 1, 'child' => 0, 'infant' => 0]);
        $throughMember = app(\App\Services\VisaDiscoveryService::class)->search($nationality, $france, null, ['adult' => 1, 'child' => 0, 'infant' => 0]);

        $this->assertTrue($direct->contains('id', $product->id));
        $this->assertTrue($throughMember->contains('id', $product->id));

        $search = $this->validSearch($nationality, $france, ['destination_ref' => 'region:'.$region->id]);
        unset($search['destination_id']);
        $this->post(route('visa.search'), $search)->assertRedirect(route('visa.search.loading'));
        $this->get(route('visa.search.run'))->assertRedirect(route('visa.results'));
        $this->get(route('visa.results'))->assertOk()->assertSee('Schengen Tourist Visa')->assertSee('Schengen Area');
        $this->post(route('visa.applications.start'), ['visa_product_id' => $product->id])->assertRedirect();
        $application = VisaApplication::query()->latest('id')->firstOrFail();
        $this->assertSame($region->id, $application->visa_destination_id);
        $this->assertNull($application->destination_country_id);
    }

    private function catalogueProduct(string $family, string $name): array
    {
        $nationality = Country::query()->create(['alpha2' => 'GH', 'name' => 'Ghana']);
        $destination = Country::query()->create(['alpha2' => 'NG', 'name' => 'Nigeria']);
        $product = $this->additionalProduct($destination, $family, $name);

        return [$nationality, $destination, $product];
    }

    private function validSearch(Country $nationality, Country $destination, array $overrides = []): array
    {
        return array_merge([
            'nationality_id' => $nationality->id,
            'residence_country_id' => null,
            'destination_id' => $destination->id,
            'arrival_date' => now()->addMonth()->toDateString(),
            'departure_date' => now()->addMonth()->addWeek()->toDateString(),
            'adults' => 1,
            'children' => 0,
            'infants' => 0,
        ], $overrides);
    }

    private function additionalProduct(Country $destination, string $family, string $name, bool $published = true): VisaProduct
    {
        $product = VisaProduct::query()->create([
            'destination_country_id' => $destination->id,
            'name' => $name,
            'slug' => str($name)->slug(),
            'family' => $family,
            'category' => $family === 'voa' ? 'business' : 'tourist',
            'entry_type' => 'single',
            'publication_status' => $published ? 'published' : 'draft',
            'published_at' => $published ? now() : null,
            'validity_days' => 90,
            'maximum_stay_days' => 30,
        ]);
        $product->processingOptions()->create(['name' => 'Standard', 'minimum_business_days' => 3, 'maximum_business_days' => 5]);
        $product->fees()->create(['name' => 'Adult fee', 'fee_type' => 'service', 'traveler_type' => 'adult', 'calculation_basis' => 'per_traveler', 'currency' => 'USD', 'amount' => 100, 'payee' => 'travelwheel', 'pay_online' => true]);
        $product->fees()->create(['name' => 'Child fee', 'fee_type' => 'service', 'traveler_type' => 'child', 'calculation_basis' => 'per_traveler', 'currency' => 'USD', 'amount' => 50, 'payee' => 'travelwheel', 'pay_online' => true]);
        $product->fees()->create(['name' => 'Authority fee', 'fee_type' => 'government', 'traveler_type' => 'all', 'calculation_basis' => 'per_application', 'currency' => 'USD', 'amount' => 20, 'payee' => 'authority', 'pay_online' => false]);
        $product->requirements()->create(['name' => 'Passport data page', 'category' => 'passport']);

        return $product->fresh();
    }
}
