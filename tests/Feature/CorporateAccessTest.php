<?php

namespace Tests\Feature;

use App\Mail\CompanyLoginCodeMail;
use App\Mail\CompanyPasswordResetMail;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CorporateAccessTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-42';

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function company(string $status = 'active', string $cac = 'RC100'): Company
    {
        return Company::create([
            'name' => 'Acme '.$cac,
            'cac_number' => $cac,
            'contact_name' => 'Ada',
            'contact_email' => strtolower($cac).'@acme.test',
            'status' => $status,
        ]);
    }

    private function member(?Company $company = null, string $role = 'booker', array $attributes = []): CompanyUser
    {
        $company ??= $this->company();

        return $company->users()->create(array_merge([
            'name' => ucfirst($role).' Person',
            'email' => $role.uniqid().'@acme.test',
            'password' => self::PASSWORD,
            'role' => $role,
        ], $attributes));
    }

    private function signIn(CompanyUser $user): self
    {
        return $this->post(route('business.login.store'), ['email' => $user->email, 'password' => self::PASSWORD])
            ->assertRedirect(route('business.dashboard')) ? $this : $this;
    }

    // Logins never mix

    public function test_a_company_user_cannot_open_the_admin_panel(): void
    {
        $this->signIn($this->member());
        $this->assertTrue(Auth::guard('corporate')->check());
        $this->assertFalse(Auth::guard('web')->check());

        $this->get('/admin')->assertRedirect(filament()->getPanel('admin')->getLoginUrl());
    }

    public function test_a_company_user_with_the_same_email_as_staff_gains_nothing(): void
    {
        $staff = User::factory()->create(['is_admin' => true, 'email' => 'same@acme.test', 'password' => 'staff-password-1']);
        $member = $this->member(attributes: ['email' => 'same@acme.test']);

        $this->signIn($member);

        $this->assertFalse(Auth::guard('web')->check());
        $this->assertTrue(Auth::guard('corporate')->user()->is($member));
        $this->assertFalse($staff->is(Auth::guard('corporate')->user()));
    }

    public function test_staff_cannot_sign_in_at_the_corporate_login(): void
    {
        User::factory()->create(['is_admin' => true, 'email' => 'ceo@travelwheel.test', 'password' => self::PASSWORD]);

        $this->post(route('business.login.store'), ['email' => 'ceo@travelwheel.test', 'password' => self::PASSWORD])
            ->assertSessionHasErrors('email');

        $this->assertFalse(Auth::guard('corporate')->check());
    }

    public function test_a_staff_session_does_not_open_the_portal(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->get(route('business.dashboard'))->assertRedirect(route('business.login'));
    }

    public function test_signing_out_of_the_portal_leaves_a_staff_login_alone(): void
    {
        $staff = User::factory()->create(['is_admin' => true]);
        $this->actingAs($staff);
        $this->signIn($this->member());

        $this->post(route('business.logout'))->assertRedirect(route('business.login'));

        $this->assertFalse(Auth::guard('corporate')->check());
        $this->assertTrue(Auth::guard('web')->user()->is($staff));
    }

    public function test_guests_go_to_the_corporate_login_not_the_admin_one(): void
    {
        $this->get('/business')->assertRedirect(route('business.login'));
        $this->get('/business/team')->assertRedirect(route('business.login'));
        $this->get(route('business.login'))->assertOk()->assertSee('Sign in');
    }

    public function test_portal_pages_are_not_kept_for_the_back_button(): void
    {
        $this->signIn($this->member());

        $this->assertStringContainsString('no-store', (string) $this->get('/business')->assertOk()->headers->get('Cache-Control'));
    }

    // Immediate cut-off

    public function test_suspending_a_company_cuts_off_a_signed_in_user_at_once(): void
    {
        $member = $this->member();
        $this->signIn($member);
        $this->get('/business')->assertOk();

        $member->company->update(['status' => 'suspended']);
        $this->nextRequest();

        $this->get('/business')->assertRedirect(route('business.login'));
        $this->assertFalse(Auth::guard('corporate')->check());
    }

    public function test_deactivating_a_person_cuts_them_off_at_once(): void
    {
        $member = $this->member();
        $this->signIn($member);

        $member->update(['deactivated_at' => now()]);
        $this->nextRequest();

        $this->get('/business')->assertRedirect(route('business.login'));
    }

    public function test_changing_a_password_elsewhere_signs_this_session_out(): void
    {
        $member = $this->member();
        $this->signIn($member);

        $member->update(['password' => 'another-password-99']);
        $this->nextRequest();

        $this->get('/business')->assertRedirect(route('business.login'));
    }

    public function test_suspended_and_deactivated_accounts_cannot_sign_in(): void
    {
        $suspended = $this->member($this->company('suspended', 'RC200'));
        $deactivated = $this->member(attributes: ['deactivated_at' => now()]);

        foreach ([$suspended, $deactivated] as $user) {
            $this->post(route('business.login.store'), ['email' => $user->email, 'password' => self::PASSWORD])
                ->assertSessionHasErrors('email');
            $this->assertFalse(Auth::guard('corporate')->check());
        }
    }

    // Password attempts

    public function test_repeated_wrong_passwords_are_locked_out(): void
    {
        $member = $this->member();

        foreach (range(1, 5) as $ignored) {
            $this->post(route('business.login.store'), ['email' => $member->email, 'password' => 'wrong']);
        }

        // The right password no longer helps.
        $this->post(route('business.login.store'), ['email' => $member->email, 'password' => self::PASSWORD])
            ->assertSessionHasErrors('email');

        $this->assertFalse(Auth::guard('corporate')->check());
    }

    // Email codes

    public function test_an_admin_needs_a_code_and_is_not_signed_in_until_it_is_entered(): void
    {
        $admin = $this->member(role: 'admin');

        $this->post(route('business.login.store'), ['email' => $admin->email, 'password' => self::PASSWORD])
            ->assertRedirect(route('business.login.code'));

        $this->assertFalse(Auth::guard('corporate')->check());
        $this->get('/business')->assertRedirect(route('business.login'));

        $code = null;
        Mail::assertSent(CompanyLoginCodeMail::class, function (CompanyLoginCodeMail $mail) use (&$code, $admin) {
            $code = $mail->code;

            return $mail->hasTo($admin->email);
        });

        $this->post(route('business.login.code.verify'), ['code' => $code])->assertRedirect(route('business.dashboard'));
        $this->assertTrue(Auth::guard('corporate')->user()->is($admin));
    }

    public function test_the_code_is_stored_hashed_and_kept_out_of_the_subject(): void
    {
        $admin = $this->member(role: 'admin');
        $this->post(route('business.login.store'), ['email' => $admin->email, 'password' => self::PASSWORD]);

        Mail::assertSent(CompanyLoginCodeMail::class, function (CompanyLoginCodeMail $mail) use ($admin) {
            $this->assertNotSame($mail->code, $admin->fresh()->login_code_hash);
            $this->assertStringNotContainsString($mail->code, (string) $mail->envelope()->subject);

            return true;
        });
    }

    public function test_a_code_works_once(): void
    {
        $admin = $this->member(role: 'admin');
        $this->post(route('business.login.store'), ['email' => $admin->email, 'password' => self::PASSWORD]);
        $code = $this->sentCode();

        $this->post(route('business.login.code.verify'), ['code' => $code])->assertRedirect(route('business.dashboard'));
        $this->post(route('business.logout'));

        // A new password step is needed, and the old code doesn't complete it.
        $this->post(route('business.login.code.verify'), ['code' => $code])->assertRedirect(route('business.login'));
        $this->assertNull($admin->fresh()->login_code_hash);
    }

    public function test_an_expired_code_is_refused(): void
    {
        $admin = $this->member(role: 'admin');
        $this->post(route('business.login.store'), ['email' => $admin->email, 'password' => self::PASSWORD]);
        $code = $this->sentCode();

        $this->travel(11)->minutes();

        $this->post(route('business.login.code.verify'), ['code' => $code])->assertSessionHasErrors('code');
        $this->assertFalse(Auth::guard('corporate')->check());
    }

    public function test_too_many_wrong_codes_kill_the_code(): void
    {
        $admin = $this->member(role: 'admin');
        $this->post(route('business.login.store'), ['email' => $admin->email, 'password' => self::PASSWORD]);
        $code = $this->sentCode();

        foreach (range(1, 5) as $ignored) {
            $this->post(route('business.login.code.verify'), ['code' => $code === '000000' ? '111111' : '000000']);
        }

        $this->post(route('business.login.code.verify'), ['code' => $code])->assertSessionHasErrors('code');
        $this->assertFalse(Auth::guard('corporate')->check());
    }

    public function test_a_code_is_refused_once_the_company_is_suspended(): void
    {
        $admin = $this->member(role: 'admin');
        $this->post(route('business.login.store'), ['email' => $admin->email, 'password' => self::PASSWORD]);
        $code = $this->sentCode();

        $admin->company->update(['status' => 'suspended']);

        $this->post(route('business.login.code.verify'), ['code' => $code])->assertRedirect(route('business.login'));
        $this->assertFalse(Auth::guard('corporate')->check());
    }

    public function test_new_codes_are_limited_to_one_a_minute(): void
    {
        $admin = $this->member(role: 'admin');
        $this->post(route('business.login.store'), ['email' => $admin->email, 'password' => self::PASSWORD]);

        $this->post(route('business.login.code.resend'))->assertSessionHasErrors('code');
        Mail::assertSentCount(1);

        $this->travel(61)->seconds();

        $this->post(route('business.login.code.resend'))->assertSessionHasNoErrors();
        Mail::assertSentCount(2);
    }

    public function test_a_booker_needs_no_code_unless_they_turn_it_on(): void
    {
        $booker = $this->member();
        $this->signIn($booker);
        Mail::assertNothingSent();

        $this->put(route('business.account.login-code'), ['login_code_enabled' => 1])->assertRedirect();
        $this->post(route('business.logout'));

        $this->post(route('business.login.store'), ['email' => $booker->email, 'password' => self::PASSWORD])
            ->assertRedirect(route('business.login.code'));
    }

    public function test_an_admin_cannot_turn_the_code_off(): void
    {
        $admin = $this->member(role: 'admin');
        $this->post(route('business.login.store'), ['email' => $admin->email, 'password' => self::PASSWORD]);
        $this->post(route('business.login.code.verify'), ['code' => $this->sentCode()]);

        $this->put(route('business.account.login-code'), ['login_code_enabled' => 0]);

        $this->assertTrue($admin->fresh()->needsLoginCode());
    }

    // Passwords

    public function test_password_reset_works_and_does_not_reveal_who_has_an_account(): void
    {
        $member = $this->member();

        $known = $this->from(route('business.password.request'))
            ->post(route('business.password.email'), ['email' => $member->email]);
        $unknown = $this->from(route('business.password.request'))
            ->post(route('business.password.email'), ['email' => 'nobody@acme.test']);

        $this->assertSame($known->getSession()->get('status'), $unknown->getSession()->get('status'));

        $token = null;
        Mail::assertSent(CompanyPasswordResetMail::class, function (CompanyPasswordResetMail $mail) use (&$token) {
            $token = $mail->token;

            return true;
        });
        Mail::assertSentCount(1);

        $this->post(route('business.password.update'), [
            'token' => $token,
            'email' => $member->email,
            'password' => 'brand-new-pass-77',
            'password_confirmation' => 'brand-new-pass-77',
        ])->assertRedirect(route('business.login'));

        $this->assertFalse(Auth::guard('corporate')->check());
        $this->post(route('business.login.store'), ['email' => $member->email, 'password' => 'brand-new-pass-77'])
            ->assertRedirect(route('business.dashboard'));
    }

    public function test_a_staff_email_gets_no_corporate_reset(): void
    {
        User::factory()->create(['email' => 'ceo@travelwheel.test']);

        $this->post(route('business.password.email'), ['email' => 'ceo@travelwheel.test']);

        Mail::assertNothingSent();
    }

    public function test_changing_your_own_password_keeps_you_signed_in_and_is_logged(): void
    {
        $member = $this->member();
        $this->signIn($member);

        $this->put(route('business.account.password'), [
            'current_password' => self::PASSWORD,
            'password' => 'brand-new-pass-77',
            'password_confirmation' => 'brand-new-pass-77',
        ])->assertSessionHasNoErrors();

        $this->get('/business')->assertOk();

        $log = ActivityLog::where('action', 'company_password_changed')->sole();
        $this->assertSame($member->id, $log->company_user_id);
        $this->assertNull($log->user_id);
    }

    public function test_changing_your_password_needs_the_current_one(): void
    {
        $this->signIn($this->member());

        $this->put(route('business.account.password'), [
            'current_password' => 'not-the-password',
            'password' => 'brand-new-pass-77',
            'password_confirmation' => 'brand-new-pass-77',
        ])->assertSessionHasErrors('current_password');
    }

    /**
     * A test keeps one app between requests, so the guard would answer from the
     * user it already loaded. A real request starts from the session and the
     * database, which is what the cut-off relies on.
     */
    private function nextRequest(): void
    {
        Auth::forgetGuards();
    }

    private function sentCode(): string
    {
        $code = null;
        Mail::assertSent(CompanyLoginCodeMail::class, function (CompanyLoginCodeMail $mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        return $code;
    }
}
