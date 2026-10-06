<?php

namespace App\Models\Concerns;

use App\Models\WorkItem;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * A booking or application that is worked by staff. The work item itself is
 * created and kept in step by App\Workflow\SyncsWorkItems.
 */
trait HasWorkItem
{
    public function workItem(): MorphOne
    {
        return $this->morphOne(WorkItem::class, 'subject');
    }
}
