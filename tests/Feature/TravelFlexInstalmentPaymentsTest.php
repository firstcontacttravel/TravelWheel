<?php

namespace Tests\Feature;

use App\Filament\Resources\TravelFlexApplications\Pages\ViewTravelFlexApplication;
use App\Mail\TravelFlexInstalmentPaidMail;
use App\Mail\TravelFlexRepaymentReminderMail;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\FlightBooking;
use App\Models\NotificationOutbox;
use App\Models\TravelFlexApplication;
use App\Models\TravelFlexInstalmentPayment;
use App\Models\User;
use App\Services\DurableMailService;
use App\Services\TravelFlexApplicationService;
use App\Services\TravelFlexRepaymentService;
use App\Support\Admin\TravelFlexPresentation;
use App\Workflow\WorkItemService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class TravelFlexInstalmentPaymentsTest extends TestCase
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

    public function test_finance_can_mark_an_instalment_paid_from_the_application_page(): void
    {
        $finance = $this->financeUser();
        $application = $this->makeApplication([-1, 20]);
        $this->actingAs($finance);

        Livewire::test(ViewTravelFlexApplication::class, ['record' => $application->getKey()])
            ->callAction('markInstalmentPaid', data: [
                'instalment_index' => 0,
                'paid_on' => '2026-11-03',
                'payment_reference' => 'TRF-1001',
                'note' => 'Seen on the bank statement',
            ])
            ->assertHasNoActionErrors();

        $payment = TravelFlexInstalmentPayment::query()->sole();
        $this->assertSame($application->id, $payment->travel_flex_application_id);
        $this->assertSame(0, $payment->instalment_index);
        $this->assertSame('1124467.72', $payment->amount);
        $this->assertSame('2026-11-03', $payment->paid_on->format('Y-m-d'));
        $this->assertSame('TRF-1001', $payment->payment_reference);
        $this->assertSame($finance->id, $payment->recorded_by);
        $this->assertTrue(ActivityLog::query()->where('action', 'markInstalmentPaid')->exists());
    }

    public function test_the_ceo_can_mark_an_instalment_paid(): void
    {
        $application = $this->makeApplication([-1]);
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(ViewTravelFlexApplication::class, ['record' => $application->getKey()])
            ->assertActionVisible('markInstalmentPaid');
    }

    public function test_other_staff_cannot_mark_paid_or_reverse(): void
    {
        $application = $this->makeApplication([-1, 20]);
        $this->pay($application, 1);
        $operations = Department::query()->firstOrCreate(['slug' => Department::OPERATIONS], ['name' => 'Operations']);
        $this->actingAs(User::factory()->create(['department_id' => $operations->id]));

        Livewire::test(ViewTravelFlexApplication::class, ['record' => $application->getKey()])
            ->assertActionHidden('markInstalmentPaid')
            ->assertActionHidden('reverseInstalmentPayment');
    }

    public function test_owning_the_booking_does_not_let_other_staff_record_money(): void
    {
        $application = $this->makeApplication([-1]);
        $operations = Department::query()->firstOrCreate(['slug' => Department::OPERATIONS], ['name' => 'Operations']);
        $owner = User::factory()->create(['department_id' => $operations->id]);
        $this->assignBooking($application, $owner);
        $this->actingAs($owner);

        Livewire::test(ViewTravelFlexApplication::class, ['record' => $application->getKey()])
            ->assertActionHidden('markInstalmentPaid');
    }

    public function test_finance_can_record_a_payment_on_a_booking_owned_by_someone_else(): void
    {
        $application = $this->makeApplication([-1]);
        $operations = Department::query()->firstOrCreate(['slug' => Department::OPERATIONS], ['name' => 'Operations']);
        $this->assignBooking($application, User::factory()->create(['department_id' => $operations->id]));
        $this->actingAs($this->financeUser());

        Livewire::test(ViewTravelFlexApplication::class, ['record' => $application->getKey()])
            ->assertActionVisible('markInstalmentPaid')
            // Their other actions on the booking still follow the ownership rule.
            ->assertActionHidden('resendProviderEmail');
    }

    public function test_the_action_is_hidden_until_the_deposit_is_paid(): void
    {
        $application = $this->makeApplication([20], ['deposit_status' => 'not_due']);
        $this->actingAs($this->financeUser());

        Livewire::test(ViewTravelFlexApplication::class, ['record' => $application->getKey()])
            ->assertActionHidden('markInstalmentPaid');
    }

    public function test_the_action_is_hidden_once_everything_is_paid(): void
    {
        $application = $this->makeApplication([-1]);
        $this->pay($application, 0);
        $this->actingAs($this->financeUser());

        Livewire::test(ViewTravelFlexApplication::class, ['record' => $application->getKey()])
            ->assertActionHidden('markInstalmentPaid')
            ->assertActionVisible('reverseInstalmentPayment');
    }

    public function test_the_same_instalment_cannot_be_recorded_twice(): void
    {
        $application = $this->makeApplication([-1]);
        $this->pay($application, 0);

        $this->expectException(ValidationException::class);

        $this->pay($application, 0);
    }

    public function test_an_instalment_not_on_the_plan_cannot_be_recorded(): void
    {
        $application = $this->makeApplication([-1]);

        $this->expectException(ValidationException::class);

        $this->pay($application, 5);
    }

    public function test_a_future_payment_date_is_refused(): void
    {
        $application = $this->makeApplication([20]);

        $this->expectException(ValidationException::class);

        $this->pay($application, 0, '2026-11-05');
    }

    public function test_an_unapproved_plan_cannot_take_a_payment(): void
    {
        $application = $this->makeApplication([20], ['financing_status' => 'under_review']);

        $this->expectException(ValidationException::class);

        $this->pay($application, 0);
    }

    public function test_a_payment_can_still_be_recorded_when_the_booking_has_fallen_through(): void
    {
        $application = $this->makeApplication([-1], [], ['booking_status' => 'cancelled']);

        $this->pay($application, 0);

        $this->assertSame(1, TravelFlexInstalmentPayment::query()->count());
    }

    public function test_a_paid_instalment_gets_no_more_reminders(): void
    {
        $application = $this->makeApplication([-1, -2]);
        $this->pay($application, 0);

        app(TravelFlexApplicationService::class)->sendRepaymentReminders();

        Mail::assertSent(TravelFlexRepaymentReminderMail::class, 1);
        Mail::assertSent(TravelFlexRepaymentReminderMail::class, fn ($mail) => $mail->instalment['due_date'] === '2026-11-02');
    }

    public function test_paying_cancels_only_that_instalments_queued_reminders(): void
    {
        $application = $this->makeApplication([-1, -2]);
        $paid = $this->queuedReminder($application, 0, '2026-11-03');
        $other = $this->queuedReminder($application, 1, '2026-11-02');
        // Same leading digit as instalment 1, so a sloppy key match would catch it.
        $tenth = $this->queuedReminder($application, 10, '2026-11-03');

        $this->pay($application, 0);

        $this->assertSame('cancelled', $paid->fresh()->status);
        $this->assertSame('failed', $other->fresh()->status);
        $this->assertSame('failed', $tenth->fresh()->status);

        $paid->forceFill(['available_at' => now()->subHour()])->save();
        app(DurableMailService::class)->deliver($paid->fresh());
        Mail::assertNotSent(TravelFlexRepaymentReminderMail::class);
    }

    public function test_a_reversal_keeps_the_record_and_reminders_resume(): void
    {
        $application = $this->makeApplication([-1]);
        $payment = $this->pay($application, 0);
        $reminders = app(TravelFlexApplicationService::class);

        $reminders->sendRepaymentReminders();
        Mail::assertNotSent(TravelFlexRepaymentReminderMail::class);

        app(TravelFlexRepaymentService::class)->reverse($payment, 'Wrong booking', $this->financeUser());

        $this->assertNotNull($payment->fresh()->reversed_at);
        $this->assertSame('Wrong booking', $payment->fresh()->reversal_reason);

        $reminders->sendRepaymentReminders();
        Mail::assertSent(TravelFlexRepaymentReminderMail::class, 1);
    }

    public function test_a_reminder_cancelled_by_a_payment_can_send_after_a_reversal(): void
    {
        $application = $this->makeApplication([-1]);
        $queued = $this->queuedReminder($application, 0, '2026-11-03', 'overdue_first');
        $payment = $this->pay($application, 0);
        $this->assertSame('cancelled', $queued->fresh()->status);

        app(TravelFlexRepaymentService::class)->reverse($payment, 'Wrong booking', $this->financeUser());
        app(TravelFlexApplicationService::class)->sendRepaymentReminders();

        Mail::assertSent(TravelFlexRepaymentReminderMail::class, 1);
        $this->assertNotNull($queued->fresh()->sent_at);
    }

    public function test_a_reversed_instalment_can_be_recorded_again(): void
    {
        $application = $this->makeApplication([-1]);
        $payment = $this->pay($application, 0);
        app(TravelFlexRepaymentService::class)->reverse($payment, 'Wrong reference', $this->financeUser());

        $this->pay($application, 0);

        $this->assertSame(2, TravelFlexInstalmentPayment::query()->count());
        $this->assertSame(1, TravelFlexInstalmentPayment::query()->whereNull('reversed_at')->count());
    }

    public function test_a_payment_cannot_be_reversed_twice(): void
    {
        $application = $this->makeApplication([-1]);
        $payment = $this->pay($application, 0);
        app(TravelFlexRepaymentService::class)->reverse($payment, 'Mistake', $this->financeUser());

        $this->expectException(ValidationException::class);

        app(TravelFlexRepaymentService::class)->reverse($payment, 'Mistake again', $this->financeUser());
    }

    public function test_finance_can_reverse_from_the_application_page(): void
    {
        $application = $this->makeApplication([-1]);
        $payment = $this->pay($application, 0);
        $this->actingAs($this->financeUser());

        Livewire::test(ViewTravelFlexApplication::class, ['record' => $application->getKey()])
            ->callAction('reverseInstalmentPayment', data: ['payment_id' => $payment->id, 'reversal_reason' => 'Bounced'])
            ->assertHasNoActionErrors();

        $this->assertSame('Bounced', $payment->fresh()->reversal_reason);
    }

    public function test_the_customer_is_emailed_a_confirmation_once(): void
    {
        $application = $this->makeApplication([-1, 20]);

        $payment = $this->pay($application, 0);

        Mail::assertSent(TravelFlexInstalmentPaidMail::class, 1);
        Mail::assertSent(TravelFlexInstalmentPaidMail::class, fn ($mail) => $mail->hasTo('traveller@example.com') && $mail->payment->is($payment));
    }

    public function test_the_confirmation_shows_what_is_left_and_hides_the_internal_note(): void
    {
        $application = $this->makeApplication([-1, 20, 50]);

        $html = (new TravelFlexInstalmentPaidMail($this->pay($application, 0, note: 'Secret internal note')->fresh()))->render();

        $this->assertStringContainsString('TRF-1', $html);
        $this->assertStringContainsString('NGN 2,248,935.44', $html);
        $this->assertStringContainsString('Your next instalment', $html);
        $this->assertStringNotContainsString('Secret internal note', $html);
    }

    public function test_the_last_payment_says_the_plan_is_fully_repaid(): void
    {
        $application = $this->makeApplication([-1]);

        $html = (new TravelFlexInstalmentPaidMail($this->pay($application, 0)->fresh()))->render();

        $this->assertStringContainsString('Your plan is fully repaid', $html);
    }

    public function test_reversing_cancels_a_confirmation_still_waiting_to_send(): void
    {
        $application = $this->makeApplication([-1]);
        $payment = $this->pay($application, 0);
        $confirmation = NotificationOutbox::query()->where('unique_key', 'travelflex-instalment-paid:'.$payment->id)->sole();
        $confirmation->forceFill(['sent_at' => null, 'status' => 'failed'])->save();

        app(TravelFlexRepaymentService::class)->reverse($payment, 'Mistake', $this->financeUser());

        $this->assertSame('cancelled', $confirmation->fresh()->status);
    }

    public function test_the_plan_table_shows_amounts_statuses_and_repaid_in_full(): void
    {
        $application = $this->makeApplication([-1, 20]);

        $html = (string) TravelFlexPresentation::plan($application);
        $this->assertStringContainsString('NGN 1,124,467.72', $html);
        $this->assertStringContainsString('Overdue', $html);
        $this->assertStringContainsString('Upcoming', $html);
        $this->assertStringNotContainsString('Repaid in full', $html);

        $this->pay($application, 0);
        $this->pay($application, 1);

        $html = (string) TravelFlexPresentation::plan($application);
        $this->assertStringContainsString('TRF-1', $html);
        $this->assertStringContainsString('Repaid in full', $html);
    }

    public function test_the_plan_table_lists_reversed_payments(): void
    {
        $application = $this->makeApplication([-1]);
        app(TravelFlexRepaymentService::class)->reverse($this->pay($application, 0), 'Bounced transfer', $this->financeUser());

        $html = (string) TravelFlexPresentation::plan($application);

        $this->assertStringContainsString('Reversed payments', $html);
        $this->assertStringContainsString('Bounced transfer', $html);
    }

    public function test_the_application_page_renders_with_a_payment(): void
    {
        $application = $this->makeApplication([-1, 20]);
        $this->pay($application, 0);
        $this->actingAs($this->financeUser());

        Livewire::test(ViewTravelFlexApplication::class, ['record' => $application->getKey()])
            ->assertSee('TRF-1');
    }

    private function pay(TravelFlexApplication $application, int $index, string $paidOn = '2026-11-03', ?string $note = null): TravelFlexInstalmentPayment
    {
        return app(TravelFlexRepaymentService::class)->markPaid($application, $index, $paidOn, 'TRF-'.($index + 1), $note, $this->financeUser());
    }

    private function assignBooking(TravelFlexApplication $application, User $owner): void
    {
        $work = app(WorkItemService::class);
        $work->claim($work->sync($application->booking), $owner);

        $this->assertSame($owner->id, $application->booking->workItem()->first()->owner_id);
    }

    private function queuedReminder(TravelFlexApplication $application, int $index, string $dueDate, string $stage = 'overdue_first'): NotificationOutbox
    {
        return NotificationOutbox::query()->create([
            'kind' => DurableMailService::TRAVELFLEX_REPAYMENT,
            'recipient' => 'traveller@example.com',
            'related_type' => $application->getMorphClass(),
            'related_id' => $application->id,
            'payload' => ['instalment' => ['label' => '1st Payment'], 'stage' => $stage],
            'unique_key' => "travelflex-reminder:{$application->id}:{$index}:{$dueDate}:{$stage}",
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

        $ordinals = ['1st', '2nd', '3rd'];
        $schedule = array_map(function (int $days, int $position) use ($ordinals): array {
            $due = now('Africa/Lagos')->addDays($days);

            return [
                'label' => $ordinals[$position].' Payment',
                'dueDate' => $due->toFormattedDateString(),
                'due_date' => $due->toDateString(),
                'total' => 1124467.72,
            ];
        }, $daysFromToday, array_keys($daysFromToday));

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
