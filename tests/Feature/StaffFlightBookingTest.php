<?php

namespace Tests\Feature;

use App\Filament\Pages\BookFlight;
use App\Filament\Pages\NewBooking;
use App\Mail\StaffBookingPaymentLinkMail;
use App\Models\Department;
use App\Models\ExchangeRate;
use App\Models\FlightBooking;
use App\Models\PaymentVerificationRecord;
use App\Models\StaffBooking;
use App\Models\User;
use App\Services\Flights\FlightSupplierControl;
use App\Services\SkylinkFlightService;
use App\Services\TravelnextFlightService;
use App\Support\FlightMarkup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

class StaffFlightBookingTest extends TestCase
{
    use RefreshDatabase;

    /** What the airline's re-confirmation says the fare costs, in USD before markup. */
    private float $confirmedPrice = 620.0;

    private bool $passportMandatory = false;

    private string $holdAnswer = 'held';

    private bool $skylinkDown = false;

    protected function setUp(): void
    {
        parent::setUp();
        FlightMarkup::forgetCachedConfiguration();
        ExchangeRate::query()->updateOrCreate(['currency' => 'USD'], ['rate' => 1500]);

        $control = app(FlightSupplierControl::class);
        $control->enable('travelnext', null);
        $control->enable('skylink', null);

        Mail::fake();

        Http::fake(function (Request $request) {
            if (! str_ends_with($request->url(), 'booking')) {
                return Http::response([]);
            }

            return match ($this->holdAnswer) {
                'refused' => Http::response(['BookFlightResponse' => ['BookFlightResult' => ['Success' => 'false', 'Errors' => [['Message' => 'Fare gone']]]]]),
                'timeout' => throw new \Illuminate\Http\Client\ConnectionException('Timed out'),
                default => Http::response(['BookFlightResponse' => ['BookFlightResult' => [
                    'Success' => 'true', 'UniqueID' => 'TN-HOLD-1', 'Status' => 'CONFIRMED', 'TktTimeLimit' => now()->addDay()->format('Y-m-d\TH:i:s'),
                ]]]),
            };
        });

        // The search and re-confirmation, as TravelNext would answer them; the hold itself goes through the real book()
        $this->partialMock(TravelnextFlightService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('search')->andReturnUsing(fn () => ['error' => false, 'message' => null, 'data' => [
                'flights' => [$this->flight('fare_public'), $this->flight('fare_web', ['fareType' => 'WebFare', 'price' => 400.0])],
                'meta' => ['session_id' => 'tn-session'],
            ]]);
            $mock->shouldReceive('select')->andReturnUsing(fn (string $code) => ['error' => false, 'message' => null, 'data' => [
                'flight' => $this->flight($code, ['price' => $this->confirmedPrice, 'isPassportMandatory' => $this->passportMandatory]),
                'extraServices' => [], 'fareRules' => [],
            ]]);
        });

        // Can't hold with the airline: its fares are saved on our side to pay later
        $this->mock(SkylinkFlightService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('key')->andReturn('skylink');
            $mock->shouldReceive('supportsHold')->andReturn(false);
            $mock->shouldReceive('label')->andReturn('SkyLink');
            $mock->shouldReceive('search')->andReturnUsing(fn () => $this->skylinkDown ? ['error' => true, 'message' => 'SkyLink flight search is temporarily unavailable.', 'data' => []] : ['error' => false, 'message' => null, 'data' => [
                'flights' => [$this->flight('sky_1', ['source' => 'skylink', 'price' => 580.0])], 'meta' => [],
            ]]);
            $mock->shouldReceive('select')->andReturnUsing(fn (string $code) => ['error' => false, 'message' => null, 'data' => [
                'flight' => $this->flight($code, ['source' => 'skylink', 'price' => 590.0]), 'extraServices' => [], 'fareRules' => [],
            ]]);
        });
    }

    private function flight(string $code, array $overrides = []): array
    {
        return array_merge([
            'source' => 'travelnext', 'fareSourceCode' => $code, 'fareType' => 'Public', 'price' => 600.0, 'currency' => 'USD',
            'airline' => 'Royal Air Maroc', 'cabin' => 'Economy', 'cabinCode' => 'Y', 'isPassportMandatory' => false, 'isRefundable' => false,
            'stops' => 1, 'durationLabel' => '9h 10m', 'directionInd' => 'OneWay',
            'fareBreakdown' => [['qty' => 1, 'currency' => 'USD', 'paxType' => 'ADT']],
            'departDT' => now()->addDays(40)->setTime(10, 0)->toIso8601String(),
            'segments' => [[
                'from' => 'LOS', 'to' => 'CMN', 'departDT' => now()->addDays(40)->setTime(10, 0)->toIso8601String(),
                'arriveDT' => now()->addDays(40)->setTime(16, 0)->toIso8601String(), 'flightNo' => 'AT554', 'airline' => 'Royal Air Maroc', 'cabin' => 'Economy',
            ]],
        ], $overrides);
    }

    private function staff(string $department = 'operations'): User
    {
        return User::factory()->create(['department_id' => Department::where('slug', $department)->value('id')]);
    }

    private function searched()
    {
        return Livewire::test(BookFlight::class)
            ->set('from', 'Lagos - Murtala Muhammed (LOS)')
            ->set('to', 'Dubai - Dubai International (DXB)')
            ->set('depart', now()->addDays(40)->toDateString())
            ->call('search');
    }

    private function chosen()
    {
        return $this->searched()->call('choose', 'travelnext', 'fare_public');
    }

    private function filledIn($page, array $passenger = [])
    {
        return $page
            ->set('passengers.0', array_merge([
                'type' => 'ADT', 'title' => 'Mrs', 'first_name' => 'Ngozi', 'last_name' => 'Eze', 'gender' => 'F', 'dob' => '1985-03-14', 'nationality' => 'NG',
                'passport_no' => '', 'passport_issue_country' => '', 'passport_issue_date' => '', 'passport_exp' => '',
            ], $passenger))
            ->set('contact.email', 'customer@example.com')
            ->set('contact.phone', '8030000000');
    }

    private function held(bool $sendLink = false): FlightBooking
    {
        $this->actingAs($this->staff());
        $this->filledIn($this->chosen())->set('sendLink', $sendLink)->call('create')->assertRedirect(NewBooking::getUrl());

        return FlightBooking::query()->sole();
    }

    public function test_only_the_people_who_book_for_customers_can_open_it(): void
    {
        $this->actingAs($this->staff('finance'))->get(BookFlight::getUrl())->assertOk();
        $this->actingAs($this->staff('customer-support'))->get(BookFlight::getUrl())->assertForbidden();
    }

    public function test_the_search_shows_every_fare_and_how_it_can_be_held(): void
    {
        $this->actingAs($this->staff());

        $this->searched()
            ->assertHasNoErrors()
            ->assertSee('Choose this fare')
            ->assertSee(\App\Services\Flights\StaffFlightBooking::WEBFARE_REASON)
            ->assertViewHas('flights', fn ($flights) => $flights->pluck('holdKind', 'fareSourceCode')->all() === ['fare_web' => null, 'sky_1' => 'pay_later', 'fare_public' => 'airline']);
    }

    public function test_a_flight_api_that_does_not_answer_is_said_plainly(): void
    {
        $this->actingAs($this->staff());
        $this->skylinkDown = true;

        $this->searched()
            ->assertSet('searchError', fn ($error) => str_contains($error, 'SkyLink did not answer'))
            ->assertViewHas('flights', fn ($flights) => $flights->pluck('fareSourceCode')->sort()->values()->all() === ['fare_public', 'fare_web']);
    }

    public function test_a_webfare_cannot_be_chosen(): void
    {
        $this->actingAs($this->staff());

        $this->searched()->call('choose', 'travelnext', 'fare_web')
            ->assertSet('fareKey', null)
            ->assertSet('fareError', \App\Services\Flights\StaffFlightBooking::WEBFARE_REASON);
    }

    public function test_a_skylink_fare_is_saved_to_pay_later_and_the_customer_always_gets_the_card_link(): void
    {
        $this->actingAs($this->staff());

        $this->filledIn($this->searched()->call('choose', 'skylink', 'sky_1'))
            ->assertSee('not held with the airline')
            ->set('sendLink', false)
            ->call('create')
            ->assertRedirect(NewBooking::getUrl());

        $booking = FlightBooking::query()->sole();
        $this->assertSame(['skylink', 'pay_later', '', 'on_hold', 'pending', 'gateway'], [$booking->supplier, $booking->hold_type, (string) $booking->unique_id, $booking->booking_status, $booking->payment_status, $booking->payment_method]);
        $this->assertEqualsWithDelta(now()->addHours((int) config('flights.platform_hold.pay_later_hours', 24))->timestamp, $booking->tkt_time_limit->timestamp, 120);
        Http::assertNotSent(fn ($request) => str_ends_with($request->url(), 'booking'));
        Mail::assertSent(StaffBookingPaymentLinkMail::class, fn ($mail) => $mail->hasTo('customer@example.com'));

        $staffBooking = StaffBooking::query()->sole();
        $this->assertStringContainsString('/flights/hold/'.$booking->booking_ref, $staffBooking->checkoutUrl());
        $this->assertFalse($staffBooking->canRecordPayment(), 'Card only: the airline booking is made as the card payment goes through.');

        $this->actingAs($this->staff('finance'));
        Livewire::test(NewBooking::class)->assertTableActionHidden('recordPayment', $staffBooking);

        // The customer, in their own browser, lands on the card payment page
        auth()->logout();
        session()->flush();
        $resume = $this->get($staffBooking->payUrl())->assertRedirect()->headers->get('Location');
        $this->get($resume)->assertRedirect(route('flights.payment.gateway'));
        $this->get(route('flights.payment.gateway'))->assertOk()->assertSee('Royal Air Maroc');
    }

    public function test_an_airport_must_be_chosen_from_the_list(): void
    {
        $this->actingAs($this->staff());

        Livewire::test(BookFlight::class)
            ->set('from', 'Lagos')
            ->set('to', 'Dubai - Dubai International (DXB)')
            ->set('depart', now()->addDays(40)->toDateString())
            ->call('search')
            ->assertHasErrors('from');
    }

    public function test_choosing_a_fare_reconfirms_its_price_and_asks_for_each_passenger(): void
    {
        $this->actingAs($this->staff());

        $page = $this->searched()->call('changeCount', 'children', 1)->call('search')->call('choose', 'travelnext', 'fare_public');

        $expected = FlightMarkup::apply($this->flight('fare_public', ['price' => $this->confirmedPrice]), null)['price'];
        $page->assertViewHas('fare', fn ($fare) => (float) $fare['price'] === (float) $expected)
            ->assertSet('passengers', fn ($passengers) => array_column($passengers, 'type') === ['ADT', 'CHD']);
    }

    public function test_changing_the_search_drops_the_chosen_fare(): void
    {
        $this->actingAs($this->staff());

        $this->chosen()->set('depart', now()->addDays(41)->toDateString())
            ->assertSet('fareKey', null)
            ->assertSet('searchId', null)
            ->assertSet('passengers', []);
    }

    public function test_staff_hold_the_fare_and_it_joins_the_staff_bookings_unpaid(): void
    {
        $booking = $this->held();

        $this->assertSame(['on_hold', 'pending', 'TN-HOLD-1', 'tn-session'], [$booking->booking_status, $booking->payment_status, $booking->unique_id, $booking->session_id]);
        $this->assertSame(auth()->id(), $booking->booked_by_user_id);
        $this->assertSame((float) FlightMarkup::apply($this->flight('fare_public', ['price' => $this->confirmedPrice]), null)['price'], (float) $booking->total_price);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), 'booking') && $request['paxInfo']['customerEmail'] === 'customer@example.com');

        $staffBooking = StaffBooking::query()->sole();
        $this->assertTrue($staffBooking->bookable->is($booking));
        $this->assertSame([$booking->booking_ref, 'pending', 'Ngozi Eze'], [$staffBooking->bookingReference(), $staffBooking->paymentState(), $staffBooking->customerName()]);
        Mail::assertNotSent(StaffBookingPaymentLinkMail::class);
    }

    public function test_the_passengers_are_checked_as_on_the_website(): void
    {
        $this->actingAs($this->staff());

        $this->filledIn($this->chosen(), ['dob' => now()->subYears(10)->toDateString()])->call('create')->assertHasErrors('passengers.0.dob');

        $this->passportMandatory = true;
        $this->filledIn($this->chosen())->call('create')->assertHasErrors('passengers.0.passport_no');

        $this->assertSame(0, FlightBooking::query()->count());
        Http::assertNotSent(fn ($request) => str_ends_with($request->url(), 'booking'));
    }

    public function test_an_airline_that_refuses_the_hold_is_explained(): void
    {
        $this->actingAs($this->staff());
        $this->holdAnswer = 'refused';

        $this->filledIn($this->chosen())->call('create')->assertSet('fareError', 'The airline could not hold this fare. Fare gone');

        $this->assertSame(0, FlightBooking::query()->count());
    }

    public function test_an_airline_that_does_not_answer_is_not_retried_blindly(): void
    {
        $this->actingAs($this->staff());
        $this->holdAnswer = 'timeout';

        $this->filledIn($this->chosen())->call('create')
            ->assertSet('fareError', fn ($error) => str_contains($error, 'may or may not be held'));

        $this->assertSame(0, StaffBooking::query()->count());
    }

    public function test_the_customer_is_emailed_a_link_that_opens_the_payment_page(): void
    {
        $booking = $this->held(sendLink: true);
        $staffBooking = StaffBooking::query()->sole();

        Mail::assertSent(StaffBookingPaymentLinkMail::class, fn ($mail) => $mail->hasTo('customer@example.com'));

        // The customer, in their own browser
        auth()->logout();
        session()->flush();
        $resume = $this->get($staffBooking->payUrl())->assertRedirect()->headers->get('Location');
        $this->assertStringContainsString('/flights/payment/options/resume/'.$booking->booking_ref, $resume);
        $this->get($resume)->assertRedirect(route('flights.payment.options'));
        $this->get(route('flights.payment.options'))->assertOk();
    }

    public function test_finance_records_a_payment_and_the_flight_team_orders_the_ticket(): void
    {
        $booking = $this->held();
        $staffBooking = StaffBooking::query()->sole();

        $this->actingAs($this->staff('finance'));
        Livewire::test(NewBooking::class)
            ->callTableAction('recordPayment', $staffBooking, data: ['method' => 'pos', 'reference' => 'POS-77'])
            ->assertHasNoTableActionErrors();

        $booking->refresh();
        $this->assertSame(['paid', 'pos', 'POS-77', 'on_hold'], [$booking->payment_status, $booking->payment_method, $booking->bank_transfer_reference, $booking->booking_status]);
        $this->assertNotNull($booking->payment_verified_at);
        $this->assertFalse((bool) $booking->ticket_ordered);
        Http::assertNotSent(fn ($request) => str_ends_with($request->url(), 'ticket_order'));
        $this->assertDatabaseHas(PaymentVerificationRecord::class, ['flight_booking_id' => $booking->id, 'action' => 'staff_booking_payment_recorded', 'payment_reference' => 'POS-77']);
        $this->assertSame('paid', $staffBooking->fresh()->paymentState());
    }

    public function test_an_expired_hold_cannot_be_paid(): void
    {
        $booking = $this->held();
        $booking->update(['tkt_time_limit' => now()->subMinute()]);
        $staffBooking = StaffBooking::query()->sole();

        $this->assertSame('expired', $staffBooking->paymentState());
        $this->actingAs($this->staff('finance'));
        Livewire::test(NewBooking::class)->assertTableActionHidden('recordPayment', $staffBooking);
    }

    public function test_a_cancelled_booking_closes_and_its_link_stops_working(): void
    {
        $booking = $this->held();
        $staffBooking = StaffBooking::query()->sole();

        Livewire::test(NewBooking::class)->callTableAction('cancelBooking', $staffBooking);

        $booking->refresh();
        $this->assertSame(['cancelled', 'cancelled'], [$booking->booking_status, $booking->payment_status]);
        $this->assertSame('cancelled', $staffBooking->fresh()->paymentState());
        $this->get($staffBooking->payUrl())->assertOk()->assertSee('no longer available');
    }
}
