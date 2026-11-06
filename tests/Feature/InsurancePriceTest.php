<?php

namespace Tests\Feature;

use App\Models\InsurancePurchase;
use App\Models\InsuranceQuote;
use App\Services\SeerbitPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsurancePriceTest extends TestCase
{
    use RefreshDatabase;

    private function quote(): InsuranceQuote
    {
        return InsuranceQuote::create(['quoteRequestId' => 'QR-100', 'bookingTypeId' => '1', 'noOfChildren' => '0', 'amount' => 40000]);
    }

    private function details(array $overrides = []): array
    {
        return array_merge([
            'qouteId' => 'QR-100', 'bookingTypeId' => '1', 'amount' => '1', 'surname' => 'Obi', 'firstname' => 'Ada',
            'email' => 'ada@example.com', 'phone_no' => '08030000000',
        ], $overrides);
    }

    public function test_the_checkout_shows_the_quotes_price_whatever_the_form_says(): void
    {
        $this->quote();

        $this->post(route('air.insurance.purchase'), $this->details())->assertOk()
            ->assertSee('value="40000"', false)
            ->assertSee('value="43000"', false)
            ->assertSee('43,000.00');

        $this->assertEquals(40000, session('insurance_data_form')['amount']);
    }

    public function test_payment_is_for_the_quotes_price_plus_vat_whatever_the_form_says(): void
    {
        $this->quote();
        $charged = null;
        $this->mock(SeerbitPaymentService::class, function ($mock) use (&$charged) {
            $mock->shouldReceive('initializePayment')->andReturnUsing(function (array $payment) use (&$charged) {
                $charged = $payment;

                return ['redirect_link' => 'https://pay.example.test/checkout'];
            });
        });

        $this->post(route('air.insurance.purchase'), $this->details());
        $this->post(route('air.insurance.pay'), ['p_amount' => '1', 'vat' => '0', 'c_amount' => '1', 'nok_fullname' => 'Kin'])
            ->assertRedirect('https://pay.example.test/checkout');

        $this->assertEquals(43000, (float) $charged['amount']);
        $form = session('insurance_data_form2');
        $this->assertEquals(43000, $form['p_amount']);
        $this->assertEquals(3000, $form['vat']);
        $this->assertSame($charged['paymentReference'], $form['reference']);
    }

    public function test_an_unknown_quote_is_refused(): void
    {
        $this->mock(SeerbitPaymentService::class, fn ($mock) => $mock->shouldNotReceive('initializePayment'));

        $this->post(route('air.insurance.purchase'), $this->details(['qouteId' => 'MADE-UP']))->assertRedirect(route('air.insurance'));

        $this->withSession(['insurance_data_form' => $this->details(['qouteId' => 'MADE-UP'])])
            ->post(route('air.insurance.pay'), ['p_amount' => '1'])
            ->assertRedirect(route('air.insurance'));
    }

    public function test_a_payment_for_another_checkout_cannot_claim_the_forms_in_the_session(): void
    {
        $this->mock(SeerbitPaymentService::class, fn ($mock) => $mock->shouldReceive('verifyPayment')->andReturn(['ok' => true, 'query_succeeded' => true]));

        $this->withSession([
            'insurance_data_form' => $this->details(),
            'insurance_data_form2' => ['reference' => 'INSEXPECTED', 'p_amount' => 43000],
        ])->get(route('air.insurance.callback', ['reference' => 'INSOTHER']))->assertRedirect(route('air.insurance'));

        $this->assertSame(0, InsurancePurchase::count());
    }
}
