<?php

namespace App\Models;

use App\Mail\CompanyPasswordResetMail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Mail;

/**
 * Someone at a company who signs in at /business. Never a member of staff:
 * this is a different table and a different guard from App\Models\User.
 */
class CompanyUser extends Authenticatable
{
    public const ROLES = [
        'admin' => 'Company Admin',
        'booker' => 'Booker',
    ];

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'password',
        'role',
        'is_system',
        'login_code_enabled',
        'login_code_hash',
        'login_code_expires_at',
        'login_code_attempts',
        'login_code_sent_at',
        'last_login_at',
        'deactivated_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'login_code_hash',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'login_code_enabled' => 'boolean',
            'is_system' => 'boolean',
            'login_code_expires_at' => 'datetime',
            'login_code_sent_at' => 'datetime',
            'last_login_at' => 'datetime',
            'deactivated_at' => 'datetime',
        ];
    }

    protected function email(): Attribute
    {
        return Attribute::make(set: fn (string $value): string => strtolower(trim($value)));
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isCompanyAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isDeactivated(): bool
    {
        return $this->deactivated_at !== null;
    }

    /** Admins control the wallet and the team, so they have no choice. */
    public function needsLoginCode(): bool
    {
        return $this->isCompanyAdmin() || $this->login_code_enabled;
    }

    /** May this person be signed in right now? */
    public function canSignIn(): bool
    {
        return ! $this->is_system && ! $this->isDeactivated() && $this->company?->isActive();
    }

    /**
     * Sessions remember this at sign-in. A changed password changes it, which
     * signs the person out everywhere else.
     */
    public function sessionFingerprint(): string
    {
        return hash_hmac('sha256', (string) $this->getAuthPassword(), (string) config('app.key'));
    }

    /**
     * Sent straight away, not queued: someone is waiting for it, and a stuck
     * scheduler must not lock people out of their account.
     *
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        if ($this->is_system) {
            return;
        }

        Mail::to($this->email)->send(new CompanyPasswordResetMail($this, $token));
    }
}
