<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A request for another person or department to step in on a work item.
 * Changed only through EscalationService.
 */
class Escalation extends Model
{
    public const MODE_HELP = 'help';

    public const MODE_HANDOFF = 'handoff';

    public const MODES = [
        self::MODE_HELP => 'Ask for help (I keep it)',
        self::MODE_HANDOFF => 'Hand it off (they take it over)',
    ];

    public const STATUS_OPEN = 'open';

    /** Help: someone is on it. A hand-off is resolved the moment it is accepted. */
    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_WITHDRAWN = 'withdrawn';

    public const STATUSES = [
        self::STATUS_OPEN => 'Waiting for a response',
        self::STATUS_ACCEPTED => 'Being handled',
        self::STATUS_RESOLVED => 'Resolved',
        self::STATUS_DECLINED => 'Declined',
        self::STATUS_WITHDRAWN => 'Withdrawn',
    ];

    protected $fillable = [
        'mode',
        'status',
        'raised_by',
        'to_department_id',
        'to_user_id',
        'priority',
        'reason',
        'responded_by',
        'response_note',
        'accepted_at',
        'closed_at',
        'linear_requested',
        'linear_issue_id',
        'linear_identifier',
        'linear_issue_url',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'closed_at' => 'datetime',
            'linear_requested' => 'boolean',
        ];
    }

    public function workItem(): BelongsTo
    {
        return $this->belongsTo(WorkItem::class);
    }

    public function raiser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'raised_by');
    }

    public function toDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'to_department_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_OPEN, self::STATUS_ACCEPTED], true);
    }

    /** "Ngozi (Visas)", "the Finance department". */
    public function targetLabel(): string
    {
        if ($this->toUser) {
            return $this->toUser->name.($this->toDepartment ? " ({$this->toDepartment->name})" : '');
        }

        return 'the '.($this->toDepartment?->name ?? 'unknown').' department';
    }

    /**
     * The people who may respond: the named person if there is one,
     * otherwise anyone in the department. The CEO always may.
     */
    public function canBeRespondedToBy(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($this->to_user_id) {
            return $this->to_user_id === $user->getKey();
        }

        return $this->to_department_id !== null && $this->to_department_id === $user->department_id;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_OPEN, self::STATUS_ACCEPTED]);
    }

    /** Active escalations waiting on this person, directly or through their department. */
    public function scopeAwaiting(Builder $query, User $user): Builder
    {
        return $query->active()->where(function (Builder $query) use ($user): void {
            $query->where('to_user_id', $user->getKey())
                ->orWhere(fn (Builder $query) => $query
                    ->whereNull('to_user_id')
                    ->where('to_department_id', $user->department_id ?? 0));
        });
    }
}
