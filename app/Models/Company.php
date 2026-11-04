<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    ];

    public function users(): HasMany
    {
        return $this->hasMany(CompanyUser::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(CompanyInvitation::class);
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
