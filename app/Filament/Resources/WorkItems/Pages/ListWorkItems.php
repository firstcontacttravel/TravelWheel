<?php

namespace App\Filament\Resources\WorkItems\Pages;

use App\Filament\Resources\WorkItems\WorkItemResource;
use App\Models\WorkItem;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListWorkItems extends ListRecords
{
    protected static string $resource = WorkItemResource::class;

    public function getTitle(): string
    {
        return 'My Work';
    }

    public function getSubheading(): ?string
    {
        return 'Everything you own, everything waiting on your department, and everything escalated to you, across every service.';
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'mine';
    }

    public function getTabs(): array
    {
        $tabs = [
            'mine' => Tab::make('Mine')
                ->query(fn (Builder $query): Builder => self::mine($query)),
            'department' => Tab::make('My department')
                ->query(fn (Builder $query): Builder => self::myDepartment($query)),
            'unclaimed' => Tab::make('Unclaimed')
                ->query(fn (Builder $query): Builder => self::unclaimed($query)),
            'escalated_to_me' => Tab::make('Escalated to me')
                ->badgeColor('warning')
                ->query(fn (Builder $query): Builder => self::escalatedToMe($query)),
            'escalated_by_me' => Tab::make('Escalated by me')
                ->query(fn (Builder $query): Builder => self::escalatedByMe($query)),
            'overdue' => Tab::make('Overdue')
                ->badgeColor('danger')
                ->query(fn (Builder $query): Builder => self::overdue($query)),
            'all' => Tab::make('All open')
                ->query(fn (Builder $query): Builder => $query->where('state', WorkItem::STATE_OPEN)),
        ];

        foreach (['mine', 'department', 'unclaimed', 'escalated_to_me', 'escalated_by_me', 'overdue'] as $key) {
            $tabs[$key]->badge(fn (): ?int => ($count = $this->{'count'.str($key)->studly()}()) > 0 ? $count : null);
        }

        return $tabs;
    }

    // Each tab's query lives in one place, shared by its list and its badge.

    private static function mine(Builder $query): Builder
    {
        return $query->active()->where('owner_id', auth()->id());
    }

    private static function myDepartment(Builder $query): Builder
    {
        return $query->active()->where('department_id', auth()->user()?->department_id ?? 0);
    }

    /** Waiting on a customer or supplier is not work anyone can claim, so only open items. */
    private static function unclaimed(Builder $query): Builder
    {
        return $query->where('state', WorkItem::STATE_OPEN)->whereNull('owner_id');
    }

    private static function escalatedToMe(Builder $query): Builder
    {
        $user = auth()->user();

        return $query->whereHas('escalations', fn (Builder $escalations) => $escalations
            ->awaiting($user)
            ->where('raised_by', '!=', $user?->getKey() ?? 0));
    }

    private static function escalatedByMe(Builder $query): Builder
    {
        return $query->whereHas('escalations', fn (Builder $escalations) => $escalations
            ->active()
            ->where('raised_by', auth()->id()));
    }

    private static function overdue(Builder $query): Builder
    {
        return $query->active()->whereNotNull('due_at')->where('due_at', '<', now());
    }

    private function countMine(): int
    {
        return self::mine(WorkItem::query())->count();
    }

    private function countDepartment(): int
    {
        return self::myDepartment(WorkItem::query())->count();
    }

    private function countUnclaimed(): int
    {
        return self::unclaimed(WorkItem::query())->count();
    }

    private function countEscalatedToMe(): int
    {
        return self::escalatedToMe(WorkItem::query())->count();
    }

    private function countEscalatedByMe(): int
    {
        return self::escalatedByMe(WorkItem::query())->count();
    }

    private function countOverdue(): int
    {
        return self::overdue(WorkItem::query())->count();
    }
}
