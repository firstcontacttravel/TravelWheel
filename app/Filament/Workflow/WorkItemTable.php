<?php

namespace App\Filament\Workflow;

use App\Models\WorkItem;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The owner column and "my work" filter shared by every booking queue.
 * Tables that use them should eager-load workItem.owner.
 */
class WorkItemTable
{
    public static function ownerColumn(): TextColumn
    {
        return TextColumn::make('work_owner')
            ->label('Owner')
            ->state(fn (Model $record): ?string => $record->workItem?->owner?->name)
            ->placeholder('Unclaimed')
            ->toggleable();
    }

    public static function filter(): SelectFilter
    {
        return SelectFilter::make('work')
            ->label('Work')
            ->options([
                'mine' => 'Mine',
                'my_department' => 'My department',
                'unclaimed' => 'Unclaimed',
                'needs_action' => 'Needs action',
                'overdue' => 'Overdue',
            ])
            ->query(function (Builder $query, array $data): Builder {
                $user = auth()->user();

                return match ($data['value'] ?? null) {
                    'mine' => $query->whereHas('workItem', fn (Builder $item) => $item->where('owner_id', $user?->getKey())),
                    'my_department' => $query->whereHas('workItem', fn (Builder $item) => $item->where('department_id', $user?->department_id)),
                    'unclaimed' => $query->whereHas('workItem', fn (Builder $item) => $item->active()->whereNull('owner_id')),
                    'needs_action' => $query->whereHas('workItem', fn (Builder $item) => $item->where('state', WorkItem::STATE_OPEN)),
                    'overdue' => $query->whereHas('workItem', fn (Builder $item) => $item->active()->where('due_at', '<', now())),
                    default => $query,
                };
            });
    }
}
