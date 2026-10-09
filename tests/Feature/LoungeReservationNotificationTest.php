<?php

namespace Tests\Feature;

use App\Mail\LoungeBookingMail;
use App\Mail\LoungeReservationNotificationMail;
use App\Models\Lounge;
use App\Models\LoungeBooking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LoungeReservationNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_paid_lounge_booking_sends_the_full_form_to_reservations(): void
    {
        Mail::fake();
        config(['services.seerbit.public_key' => 'pk', 'services.seerbit.secret_key' => 'sk']);
        Http::fake([
            '*/encrypt/keys' => Http::response(['data' => ['EncryptedSecKey' => ['encryptedKey' => 'token']]]),
            '*/payments/query/*' => Http::response(['data' => ['payments' => ['gatewayCode' => '00', 'gatewayMessage' => 'Successful']]]),
        ]);

        // The lounges table has many required columns; fill the ones this test doesn't care about
        $required = collect(\Illuminate\Support\Facades\Schema::getColumns('lounges'))
            ->filter(fn ($column) => ! $column['nullable'] && $column['default'] === null && ! $column['auto_increment'])
            ->mapWithKeys(fn ($column) => [$column['name'] => str_contains($column['type'], 'int') || str_contains($column['type'], 'decimal') ? 0 : 'x'])
            ->except(['created_at', 'updated_at']);

        $lounge = Lounge::create([
            ...$required->only((new Lounge)->getFillable())->all(),
            'lounge_id' => 'LNGETEST01', 'brand_name' => 'OASIS EXECUTIVE LOUNGE', 'location' => 'Lagos', 'airport' => '1',
            'terminal' => 'Old Terminal', 'service' => 'Departure', 'is_active' => true,
        ]);

        $this->withSession(['lounge_checkout_form' => [
            'lounge_id' => $lounge->id, 'lounge' => 'OASIS EXECUTIVE LOUNGE', 'state' => 'Lagos',
            'firstname' => 'Ada', 'lastname' => 'Obi', 'email' => 'ada@example.com', 'phone' => '08011111111',
            'travel_date' => '2026-11-02', 'd_time' => '14:30', 'ticket_no' => 'ET123',
            'noa' => 2, 'noc' => 1, 'noi' => 0, 'adultAmount' => '60,000', 'childAmount' => '20,000',
            'infantAmount' => '0', 'c_amount' => '80,000', 'vat' => '6,000', 'p_amount' => '86,000',
        ]])->get(route('air.lounge.callback', ['reference' => 'LNGTEST1']))
            ->assertRedirect(route('air.lounge_payment', ['trans_id' => 'LNGTEST1']));

        $booking = LoungeBooking::sole();

        Mail::assertSent(LoungeReservationNotificationMail::class, function ($mail) use ($booking) {
            return $mail->hasTo(config('travelwheel.reservations_email')) && $mail->booking->is($booking);
        });
        Mail::assertSent(LoungeBookingMail::class, fn ($mail) => $mail->hasTo('ada@example.com'));

        $html = (new LoungeReservationNotificationMail($booking, ['state' => 'Lagos', 'p_amount' => '86,000']))->render();
        foreach (['Obi Ada', 'ada@example.com', '08011111111', 'OASIS EXECUTIVE LOUNGE', 'Murtala Muhammed International Airport', 'Old Terminal',
            'International', 'ET123', '14:30', '2 adult(s) · 1 child(ren) · 0 infant(s)', '₦86,000.00', 'LNGTEST1'] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }
    }
}
