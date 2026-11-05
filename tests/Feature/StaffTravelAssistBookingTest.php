<?php

namespace Tests\Feature;

use App\Filament\Pages\BookTravelAssist;
use App\Filament\Pages\NewBooking;
use App\Mail\StaffBookingPaymentLinkMail;
use App\Mail\SupportFlightAssistNotificationMail;
use App\Mail\SupportFlightAssistSuccessMail;
use App\Mail\SupportYellowCardNotificationMail;
use App\Mail\SupportYellowCardSuccessMail;
use App\Models\Department;
use App\Models\StaffBooking;
use App\Models\SupportFlightAssist;
use App\Models\SupportProductPrice;
use App\Models\SupportYellowCard;
use App\Models\User;
use App\Services\SeerbitPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StaffTravelAssistBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('public');
    }

    private function staff(string $department = 'operations'): User
    {
        return User::factory()->create(['department_id' => Department::where('slug', $department)->value('id')]);
    }

    private function flightAssistPage(array $overrides = [])
    {
        $values = array_merge([
            'requestType' => 'date_change', 'bookingSource' => 'airline', 'nameOnTicket' => 'Ada Obi',
            'airlineReference' => 'ABC123', 'airlineCategory' => 'local', 'airline' => 'Air Peace',
            'tripType' => 'one_way', 'travelDate' => now()->addDays(5)->toDateString(),
            'phone' => '08030000000', 'email' => 'ada@example.com',
        ], $overrides);

        $page = Livewire::test(BookTravelAssist::class);
        foreach ($values as $property => $value) {
            $page->set($property, $value);
        }

        return $page;
    }

    private function yellowCardPage(array $overrides = [])
    {
        $values = array_merge([
            'tab' => 'yellow_card', 'serviceType' => 'fasttrack', 'fullName' => 'Ada Obi',
            'email' => 'ada@example.com', 'phone' => '08030000000', 'homeAddress' => 'Wuse 2, Abuja',
            'deliveryAddress' => 'Maitama, Abuja', 'sendLink' => true,
            'dataPage' => UploadedFile::fake()->create('passport.pdf', 200, 'application/pdf'),
        ], $overrides);

        $page = Livewire::test(BookTravelAssist::class);
        foreach ($values as $property => $value) {
            $page->set($property, $value);
        }

        return $page;
    }

    private function bookYellowCard(array $overrides = []): StaffBooking
    {
        $this->yellowCardPage($overrides)->call('createYellowCard')->assertHasNoErrors()->assertRedirect(NewBooking::getUrl());

        return StaffBooking::sole();
    }

    public function test_only_the_people_who_book_for_customers_can_open_it(): void
    {
        $this->actingAs($this->staff('finance'))->get(BookTravelAssist::getUrl())->assertOk()->assertSee('Flight Assist');

        $this->actingAs($this->staff('customer-support'))->get(BookTravelAssist::getUrl())->assertForbidden();
    }

    public function test_flight_assist_is_billed_with_the_main_fee_at_the_admins_price_and_the_customer_is_told(): void
    {
        SupportProductPrice::where('product', 'flight_assist')->update(['amount' => 40000]);
        $staff = $this->staff();
        $this->actingAs($staff);

        $this->flightAssistPage()->assertSee('40,000')->call('createFlightAssist')->assertHasNoErrors()->assertRedirect(NewBooking::getUrl());

        $request = SupportFlightAssist::sole();
        $this->assertEquals(40000, $request->amount);
        $this->assertSame('billed_with_main_fee', $request->payment_status);
        $this->assertSame('not_required', $request->payment_option);
        $this->assertSame($staff->id, StaffBooking::sole()->created_by);

        Mail::assertSent(SupportFlightAssistSuccessMail::class, fn ($mail) => $mail->hasTo('ada@example.com'));
        Mail::assertSent(SupportFlightAssistNotificationMail::class, 1);
        Mail::assertNotSent(StaffBookingPaymentLinkMail::class);
    }

    public function test_flight_assist_is_listed_with_its_customer_and_cannot_be_charged_or_cancelled(): void
    {
        $this->actingAs($this->staff('finance'));
        $this->flightAssistPage()->call('createFlightAssist');
        $staffBooking = StaffBooking::sole();

        Livewire::test(NewBooking::class)
            ->assertSee('Ada Obi')
            ->assertSee('Billed with main fee')
            ->assertTableActionHidden('recordPayment', $staffBooking)
            ->assertTableActionHidden('sendPaymentLink', $staffBooking)
            ->assertTableActionHidden('cancelBooking', $staffBooking);
    }

    public function test_a_ticket_from_a_travel_agent_is_sent_back_to_the_agent(): void
    {
        $this->actingAs($this->staff());

        $this->flightAssistPage(['bookingSource' => 'agent'])
            ->assertSee('contact their travel agent')
            ->assertDontSee('Submit request')
            ->call('createFlightAssist')
            ->assertHasErrors('bookingSource');

        $this->assertSame(0, SupportFlightAssist::count());
    }

    public function test_flight_assist_keeps_only_the_dates_of_the_chosen_trip_and_needs_them(): void
    {
        $this->actingAs($this->staff());

        $this->flightAssistPage(['travelDate' => ''])->call('createFlightAssist')->assertHasErrors('travelDate');

        $this->flightAssistPage([
            'tripType' => 'round_trip', 'departureDate' => '2027-01-10', 'returnDate' => '2027-01-01',
        ])->call('createFlightAssist')->assertHasErrors('returnDate');

        $this->flightAssistPage([
            'tripType' => 'round_trip', 'departureDate' => '2027-01-10', 'returnDate' => '2027-01-20',
        ])->call('createFlightAssist')->assertHasNoErrors();

        $request = SupportFlightAssist::sole();
        $this->assertNull($request->travel_date_oneway);
        $this->assertSame('2027-01-20', $request->return_date->toDateString());
    }

    public function test_an_airline_from_the_other_list_is_refused_and_changing_the_category_clears_it(): void
    {
        $this->actingAs($this->staff());

        $this->flightAssistPage(['airline' => 'Emirates'])->call('createFlightAssist')->assertHasErrors('airline');

        $this->flightAssistPage()->set('airlineCategory', 'international')->assertSet('airline', '');
    }

    public function test_yellow_card_takes_the_server_price_stores_the_passport_and_sends_a_link_not_a_confirmation(): void
    {
        $this->actingAs($this->staff());

        $booking = $this->bookYellowCard()->bookable;

        $this->assertInstanceOf(SupportYellowCard::class, $booking);
        $this->assertSame(50000, (int) $booking->amount);
        $this->assertSame('pending', $booking->payment_status);
        Storage::disk('public')->assertExists($booking->data_page);

        Mail::assertSent(StaffBookingPaymentLinkMail::class, fn ($mail) => $mail->hasTo('ada@example.com'));
        Mail::assertNotSent(SupportYellowCardSuccessMail::class);
    }

    public function test_a_yellow_card_passport_must_be_a_document(): void
    {
        $this->actingAs($this->staff());

        $this->yellowCardPage(['dataPage' => UploadedFile::fake()->create('virus.exe', 100)])
            ->call('createYellowCard')
            ->assertHasErrors('dataPage');

        $this->assertSame(0, SupportYellowCard::count());
    }

    public function test_the_customer_pays_a_yellow_card_through_the_link_and_is_confirmed_once(): void
    {
        $this->actingAs($this->staff());
        $staffBooking = $this->bookYellowCard();
        $booking = $staffBooking->bookable;
        auth()->logout();

        (new StaffBookingPaymentLinkMail($staffBooking))->assertSeeInHtml('Fast Track (24 Hours)');

        $this->get($staffBooking->payUrl())->assertOk()
            ->assertSee('Fast Track (24 Hours)')
            ->assertSee('Maitama, Abuja')
            ->assertDontSee('Pick-up');

        $this->mock(SeerbitPaymentService::class, function ($mock) {
            $mock->shouldReceive('verifyPayment')->andReturn(['ok' => true, 'query_succeeded' => true]);
            $mock->shouldReceive('initializePayment')
                ->withArgs(fn (array $payment) => $payment['callbackUrl'] === route('air.support.seerbit.callback') && (float) $payment['amount'] === 50000.0)
                ->andReturn(['redirect_link' => 'https://pay.example.test/checkout']);
        });
        $this->post(route('staff-bookings.pay.start', $staffBooking->pay_token))->assertRedirect('https://pay.example.test/checkout');

        foreach ([1, 2] as $visit) {
            $this->get(route('air.support.seerbit.callback', ['reference' => $booking->payment_reference]))
                ->assertRedirect(route('air.support.success'));
        }

        $this->assertSame('paid', $booking->fresh()->payment_status);
        Mail::assertSent(SupportYellowCardSuccessMail::class, 1);
        Mail::assertSent(SupportYellowCardNotificationMail::class, 1);
    }

    public function test_finance_records_a_yellow_card_payment(): void
    {
        $this->actingAs($this->staff());
        $staffBooking = $this->bookYellowCard(['sendLink' => false]);
        Mail::assertNotSent(StaffBookingPaymentLinkMail::class);

        $this->actingAs($this->staff('finance'));
        Livewire::test(NewBooking::class)
            ->callTableAction('recordPayment', $staffBooking, data: ['method' => 'bank_transfer', 'reference' => 'TRF-9'])
            ->assertHasNoTableActionErrors();

        $booking = $staffBooking->bookable->fresh();
        $this->assertSame('paid', $booking->payment_status);
        $this->assertSame('bank_transfer', $booking->payment_option);
        Mail::assertSent(SupportYellowCardSuccessMail::class, 1);
    }

    public function test_the_website_forms_still_work_through_the_shared_service(): void
    {
        $this->post(route('air.support.flight-assist'), [
            'request_type' => 'rerouting', 'booking_source' => 'airline', 'phone' => '08030000000', 'email' => 'web@example.com',
        ])->assertRedirect(route('air.support.success'));

        $this->assertSame('billed_with_main_fee', SupportFlightAssist::sole()->payment_status);
        Mail::assertSent(SupportFlightAssistSuccessMail::class, 1);

        $this->mock(SeerbitPaymentService::class, fn ($mock) => $mock->shouldReceive('initializePayment')->andReturn(['redirect_link' => 'https://pay.example.test/checkout']));

        $this->post(route('air.support.yellow-card'), [
            'service_type' => 'standard', 'full_name' => 'Web Customer', 'email' => 'web@example.com',
            'data_page' => UploadedFile::fake()->create('passport.pdf', 200, 'application/pdf'),
            'home_address' => 'Lekki', 'phone_number' => '08030000000', 'delivery_address' => 'Ikeja', 'payment_option' => 'seerbit',
        ])->assertRedirect('https://pay.example.test/checkout');

        $this->assertSame(30000, (int) SupportYellowCard::sole()->amount);
    }
}
