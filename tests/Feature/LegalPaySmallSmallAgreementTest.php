<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegalPaySmallSmallAgreementTest extends TestCase
{
    public function test_legal_page_uses_the_fast_credit_agreement_from_travelflex(): void
    {
        $response = $this->get(route('legal.pay-small-small'));

        $response
            ->assertOk()
            ->assertSeeText('TravelFlex Fast Credit Loan Agreement')
            ->assertSeeText('IMPORTANT: SEPARATE ROLES OF TRAVELWHEEL AND FAST CREDIT')
            ->assertSeeText('These administrative and travel services do not make TravelWheel the lender, a co-lender, a guarantor, or the decision-maker under the loan.')
            ->assertSeeText('repayment obligations are between Fast Credit and the borrower, not TravelWheel.')
            ->assertSeeText('TravelWheel remains responsible for its own travel-booking services')
            ->assertSeeText('Fast Credit will charge interest on the loan amount at a fixed rate of 4% per month')
            ->assertDontSeeText('Pay Small Small enables you to spread the cost')
            ->assertDontSeeText('By using the TravelWheel website');
    }

    public function test_all_legal_pages_use_current_professional_copy(): void
    {
        $routes = [
            'legal.terms',
            'legal.privacy',
            'legal.refund',
            'legal.payment',
            'legal.booking-agreement',
            'legal.pay-small-small',
            'legal.insurance-terms',
            'legal.protocol-terms',
            'legal.cookies',
            'legal.disclaimer',
        ];

        foreach ($routes as $routeName) {
            $this->get(route($routeName))
                ->assertOk()
                ->assertSeeText('Last Updated: 31 July 2026')
                ->assertDontSeeText('Pay Small Small')
                ->assertDontSeeText('bound by this');
        }
    }

    public function test_terms_and_privacy_pages_include_current_required_disclosures(): void
    {
        $this->get(route('legal.terms'))
            ->assertOk()
            ->assertSeeText('Arbitration and Mediation Act 2023')
            ->assertSeeText('TravelWheel is not the lender');

        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSeeText('TravelFlex & Finance Referral Data')
            ->assertSeeText('Lawful Bases for Processing')
            ->assertSeeText('under its own privacy obligations as the finance provider.')
            ->assertSeeText('TravelWheel does not make the credit decision.');

        $this->get(route('legal.cookies'))
            ->assertOk()
            ->assertSeeText('Silence, inactivity')
            ->assertSeeText('does not constitute consent to non-essential cookies');
    }

    public function test_travelflex_application_uses_the_same_role_separation_and_clear_acceptance(): void
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
            ->assertSeeText('IMPORTANT: SEPARATE ROLES OF TRAVELWHEEL AND FAST CREDIT')
            ->assertSeeText('Fast Credit Limited, not TravelWheel, is the finance provider and lender')
            ->assertSeeText('agree to be bound by them');
    }
}
