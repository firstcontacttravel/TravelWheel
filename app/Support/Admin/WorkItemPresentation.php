<?php

namespace App\Support\Admin;

use App\Models\ActivityLog;
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
            'feed' => $item ? self::feed($item, $subject) : [],
        ])->render());
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
            default => [str($event->type)->headline()->toString()." ({$who})", 'idle'],
        };

        return [
            'title' => $title,
            'when' => self::when($event->created_at),
            'body' => $event->body,
            'tone' => $tone,
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
