<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single-use, expiring link to join a company. Only the token's hash is
 * stored, so sending a link again always makes a new one.
 */
class CompanyInvitation extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'email',
        'role',
        'token_hash',
        'expires_at',
        'accepted_at',
        'revoked_at',
        'invited_by_user_id',
        'invited_by_company_user_id',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('accepted_at')->whereNull('revoked_at')->where('expires_at', '>', now());
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function findByToken(string $token): ?self
    {
        return self::where('token_hash', self::hashToken($token))->first();
    }

    public function isUsable(): bool
    {
        return $this->accepted_at === null
            && $this->revoked_at === null
            && $this->expires_at->isFuture()
            && $this->company?->hasStatus('invited', 'active');
    }
}
