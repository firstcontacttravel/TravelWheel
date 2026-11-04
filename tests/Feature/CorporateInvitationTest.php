<?php

namespace Tests\Feature;

use App\Filament\Resources\Companies\Pages\ListCompanies;
use App\Filament\Resources\Companies\Pages\ViewCompany;
use App\Mail\CompanyInvitationMail;
use App\Mail\CompanyRequestReceivedMail;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\CompanyInvitation;
use App\Models\CompanyUser;
use App\Models\Department;
use App\Models\User;
use App\Services\Corporate\CorporateAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class CorporateInvitationTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-42';

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function ceo(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function company(string $status = 'invited', string $cac = 'RC100'): Company
    {
        return Company::create([
            'name' => 'Acme '.$cac,
            'cac_number' => $cac,
            'contact_name' => 'Ada',
            'contact_email' => strtolower($cac).'@acme.test',
            'status' => $status,
        ]);
    }

    /** Sends a real invitation and returns the token from the email. */
    private function invite(Company $company, string $email = 'ada@acme.test', string $role = 'admin'): string
    {
        app(CorporateAccounts::class)->invite($company, $email, 'Ada', $role, $this->ceo());

        $token = null;
        Mail::assertSent(CompanyInvitationMail::class, function (CompanyInvitationMail $mail) use (&$token, $email) {
            if ($mail->hasTo($email)) {
                $token = $mail->token;
            }

            return true;
        });

        return $token;
    }

    private function accept(string $token, array $overrides = [])
    {
        return $this->post(route('business.invitation.accept', $token), array_merge([
            'name' => 'Ada Obi',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ], $overrides));
    }

    private function signedInAdmin(?Company $company = null): CompanyUser
    {
        $company ??= $this->company('active');
        $admin = $company->users()->create(['name' => 'Admin', 'email' => 'admin@'.$company->cac_number.'.test', 'password' => self::PASSWORD, 'role' => 'admin']);

        $this->post(route('business.login.store'), ['email' => $admin->email, 'password' => self::PASSWORD]);
        Mail::assertSent(\App\Mail\CompanyLoginCodeMail::class, function ($mail) use ($admin) {
            if ($mail->hasTo($admin->email)) {
                $this->post(route('business.login.code.verify'), ['code' => $mail->code]);
            }

            return true;
        });

        return $admin;
    }

    // The link

    public function test_accepting_creates_the_admin_activates_the_company_and_signs_them_in(): void
    {
        $company = $this->company();
        $token = $this->invite($company);

        $this->get(route('business.invitation.show', $token))->assertOk()->assertSee($company->name)->assertSee('ada@acme.test');

        $this->accept($token)->assertRedirect(route('business.dashboard'));

        $user = CompanyUser::where('email', 'ada@acme.test')->sole();
        $this->assertSame('admin', $user->role);
        $this->assertSame($company->id, $user->company_id);
        $this->assertSame('active', $company->fresh()->status);
        $this->assertTrue(Auth::guard('corporate')->user()->is($user));
        $this->assertSame(1, ActivityLog::where('company_user_id', $user->id)->where('action', 'company_invitation_accepted')->count());
    }

    public function test_a_link_cannot_be_used_twice(): void
    {
        $token = $this->invite($this->company());
        $this->accept($token)->assertRedirect();
        $this->post(route('business.logout'));

        $this->get(route('business.invitation.show', $token))->assertStatus(410);
        $this->accept($token, ['name' => 'Someone Else'])->assertStatus(410);

        $this->assertSame(1, CompanyUser::count());
    }

    public function test_an_expired_link_fails(): void
    {
        $token = $this->invite($this->company());

        $this->travel(8)->days();

        $this->get(route('business.invitation.show', $token))->assertStatus(410);
        $this->accept($token)->assertStatus(410);
        $this->assertSame(0, CompanyUser::count());
    }

    public function test_a_revoked_link_fails(): void
    {
        $company = $this->company();
        $token = $this->invite($company);

        app(CorporateAccounts::class)->revoke($company->pendingInvitations()->sole());

        $this->accept($token)->assertStatus(410);
        $this->assertSame(0, CompanyUser::count());
    }

    public function test_a_new_invitation_cancels_the_old_link(): void
    {
        $company = $this->company();
        $first = $this->invite($company);
        Mail::fake();
        $second = $this->invite($company);

        $this->assertNotSame($first, $second);
        $this->accept($first)->assertStatus(410);
        $this->accept($second)->assertRedirect(route('business.dashboard'));
    }

    public function test_a_suspended_company_link_fails_until_reactivated(): void
    {
        $company = $this->company();
        $token = $this->invite($company);
        app(CorporateAccounts::class)->suspend($company, 'Checking');

        $this->accept($token)->assertStatus(410);

        app(CorporateAccounts::class)->reactivate($company->fresh());

        $this->accept($token)->assertRedirect(route('business.dashboard'));
    }

    public function test_an_unknown_link_fails(): void
    {
        $this->get(route('business.invitation.show', str_repeat('x', 64)))->assertStatus(410);
    }

    public function test_only_the_token_hash_is_stored(): void
    {
        $token = $this->invite($this->company());

        $this->assertDatabaseMissing('company_invitations', ['token_hash' => $token]);
        $this->assertNotNull(CompanyInvitation::findByToken($token));
    }

    public function test_the_person_cannot_choose_a_different_email_or_role(): void
    {
        $token = $this->invite($this->company(), 'booker@acme.test', 'booker');

        $this->accept($token, ['email' => 'evil@acme.test', 'role' => 'admin'])->assertRedirect();

        $user = CompanyUser::sole();
        $this->assertSame('booker@acme.test', $user->email);
        $this->assertSame('booker', $user->role);
    }

    public function test_a_weak_password_is_refused_and_the_link_stays_usable(): void
    {
        $token = $this->invite($this->company());

        $this->accept($token, ['password' => 'short', 'password_confirmation' => 'short'])->assertSessionHasErrors('password');

        $this->assertSame(0, CompanyUser::count());
        $this->get(route('business.invitation.show', $token))->assertOk();
    }

    public function test_an_email_that_joined_elsewhere_cannot_accept(): void
    {
        $token = $this->invite($this->company());
        $this->company('active', 'RC200')->users()->create(['name' => 'Ada', 'email' => 'ada@acme.test', 'password' => self::PASSWORD, 'role' => 'booker']);

        $this->accept($token)->assertStatus(410);
        $this->assertSame(1, CompanyUser::count());
    }

    // The public form

    private function requestData(array $overrides = []): array
    {
        return array_merge([
            'company_name' => 'Globex Ltd',
            'cac_number' => 'rc 123-456',
            'contact_name' => 'Ngozi',
            'contact_email' => 'Ngozi@Globex.test',
            'contact_phone' => '+234 801 234 5678',
        ], $overrides);
    }

    public function test_a_request_is_saved_and_staff_are_told(): void
    {
        $this->post(route('business.request.store'), $this->requestData())->assertRedirect(route('business.request.received'));

        $company = Company::sole();
        $this->assertSame('requested', $company->status);
        $this->assertSame('RC123456', $company->cac_number);
        $this->assertSame('ngozi@globex.test', $company->contact_email);
        Mail::assertSent(CompanyRequestReceivedMail::class, fn ($mail) => $mail->hasTo(config('corporate.notify_email')));
    }

    public function test_a_duplicate_request_looks_identical_and_adds_nothing(): void
    {
        $this->company('active', 'RC123456');

        $response = $this->post(route('business.request.store'), $this->requestData());

        $response->assertRedirect(route('business.request.received'));
        $this->assertSame(1, Company::count());
        Mail::assertNothingSent();
    }

    public function test_a_rejected_company_may_apply_again(): void
    {
        $this->company('rejected', 'RC123456');

        $this->post(route('business.request.store'), $this->requestData());

        $this->assertSame(2, Company::count());
    }

    public function test_the_spam_trap_saves_nothing_but_looks_like_success(): void
    {
        $this->post(route('business.request.store'), $this->requestData(['hp_check' => 'bot']))
            ->assertRedirect(route('business.request.received'));

        $this->assertSame(0, Company::count());
    }

    public function test_the_form_is_rate_limited(): void
    {
        foreach (range(1, 5) as $i) {
            $this->post(route('business.request.store'), $this->requestData(['cac_number' => "RC{$i}", 'contact_email' => "c{$i}@globex.test"]));
        }

        $this->post(route('business.request.store'), $this->requestData(['cac_number' => 'RC6', 'contact_email' => 'c6@globex.test']))->assertStatus(429);
    }

    // The admin section

    public function test_the_ceo_can_approve_a_request_which_invites_its_contact(): void
    {
        $this->actingAs($this->ceo());
        $company = $this->company('requested');

        Livewire::test(ViewCompany::class, ['record' => $company->id])
            ->callAction('approve', ['name' => 'Ada Obi', 'email' => 'Ada@Acme.test'])
            ->assertHasNoActionErrors();

        $this->assertSame('invited', $company->fresh()->status);
        $this->assertSame('ada@acme.test', $company->fresh()->contact_email);
        Mail::assertSent(CompanyInvitationMail::class, fn ($mail) => $mail->hasTo('ada@acme.test'));
        $this->assertSame(1, ActivityLog::where('action', 'approve')->where('subject_id', $company->id)->count());
    }

    public function test_the_ceo_can_reject_a_request(): void
    {
        $this->actingAs($this->ceo());
        $company = $this->company('requested');

        Livewire::test(ViewCompany::class, ['record' => $company->id])
            ->callAction('reject', ['reason' => 'CAC number not found']);

        $this->assertSame('rejected', $company->fresh()->status);
        $this->assertSame('CAC number not found', $company->fresh()->status_reason);
        Mail::assertNothingSent();
    }

    public function test_the_ceo_can_add_a_company_which_invites_its_contact(): void
    {
        $this->actingAs($this->ceo());

        Livewire::test(ListCompanies::class)
            ->callAction('addCompany', [
                'name' => 'Initech',
                'cac_number' => 'rc 777',
                'contact_name' => 'Bola',
                'contact_email' => 'bola@initech.test',
            ])
            ->assertHasNoActionErrors();

        $company = Company::sole();
        $this->assertSame('invited', $company->status);
        $this->assertSame('RC777', $company->cac_number);
        Mail::assertSent(CompanyInvitationMail::class, fn ($mail) => $mail->hasTo('bola@initech.test'));
    }

    public function test_adding_a_company_with_a_cac_number_already_on_file_is_refused(): void
    {
        $this->actingAs($this->ceo());
        $this->company('active', 'RC777');

        Livewire::test(ListCompanies::class)
            ->callAction('addCompany', [
                'name' => 'Initech',
                'cac_number' => 'rc777',
                'contact_name' => 'Bola',
                'contact_email' => 'bola@initech.test',
            ])
            ->assertHasActionErrors(['cac_number']);

        $this->assertSame(1, Company::count());
    }

    public function test_the_ceo_can_send_and_revoke_an_invitation(): void
    {
        $this->actingAs($this->ceo());
        $company = $this->company('invited');

        Livewire::test(ViewCompany::class, ['record' => $company->id])
            ->callAction('invite', ['name' => 'Ada', 'email' => 'ada@acme.test']);

        $invitation = $company->pendingInvitations()->sole();

        Livewire::test(ViewCompany::class, ['record' => $company->id])
            ->callAction('revokeInvitation', ['invitation' => $invitation->id]);

        $this->assertSame(0, $company->pendingInvitations()->count());
    }

    public function test_the_ceo_can_suspend_and_reactivate(): void
    {
        $this->actingAs($this->ceo());
        $company = $this->company('active');
        $company->users()->create(['name' => 'A', 'email' => 'a@acme.test', 'password' => self::PASSWORD, 'role' => 'admin']);

        Livewire::test(ViewCompany::class, ['record' => $company->id])->callAction('suspend', ['reason' => 'Unpaid']);
        $this->assertSame('suspended', $company->fresh()->status);

        Livewire::test(ViewCompany::class, ['record' => $company->id])->callAction('reactivate');
        $this->assertSame('active', $company->fresh()->status);
        $this->assertNull($company->fresh()->status_reason);
    }

    public function test_reactivating_a_company_nobody_joined_returns_it_to_invited(): void
    {
        $company = $this->company('suspended');

        app(CorporateAccounts::class)->reactivate($company);

        $this->assertSame('invited', $company->fresh()->status);
    }

    public function test_other_staff_can_look_but_not_change_anything(): void
    {
        $staff = User::factory()->create(['department_id' => Department::create(['name' => 'Ops', 'slug' => 'ops-test'])->id]);
        $this->actingAs($staff);
        $company = $this->company('requested');

        $this->get(\App\Filament\Resources\Companies\CompanyResource::getUrl('index'))->assertOk();

        Livewire::test(ViewCompany::class, ['record' => $company->id])
            ->assertOk()
            ->assertActionHidden('approve')
            ->assertActionHidden('reject');

        Livewire::test(ListCompanies::class)->assertActionHidden('addCompany');

        $this->assertSame('requested', $company->fresh()->status);
    }

    public function test_a_company_user_shows_by_name_in_the_activity_log(): void
    {
        $company = $this->company('active');
        $user = $company->users()->create(['name' => 'Ada', 'email' => 'a@acme.test', 'password' => self::PASSWORD, 'role' => 'admin']);

        $log = ActivityLog::recordForCompanyUser($user, 'x', 'Did something');

        $this->assertSame('Ada ('.$company->name.')', $log->nonStaffActorName());
        $this->assertNull($log->user_id);
        $this->assertSame('System', ActivityLog::record('y', 'Ran')->nonStaffActorName());
    }

    // The team page

    public function test_a_company_admin_can_invite_a_booker_who_can_then_join(): void
    {
        $admin = $this->signedInAdmin();

        $this->post(route('business.team.invite'), ['email' => 'booker@acme.test', 'role' => 'booker'])
            ->assertSessionHasNoErrors();

        $invitation = $admin->company->pendingInvitations()->sole();
        $this->assertSame($admin->id, $invitation->invited_by_company_user_id);
        $this->assertSame(1, ActivityLog::where('company_user_id', $admin->id)->where('action', 'company_user_invited')->count());

        $token = null;
        Mail::assertSent(CompanyInvitationMail::class, function ($mail) use (&$token) {
            $token = $mail->token;

            return $mail->hasTo('booker@acme.test');
        });

        $this->post(route('business.logout'));
        $this->accept($token)->assertRedirect(route('business.dashboard'));
        $this->assertSame('booker', CompanyUser::where('email', 'booker@acme.test')->sole()->role);
    }

    public function test_an_email_with_an_account_cannot_be_invited(): void
    {
        $admin = $this->signedInAdmin();

        $this->post(route('business.team.invite'), ['email' => $admin->email, 'role' => 'booker'])
            ->assertSessionHasErrors('email');

        $this->assertSame(0, $admin->company->invitations()->count());
    }

    public function test_a_booker_cannot_open_the_team_page(): void
    {
        $booker = $this->company('active')->users()->create(['name' => 'B', 'email' => 'b@acme.test', 'password' => self::PASSWORD, 'role' => 'booker']);
        $this->post(route('business.login.store'), ['email' => $booker->email, 'password' => self::PASSWORD]);

        $this->get(route('business.team.index'))->assertForbidden();
        $this->post(route('business.team.invite'), ['email' => 'x@acme.test', 'role' => 'admin'])->assertForbidden();
    }

    public function test_an_admin_cannot_touch_another_companys_people_or_invitations(): void
    {
        $other = $this->company('active', 'RC999');
        $stranger = $other->users()->create(['name' => 'S', 'email' => 's@other.test', 'password' => self::PASSWORD, 'role' => 'booker']);
        $foreignInvite = $other->invitations()->create([
            'email' => 'f@other.test', 'role' => 'booker', 'token_hash' => CompanyInvitation::hashToken('abc'), 'expires_at' => now()->addDay(),
        ]);

        $this->signedInAdmin();

        $this->put(route('business.team.members.deactivate', $stranger->id))->assertNotFound();
        $this->put(route('business.team.members.role', $stranger->id), ['role' => 'admin'])->assertNotFound();
        $this->delete(route('business.team.invitations.revoke', $foreignInvite->id))->assertNotFound();
        $this->post(route('business.team.invitations.resend', $foreignInvite->id))->assertNotFound();

        $this->assertNull($stranger->fresh()->deactivated_at);
        $this->assertSame('booker', $stranger->fresh()->role);
        $this->assertNull($foreignInvite->fresh()->revoked_at);
    }

    public function test_the_team_page_does_not_list_another_company(): void
    {
        $this->company('active', 'RC999')->users()->create(['name' => 'Stranger', 'email' => 's@other.test', 'password' => self::PASSWORD, 'role' => 'booker']);

        $this->signedInAdmin();

        $this->get(route('business.team.index'))->assertOk()->assertDontSee('s@other.test');
    }

    public function test_a_company_cannot_lose_its_last_admin(): void
    {
        $admin = $this->signedInAdmin();

        $this->put(route('business.team.members.role', $admin->id), ['role' => 'booker'])->assertSessionHasErrors('team');
        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_a_second_admin_can_be_removed_but_not_yourself(): void
    {
        $admin = $this->signedInAdmin();
        $second = $admin->company->users()->create(['name' => 'Second', 'email' => 'second@acme.test', 'password' => self::PASSWORD, 'role' => 'admin']);

        $this->put(route('business.team.members.deactivate', $admin->id))->assertSessionHasErrors('team');
        $this->assertNull($admin->fresh()->deactivated_at);

        $this->put(route('business.team.members.deactivate', $second->id))->assertSessionHasNoErrors();
        $this->assertNotNull($second->fresh()->deactivated_at);

        // Now the signed-in Admin is the last active one.
        $this->put(route('business.team.members.role', $admin->id), ['role' => 'booker'])->assertSessionHasErrors('team');
    }

    public function test_a_deactivated_admin_does_not_count_as_keeping_the_company_covered(): void
    {
        $admin = $this->signedInAdmin();
        $admin->company->users()->create(['name' => 'Gone', 'email' => 'gone@acme.test', 'password' => self::PASSWORD, 'role' => 'admin', 'deactivated_at' => now()]);

        $this->put(route('business.team.members.role', $admin->id), ['role' => 'booker'])->assertSessionHasErrors('team');
    }

    public function test_a_deactivated_person_can_be_reactivated(): void
    {
        $admin = $this->signedInAdmin();
        $gone = $admin->company->users()->create(['name' => 'Gone', 'email' => 'gone@acme.test', 'password' => self::PASSWORD, 'role' => 'booker', 'deactivated_at' => now()]);

        $this->put(route('business.team.members.reactivate', $gone->id))->assertSessionHasNoErrors();

        $this->assertNull($gone->fresh()->deactivated_at);
    }
}
