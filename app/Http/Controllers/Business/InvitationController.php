<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CompanyInvitation;
use App\Services\Corporate\BusinessSession;
use App\Services\Corporate\CorporateAccounts;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** Opening an invitation link and creating the account it offers. */
class InvitationController extends Controller
{
    public function show(string $token, CorporateAccounts $accounts): View|Response
    {
        $invitation = CompanyInvitation::findByToken($token);

        if (! $invitation?->isUsable() || $accounts->emailIsTaken($invitation->email)) {
            return $this->invalid();
        }

        return view('business.invitation.show', ['invitation' => $invitation, 'token' => $token]);
    }

    public function accept(Request $request, string $token, CorporateAccounts $accounts, BusinessSession $session): RedirectResponse|Response
    {
        $invitation = CompanyInvitation::findByToken($token);

        if (! $invitation?->isUsable()) {
            return $this->invalid();
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
        ]);

        try {
            $user = $accounts->accept($invitation, $data['name'], $data['password']);
        } catch (DomainException) {
            return $this->invalid();
        }

        ActivityLog::recordForCompanyUser($user, 'company_invitation_accepted', 'Accepted an invitation to '.$invitation->company->name, $invitation->company);

        // The link reached their inbox, which is what an email code would
        // prove, so no second code straight after setting a password.
        $session->signIn($request, $user);

        return redirect()->route('business.dashboard');
    }

    private function invalid(): Response
    {
        return response()->view('business.invitation.invalid', [], 410);
    }
}
