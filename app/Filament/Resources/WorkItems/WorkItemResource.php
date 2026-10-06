<?php

namespace App\Filament\Resources\WorkItems;

use App\Filament\Resources\WorkItems\Pages\ListWorkItems;
use App\Models\Department;
use App\Models\WorkItem;
use App\Workflow\WorkflowRegistry;
use App\Workflow\WorkItemService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * My Work: every booking and application across services, as one queue.
 *
 * Not in a navigation group. It pins to the top of the rail beside the
 * dashboard, because it is the first thing staff open, and it belongs to no
 * single service.
 */
class WorkItemResource extends Resource
{
    protected static ?string $model = WorkItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static ?string $navigationLabel = 'My Work';

    protected static ?int $navigationSort = -1;

    protected static ?string $modelLabel = 'work item';

    protected static ?string $pluralModelLabel = 'My Work';

    protected static ?string $slug = 'my-work';

    private const TIMEZONE = 'Africa/Lagos';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['subject', 'owner', 'department']))
            ->defaultSort('updated_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->persistFiltersInSession()
            ->striped()
            ->poll('60s')
            ->recordUrl(fn (WorkItem $record): ?string => $record->subject ? $record->workflow()->url($record->subject) : null)
            ->columns([
                TextColumn::make('reference')
                    ->label('Booking')
                    ->state(fn (WorkItem $record): string => $record->subject ? $record->workflow()->reference($record->subject) : 'Deleted')
                    ->description(fn (WorkItem $record): string => $record->workflow()->label())
                    ->searchable(query: fn (Builder $query, string $search): Builder => self::searchReference($query, $search))
                    ->extraAttributes(['class' => 'tc-mono']),
                TextColumn::make('stage')
                    ->state(fn (WorkItem $record): string => $record->stageLabel())
                    ->description(fn (WorkItem $record): string => WorkItem::STATES[$record->state] ?? $record->state),
                TextColumn::make('owner.name')
                    ->label('Owner')
                    ->placeholder('Unclaimed'),
                TextColumn::make('department.name')
                    ->label('Queue')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('priority')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => WorkItem::PRIORITIES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'urgent' => 'danger',
                        'high' => 'warning',
                        'low' => 'gray',
                        default => 'info',
                    })
                    ->sortable(),
                TextColumn::make('due_at')
                    ->label('Due')
                    ->since()
                    ->tooltip(fn (WorkItem $record): ?string => $record->due_at?->timezone(self::TIMEZONE)->format('d M Y, H:i'))
                    ->color(fn (WorkItem $record): ?string => $record->isOverdue() ? 'danger' : null)
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Last touched')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('service')
                    ->options(fn (): array => collect(app(WorkflowRegistry::class)->all())->map->label()->all()),
                SelectFilter::make('department_id')
                    ->label('Queue')
                    ->options(fn (): array => Department::query()->orderBy('name')->pluck('name', 'id')->all()),
                SelectFilter::make('priority')
                    ->options(WorkItem::PRIORITIES),
            ])
            ->recordActions([
                Action::make('workClaim')
                    ->label('Claim')
                    ->icon('heroicon-o-hand-raised')
                    ->visible(fn (WorkItem $record): bool => $record->owner_id === null && $record->isActive())
                    ->action(function (WorkItem $record): void {
                        app(WorkItemService::class)->claim($record, auth()->user());

                        Notification::make()->title('You own this now')->success()->send();
                    }),
            ]);
    }

    /** Booking references live on each service's own table. */
    private static function searchReference(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $query) use ($search): void {
            foreach (app(WorkflowRegistry::class)->all() as $workflow) {
                $class = $workflow->subjectClass();
                $query->orWhere(fn (Builder $query) => $query
                    ->where('subject_type', (new $class)->getMorphClass())
                    ->whereIn('subject_id', $class::query()
                        ->select((new $class)->getKeyName())
                        ->where($workflow->referenceColumn(), 'like', "%{$search}%")));
            }
        });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkItems::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
