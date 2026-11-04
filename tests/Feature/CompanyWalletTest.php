<?php

namespace Tests\Feature;

use App\Filament\Pages\DefaultCorporateRates;
use App\Filament\Resources\Companies\Pages\ViewCompany;
use App\Filament\Resources\CompanyWalletTopUps\CompanyWalletTopUpResource;
use App\Filament\Resources\CompanyWalletTopUps\Pages\ListCompanyWalletTopUps;
use App\Mail\CompanyTopUpDecidedMail;
use App\Mail\CompanyTopUpRecordedMail;
use App\Models\ActivityLog;
use App\Models\AppSetting;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\CompanyWalletEntry;
use App\Models\CompanyWalletTopUp;
use App\Models\Department;
use App\Models\User;
use App\Services\Corporate\CompanyWallet;
use App\Services\Corporate\CompanyWalletTopUps;
use App\Services\Corporate\InsufficientWalletBalance;
use App\Services\SystemHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use LogicException;
use Tests\TestCase;

class CompanyWalletTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-42';

    /** What SeerBit's query answers. */
    private array $seerbitAnswer = ['gatewayCode' => '00', 'gatewayMessage' => 'Successful', 'amount' => 0, 'currency' => 'NGN'];

    private int $seerbitStatus = 200;

    private int $checkoutStatus = 200;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.seerbit.public_key' => 'test-public',
            'services.seerbit.secret_key' => 'test-secret',
            'travelwheel.travelflex_bank_accounts' => [['bank' => 'Test Bank', 'account_number' => '0123456789', 'account_name' => 'TravelWheel']],
        ]);
        AppSetting::set(CompanyWalletTopUps::FEE_SETTING, '1.5');
        Mail::fake();

        Http::fake(function (Request $request) {
            $url = $request->url();

            return match (true) {
                str_contains($url, '/encrypt/keys') => Http::response(['data' => ['EncryptedSecKey' => ['encryptedKey' => 'k']]]),
                str_contains($url, '/payments/query/') => Http::response(['data' => ['payments' => $this->seerbitAnswer]], $this->seerbitStatus),
                str_contains($url, '/api/v2/payments') => Http::response(['data' => ['payments' => ['redirectLink' => 'https://checkout.example.test/pay']]], $this->checkoutStatus),
                default => Http::response([]),
            };
        });
    }

    private function company(string $cac = 'RC100', string $status = 'active'): Company
    {
        return Company::create([
            'name' => 'Acme '.$cac,
            'cac_number' => $cac,
            'contact_name' => 'Ada',
            'contact_email' => strtolower($cac).'@acme.test',
            'contact_phone' => '08012345678',
            'status' => $status,
        ]);
    }

    private function member(Company $company, string $role = 'admin'): CompanyUser
    {
        return $company->users()->create([
            'name' => ucfirst($role),
            'email' => $role.uniqid().'@acme.test',
            'password' => self::PASSWORD,
            'role' => $role,
        ]);
    }

    private function signInAdmin(CompanyUser $admin): void
    {
        $this->post(route('business.login.store'), ['email' => $admin->email, 'password' => self::PASSWORD]);

        $code = null;
        Mail::assertSent(\App\Mail\CompanyLoginCodeMail::class, function ($mail) use (&$code, $admin) {
            if ($mail->hasTo($admin->email)) {
                $code = $mail->code;
            }

            return true;
        });

        $this->post(route('business.login.code.verify'), ['code' => $code])->assertRedirect(route('business.dashboard'));
    }

    private function ceo(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function wallet(): CompanyWallet
    {
        return app(CompanyWallet::class);
    }

    // The ledger

    public function test_entries_keep_a_running_balance(): void
    {
        $company = $this->company();

        $this->wallet()->post($company, 'top_up', 1000, 'a');
        $entry = $this->wallet()->post($company, 'booking_debit', -300, 'b');

        $this->assertSame('700.00', $entry->balance_after);
        $this->assertSame('700.00', $company->fresh()->wallet_balance);
    }

    public function test_the_same_event_only_posts_once(): void
    {
        $company = $this->company();

        $first = $this->wallet()->post($company, 'top_up', 1000, 'same-key');
        $second = $this->wallet()->post($company, 'top_up', 1000, 'same-key');

        $this->assertTrue($first->is($second));
        $this->assertSame('1000.00', $company->fresh()->wallet_balance);
        $this->assertSame(1, CompanyWalletEntry::count());
    }

    public function test_a_wallet_cannot_go_below_zero(): void
    {
        $company = $this->company();
        $this->wallet()->post($company, 'top_up', 500, 'a');

        try {
            $this->wallet()->post($company, 'booking_debit', -500.01, 'b');
            $this->fail('The wallet was overdrawn.');
        } catch (InsufficientWalletBalance) {
            $this->assertSame('500.00', $company->fresh()->wallet_balance);
            $this->assertSame(1, CompanyWalletEntry::count());
        }
    }

    public function test_spending_reads_the_database_not_a_stale_copy(): void
    {
        $company = $this->company();
        $this->wallet()->post($company, 'top_up', 500, 'a');

        // Two requests that each loaded the company before either spent.
        $firstCopy = Company::find($company->id);
        $secondCopy = Company::find($company->id);

        $this->wallet()->post($firstCopy, 'booking_debit', -400, 'b');

        $this->expectException(InsufficientWalletBalance::class);
        $this->wallet()->post($secondCopy, 'booking_debit', -400, 'c');
    }

    public function test_the_whole_balance_can_be_spent(): void
    {
        $company = $this->company();
        $this->wallet()->post($company, 'top_up', 500, 'a');

        $this->wallet()->post($company, 'booking_debit', -500, 'b');

        $this->assertSame('0.00', $company->fresh()->wallet_balance);
    }

    public function test_a_credit_limit_would_allow_going_below_zero_up_to_it(): void
    {
        $company = $this->company();
        $company->forceFill(['credit_limit' => 1000])->save();

        $entry = $this->wallet()->post($company, 'booking_debit', -1000, 'a');
        $this->assertSame('-1000.00', $entry->balance_after);

        $this->expectException(InsufficientWalletBalance::class);
        $this->wallet()->post($company, 'booking_debit', -0.01, 'b');
    }

    public function test_entries_cannot_be_changed_or_deleted(): void
    {
        $entry = $this->wallet()->post($this->company(), 'top_up', 1000, 'a');

        try {
            $entry->update(['amount' => 999999]);
            $this->fail('An entry was changed.');
        } catch (LogicException) {
            $this->assertSame('1000.00', $entry->fresh()->amount);
        }

        $this->expectException(LogicException::class);
        $entry->delete();
    }

    public function test_the_health_check_catches_a_balance_that_does_not_match(): void
    {
        $company = $this->company();
        $this->wallet()->post($company, 'top_up', 1000, 'a');
        $check = fn () => collect(app(SystemHealthService::class)->run(false)['checks'])->firstWhere('id', 'corporate_wallets');

        $this->assertSame('healthy', $check()['status']);

        $company->forceFill(['wallet_balance' => 5000])->save();

        $this->assertSame([$company->id], $this->wallet()->mismatchedCompanyIds());
        $this->assertSame('failed', $check()['status']);
    }

    // Bank transfer

    public function test_a_company_admin_records_a_transfer_and_nothing_is_credited_yet(): void
    {
        $company = $this->company();
        $admin = $this->member($company);
        $this->signInAdmin($admin);

        $this->post(route('business.wallet.top-ups.store'), ['amount' => 500000, 'method' => 'bank_transfer', 'bank_reference' => 'FBN-1'])
            ->assertSessionHasNoErrors();

        $topUp = CompanyWalletTopUp::sole();
        $this->assertSame('pending', $topUp->status);
        $this->assertStringStartsWith(CompanyWalletTopUp::REFERENCE_PREFIX, $topUp->reference);
        $this->assertSame($admin->id, $topUp->requested_by_company_user_id);
        $this->assertSame('0.00', $company->fresh()->wallet_balance);
        $this->assertSame('0.00', $topUp->fee_amount);
        Mail::assertSent(CompanyTopUpRecordedMail::class, fn ($mail) => $mail->hasTo(config('corporate.notify_email')));
        $this->assertSame(1, ActivityLog::where('company_user_id', $admin->id)->where('action', 'company_top_up_recorded')->count());

        $this->get(route('business.wallet.index'))->assertOk()
            ->assertSee($topUp->reference)
            ->assertSee('0123456789');
    }

    private function pendingTransfer(Company $company, float $amount = 500000): CompanyWalletTopUp
    {
        return app(CompanyWalletTopUps::class)->recordBankTransfer($this->member($company), $amount, null);
    }

    public function test_finance_confirming_credits_the_wallet_and_tells_the_company(): void
    {
        $company = $this->company();
        $topUp = $this->pendingTransfer($company);
        $this->actingAs($this->ceo());

        Livewire::test(ListCompanyWalletTopUps::class)
            ->callTableAction('confirm', $topUp, ['amount' => 500000])
            ->assertHasNoTableActionErrors();

        $this->assertSame('confirmed', $topUp->fresh()->status);
        $this->assertSame('500000.00', $company->fresh()->wallet_balance);
        $this->assertSame('top_up', CompanyWalletEntry::sole()->type);
        $this->assertSame(auth()->id(), $topUp->fresh()->decided_by_user_id);
        Mail::assertSent(CompanyTopUpDecidedMail::class);
        $this->assertSame(1, ActivityLog::where('action', 'confirm')->where('subject_id', $topUp->id)->count());
    }

    public function test_finance_credits_what_actually_arrived(): void
    {
        $company = $this->company();
        $topUp = $this->pendingTransfer($company, 500000);

        app(CompanyWalletTopUps::class)->confirmBankTransfer($topUp, 450000, $this->ceo());

        $this->assertSame('450000.00', $company->fresh()->wallet_balance);
        $this->assertSame('450000.00', $topUp->fresh()->amount);
    }

    public function test_confirming_twice_credits_once(): void
    {
        $company = $this->company();
        $topUp = $this->pendingTransfer($company);
        $ceo = $this->ceo();

        app(CompanyWalletTopUps::class)->confirmBankTransfer($topUp, 500000, $ceo);
        app(CompanyWalletTopUps::class)->confirmBankTransfer($topUp, 500000, $ceo);

        $this->assertSame('500000.00', $company->fresh()->wallet_balance);
        $this->assertSame(1, CompanyWalletEntry::count());
    }

    public function test_rejecting_credits_nothing_and_cannot_then_be_confirmed(): void
    {
        $company = $this->company();
        $topUp = $this->pendingTransfer($company);
        $this->actingAs($this->ceo());

        Livewire::test(ListCompanyWalletTopUps::class)->callTableAction('reject', $topUp, ['reason' => 'Not received']);

        $this->assertSame('rejected', $topUp->fresh()->status);
        $this->assertSame('0.00', $company->fresh()->wallet_balance);
        Mail::assertSent(CompanyTopUpDecidedMail::class);

        $this->expectException(\DomainException::class);
        app(CompanyWalletTopUps::class)->confirmBankTransfer($topUp->fresh(), 500000, auth()->user());
    }

    public function test_only_finance_and_the_ceo_can_open_the_queue_or_confirm(): void
    {
        $topUp = $this->pendingTransfer($this->company());
        $operations = User::factory()->create(['department_id' => Department::create(['name' => 'Ops', 'slug' => 'ops-test'])->id]);

        $this->actingAs($operations);
        $this->get(CompanyWalletTopUpResource::getUrl('index'))->assertForbidden();
        $this->assertSame('pending', $topUp->fresh()->status);

        $finance = User::factory()->create(['department_id' => Department::where('slug', Department::FINANCE)->value('id')
            ?? Department::create(['name' => 'Finance', 'slug' => Department::FINANCE])->id]);

        $this->actingAs($finance);
        $this->get(CompanyWalletTopUpResource::getUrl('index'))->assertOk();
        Livewire::test(ListCompanyWalletTopUps::class)
            ->callTableAction('confirm', $topUp, ['amount' => 500000]);

        $this->assertSame('confirmed', $topUp->fresh()->status);
    }

    public function test_card_payments_cannot_be_confirmed_by_hand(): void
    {
        $company = $this->company();
        $topUp = $company->walletTopUps()->create(['reference' => 'TWTOPUPCARD1', 'method' => 'card', 'amount' => 1000, 'total_charged' => 1015]);
        $this->actingAs($this->ceo());

        Livewire::test(ListCompanyWalletTopUps::class)
            ->set('activeTab', 'all')
            ->assertTableActionHidden('confirm', $topUp)
            ->assertTableActionHidden('reject', $topUp);

        $this->expectException(\DomainException::class);
        app(CompanyWalletTopUps::class)->confirmBankTransfer($topUp, 1000, auth()->user());
    }

    // Card

    /** Starts a card top-up as a signed-in Admin and returns it. */
    private function startCardTopUp(Company $company, float $amount = 100000): CompanyWalletTopUp
    {
        $this->signInAdmin($this->member($company));

        $this->post(route('business.wallet.top-ups.store'), ['amount' => $amount, 'method' => 'card'])
            ->assertRedirect('https://checkout.example.test/pay');

        return CompanyWalletTopUp::sole();
    }

    public function test_the_fee_goes_on_top_and_stays_out_of_the_wallet(): void
    {
        $company = $this->company();
        $topUp = $this->startCardTopUp($company);

        $this->assertSame('100000.00', $topUp->amount);
        $this->assertSame('1.50', $topUp->fee_percent);
        $this->assertSame('1500.00', $topUp->fee_amount);
        $this->assertSame('101500.00', $topUp->total_charged);
        $this->assertSame('0.00', $company->fresh()->wallet_balance);

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/api/v2/payments')
            && $request['amount'] === '101500.00'
            && $request['paymentReference'] === $topUp->reference);

        $this->seerbitAnswer['amount'] = 101500;
        $this->get(route('business.wallet.callback', ['paymentReference' => $topUp->reference]))
            ->assertRedirect(route('business.wallet.index'));

        $this->assertSame('confirmed', $topUp->fresh()->status);
        $this->assertSame('100000.00', $company->fresh()->wallet_balance);
    }

    public function test_a_later_change_to_the_fee_does_not_touch_a_top_up_in_progress(): void
    {
        $company = $this->company();
        $topUp = $this->startCardTopUp($company);

        AppSetting::set(CompanyWalletTopUps::FEE_SETTING, '5');
        $this->seerbitAnswer['amount'] = 101500;
        app(CompanyWalletTopUps::class)->settleCard($topUp);

        $this->assertSame('1.50', $topUp->fresh()->fee_percent);
        $this->assertSame('confirmed', $topUp->fresh()->status);
    }

    public function test_the_browser_return_and_the_webhook_credit_once(): void
    {
        $company = $this->company();
        $topUp = $this->startCardTopUp($company);
        $this->seerbitAnswer['amount'] = 101500;

        $this->get(route('business.wallet.callback', ['paymentReference' => $topUp->reference]));
        $this->post(route('payments.seerbit.webhook'), ['paymentReference' => $topUp->reference])->assertOk()->assertJson(['status' => 'confirmed']);
        $this->post(route('payments.seerbit.webhook'), ['paymentReference' => $topUp->reference])->assertOk();
        $this->get(route('business.wallet.callback', ['paymentReference' => $topUp->reference]));

        $this->assertSame('100000.00', $company->fresh()->wallet_balance);
        $this->assertSame(1, CompanyWalletEntry::count());
    }

    public function test_the_webhook_alone_credits_a_payment(): void
    {
        $company = $this->company();
        $topUp = $this->startCardTopUp($company);
        $this->seerbitAnswer['amount'] = 101500;

        $this->post(route('payments.seerbit.webhook'), ['data' => ['paymentReference' => $topUp->reference]])->assertOk();

        $this->assertSame('100000.00', $company->fresh()->wallet_balance);
    }

    public function test_a_browser_return_with_no_session_still_credits_it(): void
    {
        $company = $this->company();
        $topUp = $this->startCardTopUp($company);
        $this->seerbitAnswer['amount'] = 101500;

        $this->post(route('business.logout'));
        \Illuminate\Support\Facades\Auth::forgetGuards();

        $this->get(route('business.wallet.callback', ['paymentReference' => $topUp->reference]))->assertRedirect();

        $this->assertSame('100000.00', $company->fresh()->wallet_balance);
    }

    public function test_paying_less_than_the_total_credits_nothing(): void
    {
        $company = $this->company();
        $topUp = $this->startCardTopUp($company);
        $this->seerbitAnswer['amount'] = 100000; // The amount without the fee.

        $this->get(route('business.wallet.callback', ['paymentReference' => $topUp->reference]))->assertSessionHasErrors('method');

        $this->assertSame('failed', $topUp->fresh()->status);
        $this->assertSame('0.00', $company->fresh()->wallet_balance);
    }

    public function test_a_declined_card_credits_nothing(): void
    {
        $company = $this->company();
        $topUp = $this->startCardTopUp($company);
        $this->seerbitAnswer = ['gatewayCode' => '51', 'gatewayMessage' => 'Declined', 'amount' => 101500, 'currency' => 'NGN'];

        app(CompanyWalletTopUps::class)->settleCard($topUp);

        $this->assertSame('failed', $topUp->fresh()->status);
        $this->assertSame('0.00', $company->fresh()->wallet_balance);
    }

    public function test_the_wrong_currency_credits_nothing(): void
    {
        $company = $this->company();
        $topUp = $this->startCardTopUp($company);
        $this->seerbitAnswer['amount'] = 101500;
        $this->seerbitAnswer['currency'] = 'USD';

        app(CompanyWalletTopUps::class)->settleCard($topUp);

        $this->assertSame('0.00', $company->fresh()->wallet_balance);
    }

    public function test_seerbit_being_down_leaves_it_pending_until_it_can_answer(): void
    {
        $company = $this->company();
        $topUp = $this->startCardTopUp($company);
        $this->seerbitStatus = 503;

        app(CompanyWalletTopUps::class)->settleCard($topUp);
        $this->assertSame('pending', $topUp->fresh()->status);

        $this->seerbitStatus = 200;
        $this->seerbitAnswer['amount'] = 101500;
        $this->actingAs($this->ceo());

        Livewire::test(ListCompanyWalletTopUps::class)
            ->set('activeTab', 'all')
            ->callTableAction('checkCard', $topUp);

        $this->assertSame('confirmed', $topUp->fresh()->status);
        $this->assertSame('100000.00', $company->fresh()->wallet_balance);
    }

    public function test_a_card_approved_after_looking_failed_is_still_credited(): void
    {
        $company = $this->company();
        $topUp = $this->startCardTopUp($company);
        $this->seerbitAnswer = ['gatewayCode' => '09', 'gatewayMessage' => 'Pending', 'amount' => 101500, 'currency' => 'NGN'];

        app(CompanyWalletTopUps::class)->settleCard($topUp);
        $this->assertSame('failed', $topUp->fresh()->status);

        $this->seerbitAnswer = ['gatewayCode' => '00', 'gatewayMessage' => 'Successful', 'amount' => 101500, 'currency' => 'NGN'];
        app(CompanyWalletTopUps::class)->settleCard($topUp->fresh());

        $this->assertSame('100000.00', $company->fresh()->wallet_balance);
    }

    public function test_a_browser_return_cannot_credit_a_bank_transfer(): void
    {
        $company = $this->company();
        $topUp = $this->pendingTransfer($company);

        $this->get(route('business.wallet.callback', ['paymentReference' => $topUp->reference]));
        $this->post(route('payments.seerbit.webhook'), ['paymentReference' => $topUp->reference]);

        $this->assertSame('pending', $topUp->fresh()->status);
        $this->assertSame('0.00', $company->fresh()->wallet_balance);
    }

    public function test_no_fee_set_means_no_card_top_ups(): void
    {
        AppSetting::set(CompanyWalletTopUps::FEE_SETTING, null);
        $this->signInAdmin($this->member($this->company()));

        $this->get(route('business.wallet.index'))->assertOk()->assertDontSee('value="card"', false);

        $this->post(route('business.wallet.top-ups.store'), ['amount' => 100000, 'method' => 'card'])->assertSessionHasErrors('method');

        $this->assertSame(0, CompanyWalletTopUp::count());
    }

    public function test_a_gateway_failure_charges_nothing_and_says_so(): void
    {
        $this->checkoutStatus = 500;
        $this->signInAdmin($this->member($this->company()));

        $this->post(route('business.wallet.top-ups.store'), ['amount' => 100000, 'method' => 'card'])->assertSessionHasErrors('method');

        $this->assertSame('failed', CompanyWalletTopUp::sole()->status);
    }

    public function test_a_small_or_invalid_amount_is_refused(): void
    {
        $this->signInAdmin($this->member($this->company()));

        foreach ([0, -5000, 999, 'abc', 1000.555] as $amount) {
            $this->post(route('business.wallet.top-ups.store'), ['amount' => $amount, 'method' => 'bank_transfer'])
                ->assertSessionHasErrors('amount');
        }

        $this->assertSame(0, CompanyWalletTopUp::count());
    }

    public function test_the_fee_percent_is_set_on_the_default_rates_page_and_logged(): void
    {
        $this->actingAs($this->ceo());

        Livewire::test(DefaultCorporateRates::class)
            ->fillForm(['card_topup_fee_percent' => 2.5])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(2.5, app(CompanyWalletTopUps::class)->cardFeePercent());
        $this->assertSame('2.5', ActivityLog::where('action', 'corporate.default_rates_updated')->sole()->properties['card_fee_after']);

        Livewire::test(DefaultCorporateRates::class)->fillForm(['card_topup_fee_percent' => null])->call('save');

        $this->assertNull(app(CompanyWalletTopUps::class)->cardFeePercent());
    }

    // Who sees the wallet

    public function test_a_booker_cannot_open_or_top_up_the_wallet(): void
    {
        $booker = $this->member($this->company(), 'booker');
        $this->post(route('business.login.store'), ['email' => $booker->email, 'password' => self::PASSWORD]);

        $this->get(route('business.wallet.index'))->assertForbidden();
        $this->post(route('business.wallet.top-ups.store'), ['amount' => 5000, 'method' => 'bank_transfer'])->assertForbidden();

        $this->assertSame(0, CompanyWalletTopUp::count());
    }

    public function test_a_company_sees_only_its_own_wallet(): void
    {
        $other = $this->company('RC200');
        $this->wallet()->post($other, 'top_up', 777777, 'other', ['reason' => 'Their money']);
        $this->pendingTransfer($other);
        $company = $this->company();
        $this->wallet()->post($company, 'top_up', 1000, 'mine');

        $this->signInAdmin($this->member($company));

        $this->get(route('business.wallet.index'))->assertOk()
            ->assertSee('1,000.00')
            ->assertDontSee('777,777.00')
            ->assertDontSee('Their money')
            ->assertDontSee($other->walletTopUps()->first()->reference);
    }

    public function test_a_guest_goes_to_the_login(): void
    {
        $this->get(route('business.wallet.index'))->assertRedirect(route('business.login'));
    }

    // Finance's corrections

    public function test_finance_can_adjust_a_wallet_with_a_reason_the_company_can_see(): void
    {
        $company = $this->company();
        $this->wallet()->post($company, 'top_up', 1000, 'a');
        $this->actingAs($this->ceo());

        Livewire::test(ViewCompany::class, ['record' => $company->id])
            ->callAction('adjustWallet', ['amount' => -250, 'reason' => 'Duplicate credit'])
            ->assertHasNoActionErrors();

        $this->assertSame('750.00', $company->fresh()->wallet_balance);
        $entry = CompanyWalletEntry::where('type', 'adjustment')->sole();
        $this->assertSame('Duplicate credit', $entry->reason);
        $this->assertSame(auth()->id(), (int) $entry->user_id);
        $this->assertSame(1, ActivityLog::where('action', 'adjustWallet')->count());
    }

    public function test_an_adjustment_cannot_overdraw_a_wallet(): void
    {
        $company = $this->company();
        $this->wallet()->post($company, 'top_up', 100, 'a');
        $this->actingAs($this->ceo());

        Livewire::test(ViewCompany::class, ['record' => $company->id])
            ->callAction('adjustWallet', ['amount' => -500, 'reason' => 'Too much'])
            ->assertNotified('The balance is too low for that');

        $this->assertSame('100.00', $company->fresh()->wallet_balance);
    }

    public function test_other_staff_cannot_adjust_a_wallet(): void
    {
        $operations = User::factory()->create(['department_id' => Department::create(['name' => 'Ops', 'slug' => 'ops-test'])->id]);
        $this->actingAs($operations);

        Livewire::test(ViewCompany::class, ['record' => $this->company()->id])->assertActionHidden('adjustWallet');
    }

    public function test_the_emails_render(): void
    {
        $company = $this->company();
        $topUp = $this->pendingTransfer($company);

        $this->assertStringContainsString($topUp->reference, (new CompanyTopUpRecordedMail($topUp))->render());

        app(CompanyWalletTopUps::class)->confirmBankTransfer($topUp, 500000, $this->ceo());

        $this->assertStringContainsString('500,000.00', (new CompanyTopUpDecidedMail($topUp->fresh()))->render());
    }
}
