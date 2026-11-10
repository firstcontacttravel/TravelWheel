<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A company with, or asking for, a corporate account at /business.
 *
 * Moves requested -> invited -> active, and can be rejected from requested or
 * suspended from invited or active. CorporateAccounts makes every move.
 */
class Company extends Model
{
    public const STATUSES = [
        'requested' => 'Requested',
        'invited' => 'Invited',
        'active' => 'Active',
        'suspended' => 'Suspended',
        'rejected' => 'Rejected',
    ];

    public const STATUS_COLORS = [
        'requested' => 'warning',
        'invited' => 'info',
        'active' => 'success',
        'suspended' => 'danger',
        'rejected' => 'gray',
    ];

    protected $fillable = [
        'name',
        'cac_number',
        'contact_name',
        'contact_email',
        'contact_phone',
        'status',
        'status_reason',
        'account_manager_id',
        'suspended_at',
    ];

    protected function casts(): array
    {
        return [
            'wallet_balance' => 'decimal:2',
            'credit_limit' => 'decimal:2',
            'suspended_at' => 'datetime',
        ];
    }

    /** The company's real people. Staff's stand-in is kept apart: see systemUser(). */
    public function users(): HasMany
    {
        return $this->hasMany(CompanyUser::class)->where('is_system', false);
    }

    /**
     * Who staff act as when booking for this company. Its password is random
     * and thrown away, and CompanyUser refuses to let it sign in.
     */
    public function systemUser(): CompanyUser
    {
        $user = CompanyUser::query()->firstOrCreate(
            ['company_id' => $this->id, 'is_system' => true],
            [
                'name' => 'TravelWheel staff',
                'email' => 'travelwheel-staff-'.$this->id.'@system.invalid',
                'password' => Str::random(64),
                'role' => 'booker',
            ],
        );

        return $user->setRelation('company', $this);
    }

    public function canBeBookedForBy(User $staff): bool
    {
        // IT doesn't book for customers, a company's paid from its wallet included
        return $this->isActive()
            && ! $staff->isDeactivated()
            && ($staff->isAdmin() || (! $staff->isIt() && ($staff->isDepartmentHead() || (int) $this->account_manager_id === (int) $staff->id)));
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(CompanyInvitation::class);
    }

    public function walletEntries(): HasMany
    {
        return $this->hasMany(CompanyWalletEntry::class);
    }

    public function recentWalletEntries(): HasMany
    {
        return $this->walletEntries()->latest('id')->limit(50);
    }

    public function walletTopUps(): HasMany
    {
        return $this->hasMany(CompanyWalletTopUp::class);
    }

    public function travellers(): HasMany
    {
        return $this->hasMany(CompanyTraveller::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(CompanyInvoice::class);
    }

    public function accountManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_manager_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasStatus(string ...$statuses): bool
    {
        return in_array($this->status, $statuses, true);
    }

    public function pendingInvitations(): HasMany
    {
        return $this->invitations()->pending();
    }

    /** Companies a new request or record must not duplicate. A rejected company may apply again. */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', '!=', 'rejected');
    }

    /** "rc 123 456" and "RC123456" are the same registration. */
    public static function normalizeCacNumber(string $number): string
    {
        return strtoupper(preg_replace('/[\s\-]+/', '', $number));
    }
}
