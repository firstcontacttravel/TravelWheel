<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\VisaProduct;
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
