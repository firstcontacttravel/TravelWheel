<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line in a work item's history. Append-only.
 */
class WorkItemEvent extends Model
{
    public const UPDATED_AT = null;

    public const CREATED = 'created';

    public const STAGE_CHANGED = 'stage_changed';

    public const CLAIMED = 'claimed';

    public const RELEASED = 'released';

    public const REASSIGNED = 'reassigned';

    /** Moved to another department's queue. */
    public const MOVED = 'moved';

    public const NOTE = 'note';

    public const PRIORITY_CHANGED = 'priority_changed';

    public const ESCALATED = 'escalated';

    public const ESCALATION_ACCEPTED = 'escalation_accepted';

    public const ESCALATION_RESOLVED = 'escalation_resolved';

    public const ESCALATION_DECLINED = 'escalation_declined';

    public const ESCALATION_WITHDRAWN = 'escalation_withdrawn';

    protected $fillable = [
        'type',
        'user_id',
        'from',
        'to',
        'body',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function workItem(): BelongsTo
    {
        return $this->belongsTo(WorkItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
