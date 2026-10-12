<?php

namespace Tests\Feature;

use App\Listeners\SendBookingThankYou;
use App\Mail\BookingThankYouMail;
use App\Services\BookingThankYou;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Mail\SentMessage;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage as SymfonySentMessage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class BookingThankYouTest extends TestCase
{
    use RefreshDatabase;

    /** Fire MessageSent as if $mailable had just been delivered to $to. */
    private function confirmationSent(string $mailable, string $to, string $toName = '', string $subject = 'Booking confirmed'): void
    {
        $email = (new Email)->from('info@travelwheel.ng')->to(new Address($to, $toName))
            ->bcc(config('travelwheel.reservations_email'))->subject($subject)->text('x');
        $sent = new SentMessage(new SymfonySentMessage($email, Envelope::create($email)));

        app(SendBookingThankYou::class)->handle(new MessageSent($sent, ['__laravel_mailable' => $mailable]));
    }

    public function test_the_listener_is_registered_for_sent_mail(): void
    {
        Event::fake();
        Event::assertListening(MessageSent::class, SendBookingThankYou::class);
    }

    public function test_a_lounge_booking_is_followed_by_a_thank_you_suggesting_the_other_products(): void
    {
        Mail::fake();

        $this->confirmationSent(\App\Mail\LoungeBookingMail::class, 'ada@example.com', 'Ada Obi');

        Mail::assertSent(BookingThankYouMail::class, function (BookingThankYouMail $mail) {
            $html = $mail->render();

            return $mail->hasTo('ada@example.com')
                && $mail->hasFrom(config('travelwheel.reservations_email'))
                && $mail->hasReplyTo(config('travelwheel.reservations_email'))
                && $mail->product === 'lounge'
                && str_contains($html, 'Dear Ada,')
                && str_contains($html, 'Thank you for booking <strong>Airport Lounge</strong>')
                && ! str_contains($html, '>Airport Lounge</a>')                // not suggested back to them
                && str_contains($html, '>Airport Protocol</a>')
                && str_contains($html, 'href="'.route('air.protocol').'"')
                && str_contains($html, '>Travel Insurance</a>')
                && str_contains($html, '>Visa Assistance</a>')
                && str_contains($html, '>Air Cargo</a>')
                && str_contains($html, '>Travel Assist</a>')
                && str_contains($html, '>Flights</a>');
        });
        Mail::assertSentCount(1);
    }

    public function test_a_flight_receipt_and_e_ticket_give_one_thank_you(): void
    {
        Mail::fake();

        $this->confirmationSent(\App\Mail\PaymentReceiptMail::class, 'ada@example.com');
        $this->confirmationSent(\App\Mail\ETicketMail::class, 'ADA@example.com');

        Mail::assertSent(BookingThankYouMail::class, 1);
        Mail::assertSent(BookingThankYouMail::class, fn ($mail) => $mail->product === 'flights');
    }

    public function test_internal_and_unrelated_emails_do_not_trigger_a_thank_you(): void
    {
        Mail::fake();

        $this->confirmationSent(\App\Mail\CarHireNotificationMail::class, 'damilola@travelwheel.ng');      // the team's copy
        $this->confirmationSent(\App\Mail\LoungeBookingMail::class, config('travelwheel.reservations_email'));
        $this->confirmationSent(\App\Mail\VisaApplicationUpdateMail::class, 'ada@example.com', '', 'Visa approved');
        $this->confirmationSent(BookingThankYouMail::class, 'ada@example.com');

        Mail::assertNothingSent();
    }

    public function test_a_submitted_visa_application_gets_a_thank_you(): void
    {
        Mail::fake();

        $this->confirmationSent(\App\Mail\VisaApplicationUpdateMail::class, 'ada@example.com', '', 'Visa application submitted - TWV123');

        Mail::assertSent(BookingThankYouMail::class, fn ($mail) => $mail->product === 'visa');
    }

    public function test_every_product_page_link_in_the_email_works(): void
    {
        foreach (array_keys(config('travelwheel.products')) as $product) {
            $html = (new BookingThankYouMail(null, $product))->render();
            $this->assertStringContainsString('Hello,', $html);
            $this->assertSame(count(config('travelwheel.products')) - 1, substr_count($html, '>Book now</a>'), $product);
        }
    }

    public function test_a_real_confirmation_send_is_followed_by_the_thank_you(): void
    {
        // The "array" mailer really sends (into memory), so the mail events fire as in production
        config(['mail.default' => 'array']);
        Mail::to('ada@example.com', 'Ada Obi')->send(new \App\Mail\ProtocolBookingMail('Ada Obi', 50000, 'PRT123'));

        $messages = collect(app('mailer')->getSymfonyTransport()->messages())->map(fn ($m) => $m->getOriginalMessage());

        $this->assertCount(2, $messages);
        $this->assertSame('ada@example.com', $messages[0]->getTo()[0]->getAddress());
        $this->assertSame(config('travelwheel.reservations_email'), $messages[0]->getBcc()[0]->getAddress()); // reservations copy still added
        $this->assertSame('Thank you for booking with TravelWheel', $messages[1]->getSubject());
        $this->assertSame('ada@example.com', $messages[1]->getTo()[0]->getAddress());
        $this->assertSame(config('travelwheel.reservations_email'), $messages[1]->getFrom()[0]->getAddress());
    }

    public function test_a_mail_failure_never_breaks_the_booking(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('mail server down'));

        app(BookingThankYou::class)->send('ada@example.com', 'Ada', 'protocol');

        $this->assertTrue(true); // no exception reached the booking
    }
}
