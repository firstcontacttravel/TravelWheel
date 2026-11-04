<?php

namespace Tests\Feature;

use App\Mail\TravelFlexRepaymentReminderMail;
use App\Models\FlightBooking;
use App\Models\NotificationOutbox;
use App\Models\TravelFlexApplication;
use App\Services\DurableMailService;
use App\Services\TravelFlexApplicationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TravelFlexRepaymentRemindersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Carbon::setTestNow(Carbon::parse('2026-11-04 08:00:00', 'Africa/Lagos'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public static function daysAndStages(): array
    {
        return [
            '3 days before' => [3, 'due_in_3_days'],
            '1 day before' => [1, 'due_tomorrow'],
            'due date' => [0, 'due_today'],
            '1 day late' => [-1, 'overdue_first'],
            '6 days late' => [-6, 'overdue_first'],
            '7 days late' => [-7, 'overdue_final'],
            '13 days late' => [-13, 'overdue_final'],
        ];
    }

    #[DataProvider('daysAndStages')]
    public function test_each_stage_sends_once_however_often_the_job_runs(int $daysFromToday, string $stage): void
    {
        $this->makeApplication([$daysFromToday]);

        $this->runReminders();
        $this->runReminders();

        Mail::assertSent(TravelFlexRepaymentReminderMail::class, 1);
        Mail::assertSent(TravelFlexRepaymentReminderMail::class, fn ($mail) => $mail->stage === $stage);
    }

    public static function quietDays(): array
    {
        return [
            '10 days before' => [10],
            '2 days before' => [2],
            '14 days late' => [-14],
            '92 days late' => [-92],
        ];
    }

    #[DataProvider('quietDays')]
    public function test_other_days_send_nothing(int $daysFromToday): void
    {
        $this->makeApplication([$daysFromToday]);

        $this->runReminders();

        Mail::assertNothingSent();
    }

    public function test_an_overdue_instalment_gets_two_emails_in_total_across_every_day(): void
    {
        $this->makeApplication([0]);
        $dueDate = now('Africa/Lagos')->copy();

        for ($day = 1; $day <= 40; $day++) {
            Carbon::setTestNow($dueDate->copy()->addDays($day));
            $this->runReminders();
        }

        Mail::assertSent(TravelFlexRepaymentReminderMail::class, 2);
        Mail::assertSent(TravelFlexRepaymentReminderMail::class, fn ($mail) => $mail->stage === 'overdue_first');
        Mail::assertSent(TravelFlexRepaymentReminderMail::class, fn ($mail) => $mail->stage === 'overdue_final');
    }

    public function test_an_instalment_first_seen_at_8_days_late_only_gets_the_final_email(): void
    {
        $this->makeApplication([-8]);

        $this->runReminders();

        Mail::assertSent(TravelFlexRepaymentReminderMail::class, 1);
        Mail::assertSent(TravelFlexRepaymentReminderMail::class, fn ($mail) => $mail->stage === 'overdue_final');
    }

    public function test_a_day_the_scheduler_missed_does_not_lose_the_overdue_email(): void
    {
        $this->makeApplication([-3]);

        $this->runReminders();

        Mail::assertSent(TravelFlexRepaymentReminderMail::class, fn ($mail) => $mail->stage === 'overdue_first');
    }

    public function test_each_instalment_is_reminded_separately(): void
    {
        $this->makeApplication([-1, 3, 40]);

        $this->runReminders();

        Mail::assertSent(TravelFlexRepaymentReminderMail::class, 2);
    }

    public static function plansNotLive(): array
    {
        return [
            'only reviewed' => [['application_status' => 'reviewed', 'financing_status' => 'under_review'], []],
            'rejected' => [['application_status' => 'rejected', 'financing_status' => 'rejected'], []],
            'financing not approved' => [['financing_status' => 'under_review'], []],
            'deposit not paid' => [['deposit_status' => 'not_due'], []],
            'refund pending' => [['deposit_status' => 'refund_pending'], []],
            'nothing paid' => [['payment_status' => 'pending'], []],
            'booking cancelled' => [[], ['booking_status' => 'cancelled']],
            'booking failed' => [[], ['booking_status' => 'failed']],
            'hold expired' => [[], ['booking_status' => 'hold_expired_review']],
            'ticketing failed' => [[], ['booking_status' => 'ticketing_failed']],
        ];
    }

    #[DataProvider('plansNotLive')]
    public function test_a_plan_that_is_not_live_gets_no_reminders(array $application, array $booking): void
    {
        $this->makeApplication([-1], $application, $booking);

        $this->runReminders();

        Mail::assertNothingSent();
    }

    public function test_awaiting_ticketing_is_still_a_live_plan(): void
    {
        $this->makeApplication([-1], [], ['booking_status' => 'awaiting_ticketing']);

        $this->runReminders();

        Mail::assertSent(TravelFlexRepaymentReminderMail::class, 1);
    }

    public function test_the_wording_matches_the_stage(): void
    {
        $application = $this->makeApplication([0]);
        $instalment = $application->repayment_plan['schedule'][0];
        $render = fn (string $stage): string => (new TravelFlexRepaymentReminderMail($application, $instalment, $stage))->render();

        $upcoming = $render('due_in_3_days');
        $this->assertStringContainsString('coming up', $upcoming);
        $this->assertStringContainsString('Due soon', $upcoming);

        $first = $render('overdue_first');
        $this->assertStringContainsString('Your repayment is overdue', $first);
        $this->assertStringNotContainsString('Due soon', $first);
        $this->assertStringNotContainsString('coming up', $first);
        $this->assertStringNotContainsString('before the due date', $first);
        $this->assertStringContainsString('No action is needed if you have already paid it', $first);
        $this->assertStringNotContainsString('payment details', $first);

        $final = $render('overdue_final');
        $this->assertStringContainsString('No action is needed if you have already paid it', $final);
        $this->assertStringNotContainsString('payment details', $final);
        $this->assertStringContainsString('last automatic reminder', $final);
        $this->assertStringNotContainsString('Due soon', $final);
        $this->assertStringNotContainsString('before the due date', $final);
    }

    public function test_the_subject_says_when_a_repayment_is_overdue(): void
    {
        $application = $this->makeApplication([0]);
        $subject = fn (string $stage): string => (new TravelFlexRepaymentReminderMail($application, [], $stage))->envelope()->subject;

        $this->assertStringStartsWith('TravelFlex Repayment Reminder', $subject('due_today'));
        $this->assertStringStartsWith('TravelFlex Repayment Overdue', $subject('overdue_first'));
        $this->assertStringStartsWith('Final Reminder', $subject('overdue_final'));
    }

    public function test_dry_run_lists_what_would_send_and_sends_nothing(): void
    {
        $this->makeApplication([-1, -30], ['booking_ref' => 'TW-DRY']);

        $this->artisan('travelflex:send-repayment-reminders', ['--dry-run' => true])
            ->expectsOutputToContain('Would send: 1')
            ->expectsOutputToContain('14+ days overdue: 1')
            ->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertSame(0, NotificationOutbox::query()->count());
    }

    public function test_dry_run_leaves_out_reminders_already_sent(): void
    {
        $this->makeApplication([-1]);
        $this->runReminders();

        $preview = app(TravelFlexApplicationService::class)->previewRepaymentReminders();

        $this->assertSame([], $preview['send']);
    }

    public function test_cancelling_old_queued_reminders_leaves_everything_else_alone(): void
    {
        $application = $this->makeApplication([-1]);
        $queued = $this->outboxRow($application, DurableMailService::TRAVELFLEX_REPAYMENT, 'pending');
        $failed = $this->outboxRow($application, DurableMailService::TRAVELFLEX_REPAYMENT, 'failed', ['failed_at' => now()]);
        $sent = $this->outboxRow($application, DurableMailService::TRAVELFLEX_REPAYMENT, 'sent', ['sent_at' => now()]);
        $otherKind = $this->outboxRow($application, DurableMailService::TRAVELFLEX_STATUS, 'pending');

        $migration = require database_path('migrations/2026_11_04_000000_cancel_queued_travelflex_repayment_reminders.php');
        $migration->up();

        $this->assertSame('cancelled', $queued->fresh()->status);
        $this->assertSame('cancelled', $failed->fresh()->status);
        $this->assertNull($failed->fresh()->failed_at);
        $this->assertSame('sent', $sent->fresh()->status);
        $this->assertSame('pending', $otherKind->fresh()->status);

        app(DurableMailService::class)->processPending();

        Mail::assertNotSent(TravelFlexRepaymentReminderMail::class);
    }

    private function runReminders(): void
    {
        app(TravelFlexApplicationService::class)->sendRepaymentReminders();
    }

    private function outboxRow(TravelFlexApplication $application, string $kind, string $status, array $extra = []): NotificationOutbox
    {
        return NotificationOutbox::query()->create([
            'kind' => $kind,
            'recipient' => 'traveller@example.com',
            'related_type' => $application->getMorphClass(),
            'related_id' => $application->id,
            'payload' => ['instalment' => [], 'timing' => 'overdue', 'status' => 'reviewed'],
            'status' => $status,
            'available_at' => now(),
            ...$extra,
        ]);
    }

    /**
     * @param  list<int>  $daysFromToday  One instalment per entry, due that many days from today.
     */
    private function makeApplication(array $daysFromToday, array $applicationOverrides = [], array $bookingOverrides = []): TravelFlexApplication
    {
        $booking = FlightBooking::create(array_merge([
            'booking_ref' => $applicationOverrides['booking_ref'] ?? 'TW-FLEX-TEST',
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
            'applicant_details' => ['full_name' => 'Test Traveller', 'email' => 'traveller@example.com'],
            'repayment_plan' => ['schedule' => $schedule],
        ], $applicationOverrides));
    }
}
