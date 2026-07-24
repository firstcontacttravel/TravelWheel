<?php

namespace Tests\Feature;

use Tests\TestCase;

class TravelFlexAddressAutocompleteTest extends TestCase
{
    public function test_application_uses_visible_legacy_places_autocomplete_with_a_manual_input_fallback(): void
    {
        $response = $this->withSession([
            'travelFlexPlan' => [
                'ticket_cost' => 500000,
                'down_payment' => 150000,
                'loan_amount' => 350000,
                'repayment_plan' => '24 hours',
            ],
            'bookingFlight' => [
                'flight' => [
                    'currency' => 'NGN',
                    'segments' => [],
                ],
            ],
        ])->get(route('flights.travelflex.application.get'));

        $response
            ->assertOk()
            ->assertSee('name="home_address"', false)
            ->assertSee('data-google-address', false)
            ->assertSee("new Autocomplete(input", false)
            ->assertSee("componentRestrictions: { country: 'ng' }", false)
            ->assertSee('libraries=places', false)
            ->assertDontSee('PlaceAutocompleteElement', false)
            ->assertDontSee("input.style.display = 'none'", false);
    }
}
