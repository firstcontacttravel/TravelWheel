<?php

namespace Tests\Feature;

use App\Filament\Pages\NewBooking;
use App\Mail\StaffBookingPaymentLinkMail;
use App\Models\Department;
use App\Models\FlightBooking;
use App\Models\PaymentVerificationRecord;
use App\Models\StaffBooking;
use App\Models\User;
use App\Services\Flights\FlightSupplierControl;
use App\Services\Flights\StaffFlightBooking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class StaffFlightBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Support\FlightMarkup::forgetCachedConfiguration();

        $control = app(FlightSupplierControl::class);
        $control->enable('travelnext', null);
        $control->enable('skylink', null);

        Mail::fake();

        Http::fake(function (Request $request) {
            $url = $request->url();

            return match (true) {
                str_ends_with($url, 'booking') => Http::response(['BookFlightResponse' => ['BookFlightResult' => [
                    'Success' => 'true', 'UniqueID' => 'TN-HOLD-1', 'Status' => 'CONFIRMED', 'TktTimeLimit' => now()->addDay()->format('Y-m-d\TH:i:s'),
                ]]]),
                default => Http::response([]),
            };
        });
    }

    private function staff(string $department = 'operations'): User
    {
        return User::factory()->create(['department_id' => Department::where('slug', $department)->value('id')]);
    }

    private function flight(array $overrides = []): array
    {
        return array_merge([
            'source' => 'travelnext', 'fareSourceCode' => 'fare_tn', 'fareType' => 'Public', 'price' => 930000.0,
            'supplierPrice' => 900000.0, 'markupAmount' => 30000.0, 'currency' => 'NGN', 'airline' => 'Royal Air Maroc',
            'cabin' => 'Economy', 'isPassportMandatory' => false,
            'fareBreakdown' => [['qty' => 1, 'currency' => 'NGN', 'paxType' => 'ADT']],
            'segments' => [[
                'from' => 'LOS', 'to' => 'DXB', 'fromCountry' => 'Nigeria', 'toCountry' => 'UAE',
                'departDT' => now()->addDays(40)->toIso8601String(), 'arriveDT' => now()->addDays(41)->toIso8601String(),
                'flightNo' => 'AT576', 'airline' => 'Royal Air Maroc', 'cabin' => 'Economy',
            ]],
        ], $overrides);
    }

    private function atCheckout(array $flight = []): void
    {
        session([
            'bookingFlight' => $this->flight($flight),
            'bookingSearchParams' => ['adults' => 1, 'childs' => 0, 'kids' => 0],
            'bookingSessionId' => 'sess-1',
        ]);
    }

    private function bookPayload(): array
    {
        return [
            'fare_source_code' => 'fare_tn',
            'contact' => ['email' => 'customer@example.com', 'phone' => '8000000000', 'area_code' => '080', 'country_code' => '234'],
            'passengers' => [[
                'type' => 'ADT', 'title' => 'Mrs', 'first_name' => 'Ngozi', 'last_name' => 'Eze',
                'gender' => 'F', 'dob' => '1985-03-14', 'nationality' => 'NG',
            ]],
        ];
    }

    /** Staff start from New Booking, then book on the website. */
    private function heldByStaff(bool $sendLink = false): FlightBooking
    {
        $staff = $this->staff();
        $this->actingAs($staff);
        app(StaffFlightBooking::class)->start($staff, $sendLink);
        $this->atCheckout();

        $this->post(route('flights.book'), $this->bookPayload())->assertRedirect(NewBooking::getUrl());

        return FlightBooking::query()->sole();
    }

    public function test_staff_start_from_new_booking_and_land_on_the_flight_search(): void
    {
        $staff = $this->staff();
        $this->actingAs($staff);

        Livewire::test(NewBooking::class)
            ->callAction('bookFlight', data: ['send_link' => false])
            ->assertRedirect(url('/'));

        $this->assertTrue(app(StaffFlightBooking::class)->active());
        $this->assertFalse(session(StaffFlightBooking::SESSION_KEY)['send_link']);
    }

    public function test_the_website_says_staff_are_booking_and_stop_ends_it(): void
    {
        $staff = $this->staff();
        $this->actingAs($staff);
        app(StaffFlightBooking::class)->start($staff, true);
        $this->atCheckout();

        $this->get(route('flights.booking'))->assertOk()->assertSee('staff booking a flight for a customer');

        $this->post(route('flights.staff-booking.stop'))->assertRedirect(NewBooking::getUrl());
        $this->assertFalse(app(StaffFlightBooking::class)->active());
    }

    public function test_a_held_fare_becomes_a_staff_booking_and_nothing_is_paid(): void
    {
        $booking = $this->heldByStaff();

        $this->assertSame(['on_hold', 'pending', 'TN-HOLD-1'], [$booking->booking_status, $booking->payment_status, $booking->unique_id]);
        $this->assertSame(auth()->id(), $booking->booked_by_user_id);
        $this->assertFalse((bool) $booking->pending_email_sent, "The website's hold email is not sent: staff chose whether to email.");

        $staffBooking = StaffBooking::query()->sole();
        $this->assertTrue($staffBooking->bookable->is($booking));
        $this->assertSame([$booking->booking_ref, 'pending', 930000.0, 'Ngozi Eze'], [$staffBooking->bookingReference(), $staffBooking->paymentState(), $staffBooking->total(), $staffBooking->customerName()]);
        $this->assertFalse(app(StaffFlightBooking::class)->active(), 'One booking per start.');
        $this->assertNull(session('flightBookingDbId'), "Staff are out of the customer's checkout.");
        Mail::assertNotSent(StaffBookingPaymentLinkMail::class);
    }

    public function test_the_customer_is_emailed_a_link_that_opens_the_payment_page(): void
    {
        $booking = $this->heldByStaff(sendLink: true);
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

    public function test_fares_paid_at_booking_cannot_be_held_for_a_customer(): void
    {
        $staff = $this->staff();
        $this->actingAs($staff);

        foreach ([['source' => 'skylink'], ['fareType' => 'WebFare']] as $fare) {
            app(StaffFlightBooking::class)->start($staff, false);
            $this->atCheckout($fare);

            $this->post(route('flights.book'), $this->bookPayload())
                ->assertRedirect(route('air.flight-s'))
                ->assertSessionHasErrors('error');
        }

        $this->assertSame(0, FlightBooking::query()->count());
        $this->assertSame(0, StaffBooking::query()->count());
    }

    public function test_a_customer_booking_on_their_own_is_unchanged(): void
    {
        $this->atCheckout();

        $this->post(route('flights.book'), $this->bookPayload())->assertRedirect(route('flights.payment.options'));

        $this->assertSame(0, StaffBooking::query()->count());
        $this->assertTrue((bool) FlightBooking::query()->sole()->pending_email_sent);
    }

    public function test_finance_records_a_payment_and_the_flight_team_orders_the_ticket(): void
    {
        $booking = $this->heldByStaff();
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
        $booking = $this->heldByStaff();
        $booking->update(['tkt_time_limit' => now()->subMinute()]);
        $staffBooking = StaffBooking::query()->sole();

        $this->assertSame('expired', $staffBooking->paymentState());
        $this->actingAs($this->staff('finance'));
        Livewire::test(NewBooking::class)->assertTableActionHidden('recordPayment', $staffBooking);
    }

    public function test_a_cancelled_booking_closes_and_its_link_stops_working(): void
    {
        $booking = $this->heldByStaff();
        $staffBooking = StaffBooking::query()->sole();

        Livewire::test(NewBooking::class)->callTableAction('cancelBooking', $staffBooking);

        $booking->refresh();
        $this->assertSame(['cancelled', 'cancelled'], [$booking->booking_status, $booking->payment_status]);
        $this->assertSame('cancelled', $staffBooking->fresh()->paymentState());
        $this->get($staffBooking->payUrl())->assertOk()->assertSee('no longer available');
    }

    public function test_staff_mode_belongs_to_the_staff_member_who_started_it(): void
    {
        $staff = $this->staff();
        $this->actingAs($staff);
        app(StaffFlightBooking::class)->start($staff, false);

        $this->actingAs($this->staff('customer-support'));
        $this->assertFalse(app(StaffFlightBooking::class)->active());
    }
}
