<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\CompanyInvitation;
use App\Models\CompanyUser;
use App\Services\Corporate\CorporateAccounts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * A company Admin managing their own people. Everything is looked up through
 * the signed-in person's company, so another company's ids answer 404.
 */
class TeamController extends Controller
{
    private const LAST_ADMIN = 'A company must keep at least one active Admin. Make someone else an Admin first.';

    public function index(Request $request): View
    {
        $company = $this->company($request);

        return view('business.team', [
            'members' => $company->users()->orderByRaw('deactivated_at is not null')->orderBy('name')->get(),
            'invitations' => $company->pendingInvitations()->latest()->get(),
        ]);
    }

    public function invite(Request $request, CorporateAccounts $accounts): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', function (string $attribute, mixed $value, \Closure $fail) use ($accounts): void {
                if ($accounts->emailIsTaken((string) $value)) {
                    $fail('This email can\'t be invited. Use another address or contact TravelWheel.');
                }
            }],
            'role' => ['required', Rule::in(array_keys(CompanyUser::ROLES))],
        ]);

        $actor = $this->actor($request);
        $sent = $accounts->invite($this->company($request), $data['email'], $data['name'] ?? null, $data['role'], $actor);

        ActivityLog::recordForCompanyUser($actor, 'company_user_invited', 'Invited '.$data['email'].' as '.CompanyUser::ROLES[$data['role']], $this->company($request));

        return $sent
            ? back()->with('status', 'Invitation sent to '.$data['email'].'.')
            : back()->withErrors(['email' => 'The invitation was saved, but the email could not be sent. Please resend it shortly.']);
    }

    public function resend(Request $request, CorporateAccounts $accounts, int $invitation): RedirectResponse
    {
        $found = $this->invitation($request, $invitation);
        $actor = $this->actor($request);

        if ($accounts->emailIsTaken($found->email)) {
            return back()->withErrors(['email' => 'This person already has an account.']);
        }

        $sent = $accounts->invite($this->company($request), $found->email, $found->name, $found->role, $actor);

        ActivityLog::recordForCompanyUser($actor, 'company_user_invite_resent', 'Sent a new invitation to '.$found->email, $this->company($request));

        return $sent
            ? back()->with('status', 'A new invitation has been sent to '.$found->email.'. The old link no longer works.')
            : back()->withErrors(['email' => 'The email could not be sent. Please try again shortly.']);
    }

    public function revoke(Request $request, CorporateAccounts $accounts, int $invitation): RedirectResponse
    {
        $found = $this->invitation($request, $invitation);
        $accounts->revoke($found);

        ActivityLog::recordForCompanyUser($this->actor($request), 'company_user_invite_revoked', 'Revoked the invitation to '.$found->email, $this->company($request));

        return back()->with('status', 'Invitation revoked.');
    }

    public function updateRole(Request $request, int $member): RedirectResponse
    {
        $data = $request->validate(['role' => ['required', Rule::in(array_keys(CompanyUser::ROLES))]]);

        return $this->changeMember($request, $member, function (CompanyUser $target, Company $company) use ($data, $request): RedirectResponse {
            if ($target->role === $data['role']) {
                return back();
            }

            if ($target->isCompanyAdmin() && ! $this->hasAnotherActiveAdmin($company, $target)) {
                return back()->withErrors(['team' => self::LAST_ADMIN]);
            }

            $target->update(['role' => $data['role']]);

            ActivityLog::recordForCompanyUser($this->actor($request), 'company_user_role_changed', 'Made '.$target->email.' a '.CompanyUser::ROLES[$data['role']], $target);

            return back()->with('status', $target->name.' is now a '.CompanyUser::ROLES[$data['role']].'.');
        });
    }

    public function deactivate(Request $request, int $member): RedirectResponse
    {
        return $this->changeMember($request, $member, function (CompanyUser $target, Company $company) use ($request): RedirectResponse {
            if ($target->is($this->actor($request))) {
                return back()->withErrors(['team' => 'You can\'t deactivate your own account. Ask another Admin.']);
            }

            if ($target->isCompanyAdmin() && ! $this->hasAnotherActiveAdmin($company, $target)) {
                return back()->withErrors(['team' => self::LAST_ADMIN]);
            }

            $target->update(['deactivated_at' => now()]);

            ActivityLog::recordForCompanyUser($this->actor($request), 'company_user_deactivated', 'Deactivated '.$target->email, $target);

            return back()->with('status', $target->name.' can no longer sign in.');
        });
    }

    public function reactivate(Request $request, int $member): RedirectResponse
    {
        return $this->changeMember($request, $member, function (CompanyUser $target) use ($request): RedirectResponse {
            $target->update(['deactivated_at' => null]);

            ActivityLog::recordForCompanyUser($this->actor($request), 'company_user_reactivated', 'Reactivated '.$target->email, $target);

            return back()->with('status', $target->name.' can sign in again.');
        });
    }

    /** Locks the company, so two Admins removing each other can't leave it with none. */
    private function changeMember(Request $request, int $member, callable $change): RedirectResponse
    {
        return DB::transaction(function () use ($request, $member, $change) {
            $company = Company::whereKey($this->company($request)->getKey())->lockForUpdate()->firstOrFail();
            $target = $company->users()->findOrFail($member);

            return $change($target, $company);
        });
    }

    private function hasAnotherActiveAdmin(Company $company, CompanyUser $target): bool
    {
        return $company->users()
            ->where('role', 'admin')
            ->whereNull('deactivated_at')
            ->whereKeyNot($target->getKey())
            ->exists();
    }

    private function invitation(Request $request, int $id): CompanyInvitation
    {
        return $this->company($request)->pendingInvitations()->findOrFail($id);
    }

    private function actor(Request $request): CompanyUser
    {
        return $request->attributes->get('companyUser');
    }

    private function company(Request $request): Company
    {
        return $this->actor($request)->company;
    }
}
