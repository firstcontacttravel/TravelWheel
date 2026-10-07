<?php

namespace App\Support\Admin;

use App\Models\ActivityLog;
use App\Models\Escalation;
use App\Models\WorkItem;
use App\Models\WorkItemEvent;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

/**
 * The Work panel on a booking's page: who owns it, where it is, and one
 * history that merges work changes with everything done to the booking in
 * the admin.
 */
class WorkItemPresentation
{
    private const TIMEZONE = 'Africa/Lagos';

    private const FEED_LIMIT = 25;

    public static function panel(Model $subject): HtmlString
    {
        $item = $subject->workItem()->with(['owner', 'department'])->first();

        return new HtmlString(view('filament.workflow.work-panel', [
            'item' => $item,
            'facts' => $item ? self::facts($item) : [],
            'escalations' => $item ? self::openEscalations($item) : [],
            'feed' => $item ? self::feed($item, $subject) : [],
        ])->render());
    }

    /**
     * What is still waiting on someone else, above the history so it is not
     * lost in it.
     *
     * @return list<array{title: string, when: string, body: string|null, tone: string}>
     */
    private static function openEscalations(WorkItem $item): array
    {
        return $item->escalations()->active()->with(['raiser', 'toUser', 'toDepartment', 'responder'])->get()
            ->map(fn (Escalation $escalation): array => [
                'title' => ($escalation->mode === Escalation::MODE_HANDOFF ? 'Hand-off to ' : 'Help from ').$escalation->targetLabel()
                    .' · '.($escalation->status === Escalation::STATUS_ACCEPTED
                        ? ($escalation->responder?->name ?? 'Someone').' is on it'
                        : 'waiting for a response')
                    .($escalation->linear_identifier ? " · Linear {$escalation->linear_identifier}" : ($escalation->linear_requested ? ' · Linear issue pending' : '')),
                'url' => $escalation->linear_issue_url,
                'when' => ($escalation->raiser?->name ?? 'Someone').', '.self::when($escalation->created_at),
                'body' => $escalation->reason,
                'tone' => $escalation->status === Escalation::STATUS_ACCEPTED ? 'progress' : (in_array($escalation->priority, ['high', 'urgent'], true) ? 'critical' : 'warning'),
            ])
            ->all();
    }

    /** @return array<string, string> */
    private static function facts(WorkItem $item): array
    {
        return [
            'Owner' => $item->owner?->name ?? 'Unclaimed',
            'Queue' => $item->department?->name ?? '-',
            'Stage' => $item->stageLabel(),
            'Status' => WorkItem::STATES[$item->state] ?? $item->state,
            'Priority' => WorkItem::PRIORITIES[$item->priority] ?? $item->priority,
            'Due' => $item->due_at ? self::when($item->due_at).($item->isOverdue() ? ' (overdue)' : '') : '-',
        ];
    }

    /**
     * Work history and the activity log for the booking, newest first. Work
     * actions are left out of the activity side; the work history says the
     * same thing in plainer words.
     *
     * @return list<array{title: string, when: string, body: string|null, tone: string}>
     */
    private static function feed(WorkItem $item, Model $subject): array
    {
        $events = $item->events()->with('user')->limit(self::FEED_LIMIT)->get()
            ->map(fn (WorkItemEvent $event): array => ['at' => $event->created_at, 'entry' => self::eventEntry($item, $event)]);

        $activity = ActivityLog::query()
            ->with('user')
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->where('action', 'not like', 'work%')
            ->latest('created_at')
            ->limit(self::FEED_LIMIT)
            ->get()
            ->map(fn (ActivityLog $log): array => ['at' => $log->created_at, 'entry' => [
                'title' => ($log->user?->name ?? 'System').': '.$log->description,
                'when' => self::when($log->created_at),
                'body' => null,
                'tone' => 'progress',
            ]]);

        return $events->concat($activity)
            ->sortByDesc(fn (array $row) => $row['at']?->getTimestamp() ?? 0)
            ->take(self::FEED_LIMIT)
            ->pluck('entry')
            ->values()
            ->all();
    }

    /** @return array{title: string, when: string, body: string|null, tone: string} */
    private static function eventEntry(WorkItem $item, WorkItemEvent $event): array
    {
        $who = $event->user?->name ?? 'System';
        $stages = $item->workflow()->stages();
        $stage = fn (?string $key): string => $stages[$key]['label'] ?? str((string) $key)->headline()->toString();

        [$title, $tone] = match ($event->type) {
            WorkItemEvent::CREATED => ['Work started: '.$stage($event->to), 'idle'],
            WorkItemEvent::STAGE_CHANGED => [$stage($event->from).' → '.$stage($event->to).' ('.$who.')', self::stageTone($stages[$event->to]['state'] ?? null)],
            WorkItemEvent::CLAIMED => [$event->from ? "{$who} took over from {$event->from}" : "{$who} claimed it", 'positive'],
            WorkItemEvent::RELEASED => ["{$who} released it to the queue", 'idle'],
            WorkItemEvent::REASSIGNED => ["{$who} assigned it to {$event->to}", 'info'],
            WorkItemEvent::MOVED => ["{$who} moved it to the {$event->to} queue", 'info'],
            WorkItemEvent::NOTE => ["{$who} added a note", 'info'],
            WorkItemEvent::PRIORITY_CHANGED => ["{$who} set priority to ".(WorkItem::PRIORITIES[$event->to] ?? $event->to), 'warning'],
            WorkItemEvent::ESCALATED => [
                ($event->metadata['mode'] ?? null) === Escalation::MODE_HANDOFF
                    ? "{$who} handed it off to {$event->to}"
                    : "{$who} asked {$event->to} for help",
                'warning',
            ],
            WorkItemEvent::ESCALATION_ACCEPTED => ["{$who} accepted the escalation", 'info'],
            WorkItemEvent::ESCALATION_RESOLVED => ["{$who} resolved the escalation", 'positive'],
            WorkItemEvent::ESCALATION_DECLINED => ["{$who} declined the escalation", 'critical'],
            WorkItemEvent::ESCALATION_WITHDRAWN => ["{$who} withdrew the escalation", 'idle'],
            WorkItemEvent::DEADLINE_WARNING => ["Due soon: {$event->to}", 'warning'],
            WorkItemEvent::DEADLINE_MISSED => ["Deadline missed: {$event->to}", 'critical'],
            WorkItemEvent::DEADLINE_CEO => ["Still overdue, the CEO was told: {$event->to}", 'critical'],
            WorkItemEvent::LINEAR_LINKED => ["Linear issue {$event->to} opened", 'info'],
            WorkItemEvent::LINEAR_FAILED => ['Could not open the Linear issue; the escalation still stands', 'critical'],
            WorkItemEvent::LINEAR_COMMENT => ["{$event->from} commented in Linear ({$event->to})", 'info'],
            default => [str($event->type)->headline()->toString()." ({$who})", 'idle'],
        };

        return [
            'title' => $title,
            'when' => self::when($event->created_at),
            'body' => $event->body,
            'tone' => $tone,
            'url' => $event->metadata['url'] ?? null,
        ];
    }

    private static function stageTone(?string $state): string
    {
        return match ($state) {
            WorkItem::STATE_OPEN => 'pending',
            WorkItem::STATE_DONE => 'positive',
            default => 'idle',
        };
    }

    private static function when(?CarbonInterface $at): string
    {
        return $at ? $at->copy()->timezone(self::TIMEZONE)->format('d M Y, H:i') : '-';
    }
}
