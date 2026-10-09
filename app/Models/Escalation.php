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
     * Who may answer: the person it names, and the target department's heads.
     * A department with no head yet falls back to anyone in it, so an
     * escalation is never stranded. The CEO always may.
     */
    public function canBeRespondedToBy(User $user): bool
    {
        if ($user->isAdmin() || ($this->to_user_id && $this->to_user_id === $user->getKey())) {
            return true;
        }

        if ($this->to_department_id === null || $this->to_department_id !== $user->department_id) {
            return false;
        }

        return $user->isDepartmentHead() || (! $this->to_user_id && ! self::departmentHasHead($this->to_department_id));
    }

    public static function departmentHasHead(int $departmentId): bool
    {
        return User::query()
            ->where('department_id', $departmentId)
            ->where('is_department_head', true)
            ->whereNull('deactivated_at')
            ->exists();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_OPEN, self::STATUS_ACCEPTED]);
    }

    /**
     * Active escalations this person may answer: ones naming them, and, for
     * a department head, everything sent to their department. Mirrors
     * canBeRespondedToBy(), including the no-head fallback.
     */
    public function scopeAwaiting(Builder $query, User $user): Builder
    {
        $department = $user->department_id ?? 0;
        $answersForDepartment = $user->isDepartmentHead() || ($department && ! self::departmentHasHead($department));

        return $query->active()->where(function (Builder $query) use ($user, $department, $answersForDepartment): void {
            $query->where('to_user_id', $user->getKey());

            if ($answersForDepartment) {
                $query->orWhere(fn (Builder $query) => $query
                    ->where('to_department_id', $department)
                    ->when(! $user->isDepartmentHead(), fn (Builder $query) => $query->whereNull('to_user_id')));
            }
        });
    }
}
