<?php

namespace Tests\Feature;

use App\Filament\Pages\DefaultCorporateRates;
use App\Filament\Pages\Reports;
use App\Filament\Resources\CompanyInvoices\CompanyInvoiceResource;
use App\Filament\Resources\CompanyInvoices\Pages\ListCompanyInvoices;
use App\Models\ActivityLog;
use App\Models\AppSetting;
use App\Models\Company;
use App\Models\CompanyInvoice;
use App\Models\CompanyTraveller;
use App\Models\CompanyUser;
use App\Models\Department;
use App\Models\FlightBooking;
use App\Models\ReportingFact;
use App\Models\User;
use App\Services\Corporate\CompanyInvoices;
use App\Services\Corporate\CompanyStatements;
use App\Services\Corporate\CompanyTravellers;
use App\Services\Corporate\CompanyWallet;
use App\Services\Corporate\CompanyWalletTopUps;
use App\Services\Corporate\CorporateAccounts;
use App\Services\Corporate\CorporateWalletPayments;
use App\Services\Reporting\ReportingAnalytics;
use App\Services\Reporting\ReportingSynchronizer;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use LogicException;
use Tests\TestCase;

class CorporateInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-42';

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function company(string $cac = 'RC100'): Company
    {
        return Company::create([
            'name' => 'Acme '.$cac, 'cac_number' => $cac, 'contact_name' => 'Ada',
            'contact_email' => strtolower($cac).'@acme.test', 'status' => 'active',
        ]);
    }

    private function member(Company $company, string $role = 'booker'): CompanyUser
    {
        return $company->users()->create([
            'name' => ucfirst($role), 'email' => $role.uniqid().'@acme.test', 'password' => self::PASSWORD, 'role' => $role,
        ]);
    }

    private function signIn(CompanyUser $user): void
    {
        $this->post(route('business.login.store'), ['email' => $user->email, 'password' => self::PASSWORD]);

        if ($user->needsLoginCode()) {
            Mail::assertSent(\App\Mail\CompanyLoginCodeMail::class, function ($mail) use ($user) {
                if ($mail->hasTo($user->email)) {
                    $this->post(route('business.login.code.verify'), ['code' => $mail->code]);
                }

                return true;
            });
        }
    }

    private int $sequence = 0;

    /** Paid and confirmed: fare 930,000 of which 30,000 is the service fee. */
    private function booking(Company $company, ?CompanyUser $user = null, array $overrides = []): FlightBooking
    {
        $this->sequence++;

        return FlightBooking::create(array_merge([
            'company_id' => $company->id,
            'company_user_id' => $user?->id,
            'booking_ref' => 'TW-INV'.$this->sequence,
            'supplier' => 'skylink',
            'unique_id' => 'PNR'.$this->sequence,
            'fare_source_code' => 'x',
            'route' => 'LOS - DXB',
            'airline' => 'Royal Air Maroc',
            'cabin' => 'Economy',
            'cost_code' => 'PROJ-1',
            'booking_status' => 'confirmed',
            'payment_status' => 'paid',
            'payment_method' => 'wallet',
            'currency' => 'NGN',
            'supplier_price' => 900000,
            'markup_amount' => 30000,
            'total_price' => 930000,
            'payment_amount' => 930000,
            'payment_charged_amount' => 930000,
            'flight_snapshot' => ['price' => 930000],
            'passengers_snapshot' => [[
                'type' => 'ADT', 'title' => 'Mrs', 'first_name' => 'Ngozi', 'last_name' => 'Eze', 'passport_no' => 'A98765432', 'dob' => '1985-03-14',
            ]],
            'contact_email' => 'x@acme.test',
        ], $overrides));
    }

    private function lineAmounts(CompanyInvoice $invoice): array
    {
        return collect($invoice->lines)->mapWithKeys(fn (array $line): array => [$line['label'] => (float) $line['amount']])->all();
    }

    // Issuing

    public function test_a_paid_and_confirmed_booking_gets_an_invoice_by_itself(): void
    {
        $company = $this->company();
        $booking = $this->booking($company);

        $invoice = CompanyInvoice::sole();
        $this->assertSame('invoice', $invoice->type);
        $this->assertSame($booking->id, $invoice->flight_booking_id);
        $this->assertSame($company->id, $invoice->company_id);
        $this->assertMatchesRegularExpression('/^TWINV-\d{4}-000001$/', $invoice->number);
    }

    public function test_a_booking_that_becomes_paid_later_gets_one_then(): void
    {
        $booking = $this->booking($this->company(), overrides: ['booking_status' => 'on_hold', 'payment_status' => 'pending']);
        $this->assertSame(0, CompanyInvoice::count());

        $booking->update(['payment_status' => 'paid']);
        $this->assertSame(0, CompanyInvoice::count());

        $booking->update(['booking_status' => 'ticketed']);
        $this->assertSame(1, CompanyInvoice::count());
    }

    public function test_a_failed_or_unpaid_booking_gets_none(): void
    {
        $company = $this->company();
        $this->booking($company, overrides: ['booking_status' => 'failed']);
        $this->booking($company, overrides: ['payment_status' => 'pending']);
        $this->booking($company, overrides: ['booking_status' => 'cancelled', 'payment_status' => 'refunded']);

        $this->assertSame(0, CompanyInvoice::count());
    }

    public function test_a_public_booking_gets_none(): void
    {
        $this->booking($this->company(), overrides: ['company_id' => null]);

        $this->assertSame(0, CompanyInvoice::count());
    }

    public function test_saving_again_does_not_issue_a_second(): void
    {
        $booking = $this->booking($this->company());

        $booking->update(['booking_status' => 'ticketed']);
        $booking->update(['payment_status' => 'paid', 'ticket_ordered' => true]);
        app(CompanyInvoices::class)->issueForBooking($booking->fresh());

        $this->assertSame(1, CompanyInvoice::count());
    }

    public function test_a_failure_issuing_never_breaks_the_booking(): void
    {
        $company = $this->company();
        $this->mock(CompanyInvoices::class)->shouldReceive('issueForBooking')->andThrow(new \RuntimeException('Oops'));

        $booking = $this->booking($company);

        $this->assertSame('confirmed', $booking->fresh()->booking_status);
        $this->assertSame(0, CompanyInvoice::count());
    }

    // The sums

    public function test_fare_fee_and_extras_add_up_to_what_was_paid(): void
    {
        $this->booking($this->company(), overrides: ['total_price' => 955000, 'payment_amount' => 955000, 'payment_charged_amount' => 955000]);

        $invoice = CompanyInvoice::sole();
        $this->assertSame([
            'Airline fare and taxes' => 900000.0,
            'TravelWheel service fee' => 30000.0,
            'Extra services' => 25000.0,
        ], $this->lineAmounts($invoice));
        $this->assertEquals(955000, (float) $invoice->total);
    }

    public function test_seerbits_card_charge_is_shown_so_the_total_matches_what_was_paid(): void
    {
        $this->booking($this->company(), overrides: ['payment_method' => 'gateway', 'payment_amount' => 930000, 'payment_charged_amount' => 932000]);

        $invoice = CompanyInvoice::sole();
        $this->assertSame(2000.0, $this->lineAmounts($invoice)['Card processing charge']);
        $this->assertEquals(932000, (float) $invoice->total);
    }

    public function test_a_bank_transfer_with_no_recorded_amount_uses_the_booking_total(): void
    {
        $this->booking($this->company(), overrides: ['payment_method' => 'bank_transfer', 'payment_amount' => null, 'payment_charged_amount' => null]);

        $this->assertEquals(930000, (float) CompanyInvoice::sole()->total);
    }

    public function test_vat_is_off_by_default(): void
    {
        $this->booking($this->company());

        $invoice = CompanyInvoice::sole();
        $this->assertNull($invoice->vat_percent);
        $this->assertEquals(0, (float) $invoice->vat_amount);
    }

    public function test_vat_is_included_in_the_service_fee_and_leaves_the_total_alone(): void
    {
        AppSetting::set(CompanyInvoices::VAT_SETTING, '7.5');

        $this->booking($this->company());

        $invoice = CompanyInvoice::sole();
        $this->assertEquals(7.5, (float) $invoice->vat_percent);
        $this->assertEquals(2093.02, (float) $invoice->vat_amount); // 30,000 - 30,000 / 1.075
        $this->assertEquals(930000, (float) $invoice->total);
    }

    public function test_an_invoice_holds_no_passport_or_date_of_birth(): void
    {
        $this->booking($this->company());

        $invoice = CompanyInvoice::sole();
        $this->assertSame(['Mrs Ngozi Eze'], $invoice->booking_details['passengers']);
        $this->assertStringNotContainsString('A98765432', json_encode($invoice->getAttributes()));
        $this->assertStringNotContainsString('1985-03-14', json_encode($invoice->getAttributes()));
    }

    // Numbers and permanence

    public function test_numbers_run_in_order_and_never_repeat(): void
    {
        $company = $this->company();
        foreach (range(1, 5) as $ignored) {
            $this->booking($company);
        }

        $numbers = CompanyInvoice::orderBy('id')->pluck('number')->map(fn (string $number): int => (int) substr($number, -6))->all();

        $this->assertSame([1, 2, 3, 4, 5], $numbers);
    }

    public function test_invoices_and_credit_notes_have_separate_sequences(): void
    {
        $company = $this->company();
        app(CompanyWallet::class)->post($company, 'top_up', 100000, 'seed');
        $booking = $this->booking($company);

        app(CorporateWalletPayments::class)->creditRefund($booking, 1000, 'Part', User::factory()->create());

        $this->assertMatchesRegularExpression('/^TWCN-\d{4}-000001$/', CompanyInvoice::where('type', 'credit_note')->sole()->number);
    }

    public function test_an_issued_invoice_does_not_change_when_things_change_later(): void
    {
        $company = $this->company();
        $booking = $this->booking($company);
        $before = CompanyInvoice::sole()->getAttributes();

        $company->update(['name' => 'Renamed Ltd']);
        $booking->update(['route' => 'ABV - LHR', 'markup_amount' => 99999, 'total_price' => 1]);
        AppSetting::set(CompanyInvoices::VAT_SETTING, '7.5');

        $this->assertSame($before, CompanyInvoice::sole()->getAttributes());
    }

    public function test_an_invoice_cannot_be_edited_or_deleted(): void
    {
        $this->booking($this->company());
        $invoice = CompanyInvoice::sole();

        try {
            $invoice->update(['total' => 1]);
            $this->fail('An invoice was changed.');
        } catch (LogicException) {
            $this->assertEquals(930000, (float) $invoice->fresh()->total);
        }

        $this->expectException(LogicException::class);
        $invoice->delete();
    }

    // Credit notes

    public function test_a_refund_issues_a_credit_note_against_the_invoice(): void
    {
        $company = $this->company();
        $booking = $this->booking($company);
        app(CompanyWallet::class)->post($company, 'top_up', 1000, 'seed');
        $invoice = CompanyInvoice::sole();

        app(CorporateWalletPayments::class)->creditRefund($booking, 500000, 'Refund less penalty', User::factory()->create());

        $note = CompanyInvoice::where('type', 'credit_note')->sole();
        $this->assertSame($invoice->id, $note->credited_invoice_id);
        $this->assertEquals(500000, (float) $note->total);
        $this->assertSame('Refund less penalty', $note->reason);
        $this->assertSame($invoice->booking_details, $note->booking_details);
    }

    public function test_a_credit_note_carries_its_share_of_the_vat(): void
    {
        AppSetting::set(CompanyInvoices::VAT_SETTING, '7.5');
        $company = $this->company();
        $booking = $this->booking($company);

        app(CorporateWalletPayments::class)->creditRefund($booking, 465000, 'Half', User::factory()->create());

        $this->assertEquals(1046.51, (float) CompanyInvoice::where('type', 'credit_note')->sole()->vat_amount);
    }

    public function test_money_returned_on_a_booking_never_issued_has_no_credit_note(): void
    {
        $company = $this->company();
        $booking = $this->booking($company, overrides: ['booking_status' => 'failed']);

        app(CorporateWalletPayments::class)->creditRefund($booking, 930000, 'Seat never existed', User::factory()->create());

        $this->assertSame(0, CompanyInvoice::count());
    }

    // Statements

    public function test_a_statement_matches_the_ledger_and_carries_the_balance_forward(): void
    {
        $company = $this->company();
        $wallet = app(CompanyWallet::class);

        $this->travelTo(CarbonImmutable::parse('2026-09-15 10:00', 'Africa/Lagos'));
        $wallet->post($company, 'top_up', 1000000, 'a');
        $wallet->post($company, 'booking_debit', -200000, 'b');

        $this->travelTo(CarbonImmutable::parse('2026-10-10 10:00', 'Africa/Lagos'));
        $wallet->post($company, 'top_up', 50000, 'c');
        $wallet->post($company, 'refund', 10000, 'd');
        $wallet->post($company, 'booking_debit', -300000, 'e');

        $this->travelTo(CarbonImmutable::parse('2026-11-02 10:00', 'Africa/Lagos'));

        $statements = app(CompanyStatements::class);
        $september = $statements->forMonth($company, CarbonImmutable::create(2026, 9, 1, 0, 0, 0, 'Africa/Lagos'));
        $october = $statements->forMonth($company, CarbonImmutable::create(2026, 10, 1, 0, 0, 0, 'Africa/Lagos'));
        $november = $statements->forMonth($company, CarbonImmutable::create(2026, 11, 1, 0, 0, 0, 'Africa/Lagos'));

        $this->assertEquals(0, $september['opening']);
        $this->assertEquals(800000, $september['closing']);
        $this->assertEquals(800000, $october['opening']);
        $this->assertEquals(60000, $october['money_in']);
        $this->assertEquals(300000, $october['money_out']);
        $this->assertEquals(560000, $october['closing']);
        $this->assertEquals(560000, $november['opening']);
        $this->assertEquals(560000, $november['closing']);
        $this->assertCount(0, $november['entries']);
        $this->assertEquals((float) $company->fresh()->wallet_balance, $november['closing']);

        // Opening + in - out = closing, every month.
        foreach ([$september, $october, $november] as $statement) {
            $this->assertEquals($statement['closing'], $statement['opening'] + $statement['money_in'] - $statement['money_out']);
        }
    }

    public function test_the_month_boundary_follows_lagos_time(): void
    {
        $company = $this->company();

        // 23:30 UTC on 31 October is 00:30 on 1 November in Lagos.
        $this->travelTo(CarbonImmutable::parse('2026-10-31 23:30', 'UTC'));
        app(CompanyWallet::class)->post($company, 'top_up', 100, 'a');
        $this->travelTo(CarbonImmutable::parse('2026-11-02 10:00', 'Africa/Lagos'));

        $statements = app(CompanyStatements::class);

        $this->assertCount(0, $statements->forMonth($company, CarbonImmutable::create(2026, 10, 1, 0, 0, 0, 'Africa/Lagos'))['entries']);
        $this->assertCount(1, $statements->forMonth($company, CarbonImmutable::create(2026, 11, 1, 0, 0, 0, 'Africa/Lagos'))['entries']);
    }

    public function test_a_bad_or_future_month_falls_back_to_this_month(): void
    {
        $statements = app(CompanyStatements::class);
        $this->travelTo(CarbonImmutable::parse('2026-11-02 10:00', 'Africa/Lagos'));

        foreach (['2099-01', 'nonsense', '2026-13', null] as $value) {
            $this->assertSame('2026-11', $statements->month($value)->format('Y-m'));
        }

        $this->assertSame('2026-09', $statements->month('2026-09')->format('Y-m'));
    }

    // The portal

    public function test_an_admin_sees_every_invoice_and_a_booker_only_their_own(): void
    {
        $company = $this->company();
        $mine = $this->member($company);
        $colleague = $this->member($company);
        $this->booking($company, $mine, ['booking_ref' => 'TW-MINE']);
        $this->booking($company, $colleague, ['booking_ref' => 'TW-THEIRS']);
        $theirs = CompanyInvoice::where('booking_details->reference', 'TW-THEIRS')->sole();
        $own = CompanyInvoice::where('booking_details->reference', 'TW-MINE')->sole();

        $this->signIn($mine);
        $this->get(route('business.invoices.index'))->assertOk()->assertSee('TW-MINE')->assertDontSee('TW-THEIRS');
        $this->get(route('business.invoices.download', $own->number))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get(route('business.invoices.download', $theirs->number))->assertNotFound();

        $this->post(route('business.logout'));
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->signIn($this->member($company, 'admin'));

        $this->get(route('business.invoices.index'))->assertOk()->assertSee('TW-MINE')->assertSee('TW-THEIRS');
        $this->get(route('business.invoices.download', $theirs->number))->assertOk();
    }

    public function test_another_companys_invoice_is_not_found(): void
    {
        $other = $this->company('RC200');
        $this->booking($other);
        $foreign = CompanyInvoice::sole();

        $this->signIn($this->member($this->company(), 'admin'));

        $this->get(route('business.invoices.index'))->assertDontSee($foreign->number);
        $this->get(route('business.invoices.download', $foreign->number))->assertNotFound();
    }

    public function test_invoices_can_be_found_by_cost_code(): void
    {
        $company = $this->company();
        $booker = $this->member($company);
        $this->booking($company, $booker, ['cost_code' => 'ALPHA', 'booking_ref' => 'TW-A']);
        $this->booking($company, $booker, ['cost_code' => 'BETA', 'booking_ref' => 'TW-B']);
        $this->signIn($booker);

        $this->get(route('business.invoices.index', ['q' => 'ALPHA']))->assertSee('TW-A')->assertDontSee('TW-B');
    }

    public function test_the_pdfs_render(): void
    {
        $company = $this->company();
        $booking = $this->booking($company);
        app(CorporateWalletPayments::class)->creditRefund($booking, 1000, 'Part', User::factory()->create());
        $this->signIn($this->member($company, 'admin'));

        foreach (CompanyInvoice::all() as $invoice) {
            $response = $this->get(route('business.invoices.download', $invoice->number))->assertOk();
            $this->assertStringStartsWith('%PDF', $response->getContent());
        }

        $this->assertStringStartsWith('%PDF', $this->get(route('business.statements.pdf'))->assertOk()->getContent());
    }

    public function test_the_invoice_and_credit_note_carry_the_logo(): void
    {
        $company = $this->company();
        $booking = $this->booking($company);
        app(CorporateWalletPayments::class)->creditRefund($booking, 1000, 'Part', User::factory()->create());

        foreach (CompanyInvoice::all() as $invoice) {
            $html = view('pdf.corporate-invoice', ['invoice' => $invoice])->render();

            $this->assertStringContainsString('src="data:image/png;base64,', $html, $invoice->typeLabel());
            $this->assertStringNotContainsString('<h1>TravelWheel</h1>', $html, $invoice->typeLabel());

            // An image DomPDF refuses to embed leaves the PDF with no image at all.
            $this->assertStringContainsString('/Subtype /Image', app(\App\Services\Corporate\CorporateDocuments::class)->invoicePdf($invoice)->getContent());
        }
    }

    public function test_the_statement_carries_the_logo(): void
    {
        $company = $this->company();
        $statements = app(CompanyStatements::class);
        $statement = $statements->forMonth($company, $statements->month(null));

        $html = view('pdf.corporate-statement', ['company' => $company, 'statement' => $statement])->render();

        $this->assertStringContainsString('src="data:image/png;base64,', $html);
        $this->assertStringNotContainsString('<h1>TravelWheel</h1>', $html);
        $this->assertStringContainsString('/Subtype /Image', app(\App\Services\Corporate\CorporateDocuments::class)->statementPdf($company, $statement)->getContent());
    }

    public function test_statements_are_for_admins_and_show_only_their_company(): void
    {
        $company = $this->company();
        $other = $this->company('RC200');
        app(CompanyWallet::class)->post($company, 'top_up', 1000, 'a', ['reason' => 'Ours']);
        app(CompanyWallet::class)->post($other, 'top_up', 777777, 'b', ['reason' => 'Theirs']);

        $booker = $this->member($company);
        $this->signIn($booker);
        $this->get(route('business.statements.show'))->assertForbidden();
        $this->get(route('business.statements.pdf'))->assertForbidden();
        $this->get(route('business.statements.csv'))->assertForbidden();

        $this->post(route('business.logout'));
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->signIn($this->member($company, 'admin'));

        $this->get(route('business.statements.show'))->assertOk()->assertSee('Ours')->assertDontSee('Theirs')->assertDontSee('777,777');
    }

    // Exports

    public function test_the_bookings_export_follows_who_can_see_what(): void
    {
        $company = $this->company();
        $mine = $this->member($company);
        $this->booking($company, $mine, ['booking_ref' => 'TW-MINE', 'cost_code' => 'PROJ-9']);
        $this->booking($company, $this->member($company), ['booking_ref' => 'TW-THEIRS']);
        $this->booking($this->company('RC200'), null, ['booking_ref' => 'TW-ELSEWHERE']);
        $this->signIn($mine);

        $csv = $this->get(route('business.bookings.export'))->assertOk()->streamedContent();

        $this->assertStringContainsString('TW-MINE', $csv);
        $this->assertStringContainsString('PROJ-9', $csv);
        $this->assertStringNotContainsString('TW-THEIRS', $csv);
        $this->assertStringNotContainsString('TW-ELSEWHERE', $csv);
        $this->assertStringNotContainsString('skylink', strtolower($csv));
    }

    public function test_a_typed_formula_cannot_run_when_the_export_is_opened(): void
    {
        $company = $this->company();
        $booker = $this->member($company);
        $this->booking($company, $booker, ['cost_code' => '=HYPERLINK("http://evil.test")']);
        $this->signIn($booker);

        $csv = $this->get(route('business.bookings.export'))->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $csv);
    }

    public function test_the_wallet_export_keeps_negative_amounts_as_numbers(): void
    {
        $company = $this->company();
        app(CompanyWallet::class)->post($company, 'top_up', 1000, 'a');
        app(CompanyWallet::class)->post($company, 'booking_debit', -250, 'b', ['reason' => 'TW-1 LOS - DXB']);
        $this->signIn($this->member($company, 'admin'));

        $csv = $this->get(route('business.statements.csv'))->assertOk()->streamedContent();

        $this->assertStringContainsString(',-250', $csv);
        $this->assertStringNotContainsString("'-250", $csv);
        $this->assertStringContainsString('TW-1 LOS - DXB', $csv);
    }

    // The admin

    public function test_finance_can_see_filter_and_download_and_others_cannot(): void
    {
        $company = $this->company();
        $this->booking($company);
        $this->booking($this->company('RC200'));
        $invoice = CompanyInvoice::where('company_id', $company->id)->sole();

        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(ListCompanyInvoices::class)
            ->filterTable('company_id', $company->id)
            ->assertCanSeeTableRecords([$invoice])
            ->assertCountTableRecords(1);
        $this->get(route('admin.corporate.invoice', $invoice))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get(route('admin.corporate.statement', ['company' => $company, 'month' => now()->format('Y-m')]))->assertOk();

        $this->actingAs(User::factory()->create(['department_id' => Department::create(['name' => 'Ops', 'slug' => 'ops-test'])->id]));
        $this->get(CompanyInvoiceResource::getUrl('index'))->assertForbidden();
        $this->get(route('admin.corporate.invoice', $invoice))->assertForbidden();
        $this->get(route('admin.corporate.statement', ['company' => $company]))->assertForbidden();
    }

    public function test_a_guest_and_a_company_user_cannot_reach_the_admin_downloads(): void
    {
        $company = $this->company();
        $this->booking($company);
        $invoice = CompanyInvoice::sole();

        $this->get(route('admin.corporate.invoice', $invoice))->assertRedirect(filament()->getPanel('admin')->getLoginUrl());

        $this->signIn($this->member($company, 'admin'));

        $this->get(route('admin.corporate.invoice', $invoice))->assertRedirect(filament()->getPanel('admin')->getLoginUrl());
    }

    public function test_the_ceo_turns_vat_on_and_it_is_logged(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(DefaultCorporateRates::class)
            ->fillForm(['vat_percent' => 7.5])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEquals(7.5, app(CompanyInvoices::class)->vatPercent());
        $this->assertSame('7.5', ActivityLog::where('action', 'corporate.default_rates_updated')->sole()->properties['vat_after']);
    }

    public function test_reports_can_be_filtered_by_company(): void
    {
        $company = $this->company();
        $other = $this->company('RC200');
        $this->booking($company, overrides: ['booking_ref' => 'TW-OURS']);
        $this->booking($other, overrides: ['booking_ref' => 'TW-OTHER']);
        $this->booking($company, overrides: ['booking_ref' => 'TW-PUBLIC', 'company_id' => null]);

        app(ReportingSynchronizer::class)->sync();

        $this->assertSame($company->id, ReportingFact::where('reference', 'TW-OURS')->sole()->dimensions['company_id']);

        $this->assertSame(1, ReportingFact::query()->where('dimensions->company_id', $company->id)->count());

        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $dashboard = app(ReportingAnalytics::class)->dashboard(['from' => now()->subDay()->toDateString(), 'to' => now()->toDateString(), 'company_id' => $company->id]);
        $this->assertEquals(930000, $dashboard['summary']['gross_value']);

        Livewire::test(Reports::class)->set('companyId', (string) $company->id)->assertOk();
    }

    // Retention

    private function traveller(Company $company): CompanyTraveller
    {
        return app(CompanyTravellers::class)->create($this->member($company), [
            'title' => 'Mrs', 'first_name' => 'Ngozi', 'last_name' => 'Eze', 'gender' => 'F', 'dob' => '1985-03-14', 'nationality' => 'NG',
            'passport_no' => 'A98765432', 'passport_exp' => now()->addYears(5)->toDateString(),
        ]);
    }

    public function test_travellers_go_30_days_after_suspension_and_not_before(): void
    {
        $company = $this->company();
        $this->traveller($company);
        app(CorporateAccounts::class)->suspend($company, 'Left');

        $this->travel(29)->days();
        $this->artisan('corporate:housekeeping')->assertSuccessful();
        $this->assertSame(1, CompanyTraveller::count());

        $this->travel(2)->days();
        $this->artisan('corporate:housekeeping')->assertSuccessful();

        $this->assertSame(0, CompanyTraveller::count());
        $this->assertSame(1, ActivityLog::where('action', 'corporate.travellers_expired')->where('subject_id', $company->id)->count());
    }

    public function test_reactivating_stops_the_clock(): void
    {
        $company = $this->company();
        $this->traveller($company);
        $accounts = app(CorporateAccounts::class);
        $accounts->suspend($company, 'Check');
        $this->travel(20)->days();
        $accounts->reactivate($company->fresh());
        $this->assertNull($company->fresh()->suspended_at);

        $this->travel(40)->days();
        $this->artisan('corporate:housekeeping')->assertSuccessful();
        $this->assertSame(1, CompanyTraveller::count());

        // A new suspension starts again from zero.
        $accounts->suspend($company->fresh(), 'Again');
        $this->travel(29)->days();
        $this->artisan('corporate:housekeeping')->assertSuccessful();
        $this->assertSame(1, CompanyTraveller::count());
    }

    public function test_an_active_company_keeps_its_travellers_however_long(): void
    {
        $this->traveller($this->company());

        $this->travel(400)->days();
        $this->artisan('corporate:housekeeping')->assertSuccessful();

        $this->assertSame(1, CompanyTraveller::count());
    }

    public function test_only_the_suspended_company_loses_its_travellers(): void
    {
        $gone = $this->company();
        $staying = $this->company('RC200');
        $this->traveller($gone);
        $this->traveller($staying);
        app(CorporateAccounts::class)->suspend($gone, 'Left');

        $this->travel(31)->days();
        $this->artisan('corporate:housekeeping')->assertSuccessful();

        $this->assertSame([$staying->id], CompanyTraveller::pluck('company_id')->all());
    }

    public function test_a_dry_run_changes_nothing(): void
    {
        $company = $this->company();
        $this->traveller($company);
        app(CorporateAccounts::class)->suspend($company, 'Left');
        $this->travel(31)->days();

        $this->artisan('corporate:housekeeping --dry-run')->assertSuccessful();

        $this->assertSame(1, CompanyTraveller::count());
    }

    public function test_housekeeping_issues_an_invoice_that_failed_at_the_time(): void
    {
        $company = $this->company();
        $booking = FlightBooking::withoutEvents(fn () => $this->booking($company));
        $this->assertSame(0, CompanyInvoice::count());

        $this->artisan('corporate:housekeeping')->assertSuccessful();
        $this->artisan('corporate:housekeeping')->assertSuccessful();

        $this->assertSame($booking->id, CompanyInvoice::sole()->flight_booking_id);
    }

    // SeerBit's fee

    public function test_a_zero_percent_fee_keeps_card_top_ups_on_and_adds_nothing_of_ours(): void
    {
        config(['services.seerbit.public_key' => 'test-public', 'services.seerbit.secret_key' => 'test-secret']);
        Http::fake(function (Request $request) {
            return match (true) {
                str_contains($request->url(), '/encrypt/keys') => Http::response(['data' => ['EncryptedSecKey' => ['encryptedKey' => 'k']]]),
                str_contains($request->url(), '/api/v2/payments') => Http::response(['data' => ['payments' => ['redirectLink' => 'https://checkout.example.test/pay']]]),
                // SeerBit's own fee added on top, as it is on our real payments.
                str_contains($request->url(), '/payments/query/') => Http::response(['data' => ['payments' => [
                    'gatewayCode' => '00', 'gatewayMessage' => 'Successful', 'amount' => '102000.00', 'fee' => '2000.00', 'feeBearer' => 'CUSTOMER', 'currency' => 'NGN',
                ]]]),
                default => Http::response([]),
            };
        });
        AppSetting::set(CompanyWalletTopUps::FEE_SETTING, '0');
        $company = $this->company();
        $this->signIn($this->member($company, 'admin'));

        $this->get(route('business.wallet.index'))->assertOk()->assertSee('value="card"', false);

        $this->post(route('business.wallet.top-ups.store'), ['amount' => 100000, 'method' => 'card'])->assertRedirect('https://checkout.example.test/pay');

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/api/v2/payments') && $request['amount'] === '100000.00');

        $topUp = $company->walletTopUps()->sole();
        $this->assertEquals(0, (float) $topUp->fee_amount);

        $this->get(route('business.wallet.callback', ['paymentReference' => $topUp->reference]));

        $topUp->refresh();
        $this->assertSame('confirmed', $topUp->status);
        $this->assertEquals(100000, (float) $company->fresh()->wallet_balance);
        $this->assertEquals(2000, (float) $topUp->fee_amount);
        $this->assertEquals(102000, (float) $topUp->total_charged);
    }
}
