<?php

namespace App\Models;

use App\Workflow\Workflow;
use App\Workflow\WorkflowRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * The work attached to one booking or application: where it is, who owns
 * it, which department's queue it sits in. Changed only through
 * WorkItemService, which writes the history.
 */
class WorkItem extends Model
{
    public const STATE_OPEN = 'open';

    public const STATE_WAITING = 'waiting';

    public const STATE_DONE = 'done';

    public const STATE_CANCELLED = 'cancelled';

    public const STATES = [
        self::STATE_OPEN => 'Needs action',
        self::STATE_WAITING => 'Waiting',
        self::STATE_DONE => 'Done',
        self::STATE_CANCELLED => 'Cancelled',
    ];

    public const PRIORITIES = [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'urgent' => 'Urgent',
    ];

    protected $fillable = [
        'service',
        'stage',
        'state',
        'owner_id',
        'department_id',
        'priority',
        'due_at',
        'claimed_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'claimed_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(WorkItemEvent::class)->latest('created_at')->latest('id');
    }

    public function escalations(): HasMany
    {
        return $this->hasMany(Escalation::class)->latest('id');
    }

    public function workflow(): Workflow
    {
        return app(WorkflowRegistry::class)->forService($this->service);
    }

    public function stageLabel(): string
    {
        return $this->workflow()->stages()[$this->stage]['label'] ?? str($this->stage)->headline()->toString();
    }

    public function isActive(): bool
    {
        return in_array($this->state, [self::STATE_OPEN, self::STATE_WAITING], true);
    }

    public function isOverdue(): bool
    {
        return $this->isActive() && $this->due_at !== null && $this->due_at->isPast();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('state', [self::STATE_OPEN, self::STATE_WAITING]);
    }
}
