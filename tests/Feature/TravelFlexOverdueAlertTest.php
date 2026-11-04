<?php

namespace Tests\Feature;

use App\Mail\TravelFlexOverdueAlertMail;
use App\Mail\TravelFlexRepaymentReminderMail;
use App\Models\Department;
use App\Models\FlightBooking;
use App\Models\NotificationOutbox;
use App\Models\TravelFlexApplication;
use App\Models\User;
use App\Services\DurableMailService;
use App\Services\TravelFlexApplicationService;
use App\Services\TravelFlexRepaymentService;
use App\Support\Admin\TravelFlexPresentation;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TravelFlexOverdueAlertTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Carbon::setTestNow(Carbon::parse('2026-11-04 08:00:00', 'Africa/Lagos'));
        config()->set('mail.travelflex_provider', 'collections@fastcredit.test');
        config()->set('mail.travelwheel_address', 'ops@travelwheel.test');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_the_alert_goes_to_both_fast_credit_and_travelwheel_on_day_7(): void
    {
        $this->makeApplication([-7]);

        $this->runReminders();

        Mail::assertSent(TravelFlexOverdueAlertMail::class, 1);
        Mail::assertSent(TravelFlexOverdueAlertMail::class, fn ($mail) => $mail->hasTo('collections@fastcredit.test') && $mail->hasCc('ops@travelwheel.test'));
    }

    public function test_it_goes_out_alongside_the_customers_final_reminder(): void
    {
        $this->makeApplication([-7]);

        $this->runReminders();

        Mail::assertSent(TravelFlexRepaymentReminderMail::class, fn ($mail) => $mail->stage === 'overdue_final' && $mail->hasTo('traveller@example.com'));
    }

    public function test_an_instalment_is_only_alerted_once_however_often_the_job_runs(): void
    {
        $this->makeApplication([0]);
        $dueDate = now('Africa/Lagos')->copy();

        for ($day = 1; $day <= 40; $day++) {
            Carbon::setTestNow($dueDate->copy()->addDays($day));
            $this->runReminders();
        }

        Mail::assertSent(TravelFlexOverdueAlertMail::class, 1);
    }

    public static function daysWithNoAlert(): array
    {
        return [
            'upcoming' => [3],
            'due today' => [0],
            '1 day late' => [-1],
            '6 days late' => [-6],
            '14 days late' => [-14],
            '92 days late' => [-92],
        ];
    }

    #[DataProvider('daysWithNoAlert')]
    public function test_other_days_send_no_alert(int $daysFromToday): void
    {
        $this->makeApplication([$daysFromToday]);

        $this->runReminders();

        Mail::assertNotSent(TravelFlexOverdueAlertMail::class);
    }

    public function test_a_scheduler_outage_is_caught_up_by_day_13(): void
    {
        $this->makeApplication([-13]);

        $this->runReminders();

        Mail::assertSent(TravelFlexOverdueAlertMail::class, 1);
    }

    public function test_an_instalment_paid_before_day_7_sends_no_alert(): void
    {
        $application = $this->makeApplication([-7]);
        $this->pay($application);

        $this->runReminders();

        Mail::assertNotSent(TravelFlexOverdueAlertMail::class);
    }

    public function test_paying_cancels_an_alert_still_waiting_to_send(): void
    {
        $application = $this->makeApplication([-7]);
        $alert = $this->queuedAlert($application);

        $this->pay($application);

        $this->assertSame('cancelled', $alert->fresh()->status);

        app(DurableMailService::class)->processPending();
        Mail::assertNotSent(TravelFlexOverdueAlertMail::class);
    }

    public function test_a_reversed_payment_can_still_be_alerted(): void
    {
        $application = $this->makeApplication([-7]);
        $this->queuedAlert($application);
        $payment = $this->pay($application);

        app(TravelFlexRepaymentService::class)->reverse($payment, 'Bounced', $this->financeUser());
        $this->runReminders();

        Mail::assertSent(TravelFlexOverdueAlertMail::class, 1);
    }

    public function test_each_overdue_instalment_gets_its_own_alert(): void
    {
        $this->makeApplication([-7, -8]);

        $this->runReminders();

        Mail::assertSent(TravelFlexOverdueAlertMail::class, 2);
    }

    public function test_a_customer_with_no_email_address_still_triggers_the_alert(): void
    {
        $this->makeApplication([-7], ['applicant_details' => ['full_name' => 'No Email']], ['contact_email' => null]);

        $this->runReminders();

        Mail::assertNotSent(TravelFlexRepaymentReminderMail::class);
        Mail::assertSent(TravelFlexOverdueAlertMail::class, 1);
    }

    public function test_a_plan_that_is_not_live_sends_no_alert(): void
    {
        $this->makeApplication([-7], [], ['booking_status' => 'cancelled']);

        $this->runReminders();

        Mail::assertNotSent(TravelFlexOverdueAlertMail::class);
    }

    public function test_when_both_addresses_are_the_same_it_is_not_sent_to_one_person_twice(): void
    {
        config()->set('mail.travelwheel_address', 'collections@fastcredit.test');
        $this->makeApplication([-7]);

        $this->runReminders();

        Mail::assertSent(TravelFlexOverdueAlertMail::class, fn ($mail) => $mail->hasTo('collections@fastcredit.test') && $mail->cc === []);
    }

    public function test_the_alert_has_the_follow_up_details(): void
    {
        $application = $this->makeApplication([-7, 20]);
        $html = $this->renderAlert($application);

        $this->assertStringContainsString('TW-FLEX-TEST', $html);
        $this->assertStringContainsString('Test Traveller', $html);
        $this->assertStringContainsString('traveller@example.com', $html);
        $this->assertStringContainsString('+2348012345678', $html);
        $this->assertStringContainsString('1st Payment', $html);
        $this->assertStringContainsString('NGN 1,124,467.72', $html);
        $this->assertStringContainsString('7 days overdue', $html);
        $this->assertStringContainsString('0 of 2', $html);
    }

    public function test_the_alert_leaves_out_financial_and_identity_details(): void
    {
        $html = $this->renderAlert($this->makeApplication([-7], [
            'bvn_metadata' => ['last_four' => '9988'],
            'identity_details' => ['nin' => 'NIN-SECRET', 'passport_number' => 'PASS-SECRET'],
            'bank_details' => ['salary_account_number' => 'ACCT-SECRET', 'bank_name' => 'BANK-SECRET', 'monthly_salary' => '777123'],
            'employment_details' => ['employer_name' => 'EMPLOYER-SECRET'],
            'next_of_kin_details' => ['surname' => 'KIN-SECRET'],
            'admin_note' => 'NOTE-SECRET',
        ]));

        foreach (['9988', 'NIN-SECRET', 'PASS-SECRET', 'ACCT-SECRET', 'BANK-SECRET', '777123', 'EMPLOYER-SECRET', 'KIN-SECRET', 'NOTE-SECRET'] as $secret) {
            $this->assertStringNotContainsString($secret, $html);
        }
    }

    public function test_the_alert_has_no_attachments(): void
    {
        $this->makeApplication([-7]);

        $this->runReminders();

        Mail::assertSent(TravelFlexOverdueAlertMail::class, fn ($mail) => $mail->attachments === [] && $mail->rawAttachments === []);
    }

    public function test_the_alert_goes_through_the_outbox_and_can_be_retried(): void
    {
        $application = $this->makeApplication([-7]);
        $alert = $this->queuedAlert($application);

        app(DurableMailService::class)->processPending();

        $this->assertNotNull($alert->fresh()->sent_at);
        Mail::assertSent(TravelFlexOverdueAlertMail::class, 1);
    }

    public function test_dry_run_lists_alerts_and_sends_nothing(): void
    {
        $this->makeApplication([-7]);

        $this->artisan('travelflex:send-repayment-reminders', ['--dry-run' => true])
            ->expectsOutputToContain('Would alert Fast Credit and TravelWheel: 1')
            ->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertSame(0, NotificationOutbox::query()->count());
    }

    public function test_dry_run_leaves_out_alerts_already_sent(): void
    {
        $this->makeApplication([-7]);
        $this->runReminders();

        $this->assertSame([], app(TravelFlexApplicationService::class)->previewRepaymentReminders()['alerts']);
    }

    public function test_the_application_page_shows_when_fast_credit_was_alerted(): void
    {
        $application = $this->makeApplication([-7, 20]);

        $this->assertStringNotContainsString('04 Nov 2026', (string) TravelFlexPresentation::plan($application));

        $this->runReminders();

        $html = (string) TravelFlexPresentation::plan($application->fresh());
        $this->assertStringContainsString('Fast Credit alerted', $html);
        $this->assertStringContainsString('04 Nov 2026', $html);
    }

    private function renderAlert(TravelFlexApplication $application): string
    {
        $instalment = $application->repayment_plan['schedule'][0];

        return (new TravelFlexOverdueAlertMail($application, $instalment))->render();
    }

    private function runReminders(): void
    {
        app(TravelFlexApplicationService::class)->sendRepaymentReminders();
    }

    private function pay(TravelFlexApplication $application)
    {
        return app(TravelFlexRepaymentService::class)->markPaid($application, 0, '2026-11-03', 'TRF-1', null, $this->financeUser());
    }

    private function queuedAlert(TravelFlexApplication $application): NotificationOutbox
    {
        return NotificationOutbox::query()->create([
            'kind' => DurableMailService::TRAVELFLEX_OVERDUE_ALERT,
            'recipient' => 'collections@fastcredit.test',
            'cc' => ['ops@travelwheel.test'],
            'related_type' => $application->getMorphClass(),
            'related_id' => $application->id,
            'payload' => ['instalment' => $application->repayment_plan['schedule'][0]],
            'unique_key' => TravelFlexApplicationService::overdueAlertKey($application->id, 0, now('Africa/Lagos')->subDays(7)->toDateString()),
            'status' => 'failed',
            'available_at' => now(),
        ]);
    }

    private function financeUser(): User
    {
        $finance = Department::query()->firstOrCreate(['slug' => Department::FINANCE], ['name' => 'Finance']);

        return User::factory()->create(['department_id' => $finance->id]);
    }

    /**
     * @param  list<int>  $daysFromToday  One instalment per entry, due that many days from today.
     */
    private function makeApplication(array $daysFromToday, array $applicationOverrides = [], array $bookingOverrides = []): TravelFlexApplication
    {
        $booking = FlightBooking::create(array_merge([
            'booking_ref' => 'TW-FLEX-TEST',
            'unique_id' => 'HOLD-123',
            'fare_source_code' => 'TEST-FARE-SOURCE',
            'fare_type' => 'Public',
            'booking_status' => 'ticketed',
            'payment_status' => 'paid',
            'contact_email' => 'traveller@example.com',
        ], $bookingOverrides));

        $schedule = array_map(function (int $days): array {
            $due = now('Africa/Lagos')->addDays($days);

            return [
                'label' => '1st Payment',
                'dueDate' => $due->toFormattedDateString(),
                'due_date' => $due->toDateString(),
                'total' => 1124467.72,
            ];
        }, $daysFromToday);

        return TravelFlexApplication::create(array_merge([
            'flight_booking_id' => $booking->id,
            'booking_ref' => $booking->booking_ref,
            'application_status' => 'approved',
            'financing_status' => 'approved',
            'deposit_status' => 'paid',
            'payment_status' => 'paid',
            'applicant_details' => ['full_name' => 'Test Traveller', 'email' => 'traveller@example.com', 'phone_primary' => '+2348012345678'],
            'repayment_plan' => ['schedule' => $schedule],
        ], $applicationOverrides));
    }
}
