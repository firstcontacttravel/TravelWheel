<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\ExchangeRate;
use App\Models\VisaApplication;
use App\Models\VisaProduct;
use App\Services\VisaPaymentService;
use App\Services\VisaQuotationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class VisaPricingPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_server_quote_snapshots_lines_rates_and_excludes_authority_direct_fees(): void
    {
        $application = $this->application();
        $quote = app(VisaQuotationService::class)->create($application);

        $this->assertSame('active', $quote->status);
        $this->assertSame('200500.00', $quote->payable_total);
        $this->assertSame(3, $quote->items->count());
        $this->assertSame(1000.0, (float) data_get($quote->exchange_rate_snapshot, 'USD.rate'));
        $this->assertSame('awaiting_payment', $application->fresh()->status);
        $authority = $quote->items->firstWhere('payee', 'authority');
        $this->assertFalse($authority->pay_online);
        $this->assertSame('20000.00', $authority->checkout_total);
    }

    public function test_visa_quotes_use_the_shared_exchange_rates_table_for_gbp(): void
    {
        $application = $this->application();
        $application->product->fees()->where('name', 'Adult visa fee')->update(['currency' => 'GBP']);
        ExchangeRate::query()->updateOrCreate(['currency' => 'GBP'], ['rate' => 2000]);

        $quote = app(VisaQuotationService::class)->create($application->fresh());

        $this->assertSame(2000.0, (float) data_get($quote->exchange_rate_snapshot, 'GBP.rate'));
        $this->assertSame('exchange_rates', data_get($quote->exchange_rate_snapshot, 'GBP.source'));
        $this->assertSame('400500.00', $quote->payable_total);
    }

    public function test_quote_readiness_uses_the_automatic_snapshotted_workflow(): void
    {
        $application = $this->application();
        $configuration = [
            'traveler_fields' => [],
            'passport_fields' => [],
            'steps' => ['hasQuestions' => false, 'hasServices' => false, 'hasDocuments' => false],
        ];
        $application->update(['form_configuration' => $configuration, 'current_step' => 3, 'completed_step' => 2, 'declaration_accepted' => true, 'declaration_accepted_at' => now()]);

        $quote = app(VisaQuotationService::class)->create($application->fresh());

        $this->assertSame('active', $quote->status);
    }

    public function test_unchanged_quote_is_idempotent_and_price_change_supersedes_it(): void
    {
        $application = $this->application();
        $quotes = app(VisaQuotationService::class);
        $first = $quotes->create($application);
        $this->assertTrue($first->is($quotes->create($application->fresh())));

        $application->product->fees()->where('name', 'Service fee')->update(['amount' => 700]);
        $second = $quotes->create($application->fresh());

        $this->assertFalse($first->is($second));
        $this->assertSame('superseded', $first->fresh()->status);
        $this->assertSame('200700.00', $second->payable_total);
    }

    public function test_payment_initialization_uses_only_the_stored_quote_total(): void
    {
        Http::fake([
            '*/api/v2/encrypt/keys' => Http::response(['data' => ['EncryptedSecKey' => ['encryptedKey' => 'token']]]),
            '*/api/v2/payments' => Http::response(['data' => ['payments' => ['redirectLink' => 'https://checkout.example/pay']]]),
        ]);
        $quote = app(VisaQuotationService::class)->create($this->application());
        $payment = app(VisaPaymentService::class)->initialize($quote);

        $this->assertSame('pending', $payment->status);
        $this->assertSame('200500.00', $payment->expected_amount);
        $this->assertSame('NGN', $payment->expected_currency);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/api/v2/payments') && $request['amount'] === '200500.00' && $request['currency'] === 'NGN' && $request['paymentReference'] === $payment->reference);
    }

    public function test_exact_verified_payment_submits_once_across_duplicate_events(): void
    {
        $payment = $this->initializedPayment();
        Http::fake([
            '*/api/v2/encrypt/keys' => Http::response(['data' => ['EncryptedSecKey' => ['encryptedKey' => 'token']]]),
            '*/api/v3/payments/query/*' => Http::response(['data' => ['payments' => ['gatewayCode' => '00', 'gatewayMessage' => 'Successful', 'amount' => '200500.00', 'currency' => 'NGN']]]),
        ]);
        $service = app(VisaPaymentService::class);
        $service->verify($payment->reference, ['paymentReference' => $payment->reference], 'callback');
        $service->verify($payment->reference, ['paymentReference' => $payment->reference], 'callback');
        $service->verify($payment->reference, ['paymentReference' => $payment->reference], 'webhook');

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('submitted', $payment->application->fresh()->status);
        $this->assertSame('consumed', $payment->quote->fresh()->status);
        $this->assertDatabaseCount('visa_payment_events', 2);
        $this->assertDatabaseCount('visa_application_status_history', 3);
        $this->assertDatabaseHas('visa_notification_events', ['visa_application_id' => $payment->visa_application_id, 'event_type' => 'payment_confirmed']);
    }

    public function test_amount_or_currency_mismatch_is_rejected(): void
    {
        $payment = $this->initializedPayment();
        Http::fake([
            '*/api/v2/encrypt/keys' => Http::response(['data' => ['EncryptedSecKey' => ['encryptedKey' => 'token']]]),
            '*/api/v3/payments/query/*' => Http::response(['data' => ['payments' => ['gatewayCode' => '00', 'gatewayMessage' => 'Successful', 'amount' => '200499.00', 'currency' => 'USD']]]),
        ]);
        app(VisaPaymentService::class)->verify($payment->reference, [], 'webhook');

        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertSame('awaiting_payment', $payment->application->fresh()->status);
        $this->assertNotNull($payment->fresh()->failure_message);
    }

    public function test_customer_borne_gateway_fee_is_reconciled_against_the_quote(): void
    {
        $payment = $this->initializedPayment();
        Http::fake([
            '*/api/v2/encrypt/keys' => Http::response(['data' => ['EncryptedSecKey' => ['encryptedKey' => 'token']]]),
            '*/api/v3/payments/query/*' => Http::response(['data' => [
                'payments' => ['gatewayCode' => '00', 'gatewayMessage' => 'Successful', 'amount' => '202500.00', 'fee' => '2000.00', 'currency' => 'NGN'],
                'customers' => ['fee' => '2000.00'],
            ]]),
        ]);

        app(VisaPaymentService::class)->verify($payment->reference, [], 'callback');

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('202500.00', $payment->fresh()->verified_amount);
        $this->assertSame('submitted', $payment->application->fresh()->status);
    }

    public function test_webhook_and_callback_recover_payment_without_application_session(): void
    {
        $payment = $this->initializedPayment();
        Http::fake([
            '*/api/v2/encrypt/keys' => Http::response(['data' => ['EncryptedSecKey' => ['encryptedKey' => 'token']]]),
            '*/api/v3/payments/query/*' => Http::response(['data' => ['payments' => ['gatewayCode' => '00', 'gatewayMessage' => 'Successful', 'amount' => '200500.00', 'currency' => 'NGN']]]),
        ]);

        $this->postJson(route('visa.payments.webhook'), ['paymentReference' => $payment->reference])
            ->assertOk()->assertJson(['status' => 'received']);
        $this->get(route('visa.payments.callback', ['reference' => $payment->reference]))
            ->assertRedirect(route('visa.payments.result', $payment));

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('submitted', $payment->application->fresh()->status);
    }

    public function test_expired_quote_cannot_initialize_payment(): void
    {
        $quote = app(VisaQuotationService::class)->create($this->application());
        $quote->update(['expires_at' => now()->subMinute()]);

        $this->expectException(ValidationException::class);
        app(VisaPaymentService::class)->initialize($quote->fresh());
    }

    private function initializedPayment()
    {
        Http::fake([
            '*/api/v2/encrypt/keys' => Http::response(['data' => ['EncryptedSecKey' => ['encryptedKey' => 'token']]]),
            '*/api/v2/payments' => Http::response(['data' => ['payments' => ['redirectLink' => 'https://checkout.example/pay']]]),
        ]);

        return app(VisaPaymentService::class)->initialize(app(VisaQuotationService::class)->create($this->application()));
    }

    private function application(): VisaApplication
    {
        $nationality = Country::query()->create(['alpha2' => 'GH', 'name' => 'Ghana']);
        $destination = Country::query()->create(['alpha2' => 'CA', 'name' => 'Canada']);
        $product = VisaProduct::query()->create(['destination_country_id' => $destination->id, 'name' => 'Visitor visa', 'slug' => 'visitor-'.Str::random(6), 'family' => 'standard', 'category' => 'tourist', 'entry_type' => 'single', 'publication_status' => 'published', 'published_at' => now(), 'version' => 3]);
        $processing = $product->processingOptions()->create(['name' => 'Standard', 'minimum_business_days' => 5, 'maximum_business_days' => 10]);
        $product->fees()->create(['name' => 'Adult visa fee', 'fee_type' => 'visa', 'traveler_type' => 'adult', 'calculation_basis' => 'per_traveler', 'currency' => 'USD', 'amount' => 100, 'payee' => 'travelwheel', 'pay_online' => true]);
        $product->fees()->create(['name' => 'Service fee', 'fee_type' => 'service', 'traveler_type' => 'all', 'calculation_basis' => 'per_application', 'currency' => 'NGN', 'amount' => 500, 'payee' => 'travelwheel', 'pay_online' => true]);
        $product->fees()->create(['name' => 'Authority fee', 'fee_type' => 'authority', 'traveler_type' => 'all', 'calculation_basis' => 'per_application', 'currency' => 'USD', 'amount' => 20, 'payee' => 'authority', 'pay_online' => false]);
        ExchangeRate::query()->updateOrCreate(['currency' => 'USD'], ['rate' => 1000]);

        $application = VisaApplication::query()->create([
            'reference' => (string) Str::ulid(), 'resume_token_hash' => hash('sha256', 'token'), 'visa_product_id' => $product->id,
            'visa_processing_option_id' => $processing->id, 'product_version' => $product->version, 'status' => 'draft', 'current_step' => 8, 'completed_step' => 7,
            'nationality_country_id' => $nationality->id, 'destination_country_id' => $destination->id,
            'arrival_date' => now()->addMonth(), 'departure_date' => now()->addMonths(2), 'adult_count' => 2, 'contact_email' => 'pay@example.com',
            'declaration_accepted' => true, 'declaration_accepted_at' => now(), 'search_snapshot' => [], 'product_snapshot' => [], 'last_activity_at' => now(), 'expires_at' => now()->addDays(30),
        ]);
        foreach ([1, 2] as $position) {
            $application->travelers()->create(['reference' => (string) Str::ulid(), 'traveler_type' => 'adult', 'applicant_type' => 'individual', 'position' => $position, 'first_name' => 'Test', 'last_name' => 'Traveler', 'phone' => '+2348000000000']);
        }
        $application->statusHistory()->create(['to_status' => 'draft', 'actor_type' => 'applicant']);

        return $application->fresh();
    }
}
