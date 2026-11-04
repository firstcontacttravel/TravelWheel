<?php

namespace App\Services\Corporate;

use App\Mail\CompanyInvitationMail;
use App\Mail\CompanyRequestReceivedMail;
use App\Models\Company;
use App\Models\CompanyInvitation;
use App\Models\CompanyUser;
use App\Models\User;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Every change to a company's account or its invitations, so the rules live
 * in one place whichever screen asks: the public form, the admin Corporate
 * section or a company's own team page.
 *
 * Mail goes out straight away rather than through the queue. A person is
 * waiting for it, and whoever sent it needs to know if it failed. Failures
 * return false; they never undo the change, and a new link can be sent.
 */
class CorporateAccounts
{
    /**
     * From the public form. Returns null for a company already on file, which
     * the form treats exactly like success so it can't be used to find out who
     * has an account.
     */
    public function submitRequest(array $data): ?Company
    {
        $cacNumber = Company::normalizeCacNumber($data['cac_number']);
        $email = strtolower(trim($data['contact_email']));

        $alreadyKnown = Company::query()
            ->open()
            ->where(fn ($query) => $query->where('cac_number', $cacNumber)->orWhere('contact_email', $email))
            ->exists();

        if ($alreadyKnown) {
            return null;
        }

        $company = Company::create([
            'name' => $data['company_name'],
            'cac_number' => $cacNumber,
            'contact_name' => $data['contact_name'],
            'contact_email' => $email,
            'contact_phone' => $data['contact_phone'] ?? null,
            'status' => 'requested',
        ]);

        try {
            Mail::to(config('corporate.notify_email'))->send(new CompanyRequestReceivedMail($company));
        } catch (Throwable $exception) {
            // It's in the admin queue with a badge either way.
            Log::error('Corporate request notification failed', ['company_id' => $company->id, 'error' => $exception->getMessage()]);
        }

        return $company;
    }

    /** Staff adding a company directly, which also invites its named contact. */
    public function addCompany(array $data, User $staff): array
    {
        $company = Company::create([
            'name' => $data['name'],
            'cac_number' => Company::normalizeCacNumber($data['cac_number']),
            'contact_name' => $data['contact_name'],
            'contact_email' => strtolower(trim($data['contact_email'])),
            'contact_phone' => $data['contact_phone'] ?? null,
            'status' => 'invited',
        ]);

        return [$company, $this->invite($company, $company->contact_email, $company->contact_name, 'admin', $staff)];
    }

    /** Approving a request is inviting its named contact. */
    public function approve(Company $company, string $contactName, string $contactEmail, User $staff): bool
    {
        $this->assertStatus($company, 'requested');

        $company->update([
            'contact_name' => $contactName,
            'contact_email' => strtolower(trim($contactEmail)),
            'status' => 'invited',
        ]);

        return $this->invite($company, $company->contact_email, $company->contact_name, 'admin', $staff);
    }

    public function reject(Company $company, string $reason): void
    {
        $this->assertStatus($company, 'requested');

        $company->update(['status' => 'rejected', 'status_reason' => $reason]);
    }

    /** Takes effect on the company's next request; see EnsureCompanyUser. */
    public function suspend(Company $company, string $reason): void
    {
        $this->assertStatus($company, 'invited', 'active');

        $company->update(['status' => 'suspended', 'status_reason' => $reason]);
    }

    public function reactivate(Company $company): void
    {
        $this->assertStatus($company, 'suspended');

        $company->update([
            'status' => $company->users()->exists() ? 'active' : 'invited',
            'status_reason' => null,
        ]);
    }

    /**
     * Replaces any open invitation for this person at this company, so only
     * the newest link works.
     *
     * @return bool whether the email was accepted for delivery
     */
    public function invite(Company $company, string $email, ?string $name, string $role, User|CompanyUser $invitedBy): bool
    {
        $email = strtolower(trim($email));

        if ($this->emailIsTaken($email)) {
            throw new DomainException('That email already has a corporate account.');
        }

        $this->assertStatus($company, 'invited', 'active');

        $token = Str::random(64);

        $invitation = DB::transaction(function () use ($company, $email, $name, $role, $invitedBy, $token) {
            $company->pendingInvitations()->where('email', $email)->update(['revoked_at' => now()]);

            return $company->invitations()->create([
                'email' => $email,
                'name' => $name,
                'role' => $role,
                'token_hash' => CompanyInvitation::hashToken($token),
                'expires_at' => now()->addDays((int) config('corporate.invitation_days')),
                'invited_by_user_id' => $invitedBy instanceof User ? $invitedBy->getKey() : null,
                'invited_by_company_user_id' => $invitedBy instanceof CompanyUser ? $invitedBy->getKey() : null,
            ]);
        });

        try {
            Mail::to($email)->send(new CompanyInvitationMail($invitation, $token));

            return true;
        } catch (Throwable $exception) {
            Log::error('Corporate invitation email failed', ['invitation_id' => $invitation->id, 'error' => $exception->getMessage()]);

            return false;
        }
    }

    public function revoke(CompanyInvitation $invitation): void
    {
        $invitation->update(['revoked_at' => now()]);
    }

    /**
     * Turns an invitation into a signed-up person. Locked and checked again
     * here, so two clicks on one link can't create two accounts.
     *
     * @throws DomainException when the link can no longer be used
     */
    public function accept(CompanyInvitation $invitation, string $name, string $password): CompanyUser
    {
        try {
            return DB::transaction(function () use ($invitation, $name, $password) {
                $invitation = CompanyInvitation::whereKey($invitation->getKey())->lockForUpdate()->firstOrFail();

                if (! $invitation->isUsable() || $this->emailIsTaken($invitation->email)) {
                    throw new DomainException('This invitation can no longer be used.');
                }

                $user = $invitation->company->users()->create([
                    'name' => $name,
                    'email' => $invitation->email,
                    'password' => $password,
                    'role' => $invitation->role,
                ]);

                $invitation->update(['accepted_at' => now()]);

                if ($invitation->company->status === 'invited') {
                    $invitation->company->update(['status' => 'active']);
                }

                return $user;
            });
        } catch (UniqueConstraintViolationException) {
            throw new DomainException('This invitation can no longer be used.');
        }
    }

    public function emailIsTaken(string $email): bool
    {
        return CompanyUser::where('email', strtolower(trim($email)))->exists();
    }

    private function assertStatus(Company $company, string ...$allowed): void
    {
        if (! $company->hasStatus(...$allowed)) {
            throw new DomainException("A {$company->status} company can't do that.");
        }
    }
}
