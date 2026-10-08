<?php

namespace App\Filament\Workflow;

use App\Models\Department;
use App\Models\User;
use App\Models\WorkItem;
use App\Workflow\WorkAccess;
use App\Workflow\WorkItemService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Model;

/**
 * The Work menu on a booking's page: claim, release, reassign, note,
 * priority. Anyone may claim an unowned booking; the owner adds notes and
 * releases it; reassigning, taking over and priority are for department
 * heads and the CEO (WorkAccess).
 *
 * Action names start with "work" so the activity feed on the panel can leave
 * them out — the work history already says the same thing in plainer words.
 */
class WorkItemActions
{
    public static function group(): ActionGroup
    {
        return ActionGroup::make([
            self::claimAction(),
            self::releaseAction(),
            self::reassignAction(),
            EscalationActions::escalateAction(),
            self::noteAction(),
            self::priorityAction(),
        ])
            ->label('Work')
            ->icon('heroicon-o-user-circle')
            ->color('gray')
            ->button();
    }

    public static function claimAction(): Action
    {
        return Action::make('workClaim')
            ->label(fn (Model $record): string => self::item($record)?->owner_id ? 'Take over' : 'Claim')
            ->icon('heroicon-o-hand-raised')
            ->visible(fn (Model $record): bool => WorkAccess::canClaim(auth()->user(), self::item($record)))
            ->requiresConfirmation(fn (Model $record): bool => (bool) self::item($record)?->owner_id)
            ->modalDescription(fn (Model $record): ?string => ($owner = self::item($record)?->owner)
                ? "{$owner->name} owns this. Taking over makes you the owner; they will see it in the history."
                : null)
            ->action(function (Model $record): void {
                app(WorkItemService::class)->claim(self::itemOrSync($record), auth()->user());

                Notification::make()->title('You own this now')->success()->send();
            });
    }

    public static function releaseAction(): Action
    {
        return Action::make('workRelease')
            ->label('Release')
            ->icon('heroicon-o-arrow-uturn-left')
            ->visible(fn (Model $record): bool => ($item = self::item($record)) !== null
                && $item->owner_id !== null
                && ($item->owner_id === auth()->id() || WorkAccess::canManage(auth()->user(), $item)))
            ->requiresConfirmation()
            ->modalDescription(fn (Model $record): string => 'It goes back to the '.(self::item($record)?->department?->name ?? 'shared').' queue for anyone to claim.')
            ->action(function (Model $record): void {
                app(WorkItemService::class)->release(self::itemOrSync($record), auth()->user());

                Notification::make()->title('Released to the queue')->success()->send();
            });
    }

    public static function reassignAction(): Action
    {
        return Action::make('workReassign')
            ->visible(fn (Model $record): bool => WorkAccess::canManage(auth()->user(), self::item($record)))
            ->label('Reassign')
            ->icon('heroicon-o-arrows-right-left')
            ->modalWidth('lg')
            ->modalDescription('Give it to a person, move it to another department\'s queue, or both.')
            ->fillForm(fn (Model $record): array => [
                'department_id' => self::item($record)?->department_id,
                'owner_id' => self::item($record)?->owner_id,
            ])
            ->form([
                Select::make('department_id')
                    ->label('Department')
                    ->options(fn (): array => Department::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('owner_id', null)),
                Select::make('owner_id')
                    ->label('Person')
                    ->placeholder('Nobody, leave it in the queue')
                    ->options(fn (Get $get): array => self::staffOptions($get('department_id')))
                    ->searchable(),
                Textarea::make('note')
                    ->label('Note')
                    ->helperText('Why, and anything they need to know.')
                    ->rows(3)
                    ->maxLength(2000),
            ])
            ->action(function (Model $record, array $data): void {
                $owner = filled($data['owner_id'] ?? null) ? User::find($data['owner_id']) : null;
                $department = filled($data['department_id'] ?? null) ? Department::find($data['department_id']) : null;

                app(WorkItemService::class)->assign(self::itemOrSync($record), $owner, $department, auth()->user(), $data['note'] ?? null);

                Notification::make()
                    ->title($owner ? "Assigned to {$owner->name}" : 'Moved to the '.($department?->name ?? 'shared').' queue')
                    ->success()
                    ->send();
            });
    }

    public static function noteAction(): Action
    {
        return Action::make('workNote')
            ->visible(fn (Model $record): bool => WorkAccess::canWork(auth()->user(), self::item($record)))
            ->label('Add note')
            ->icon('heroicon-o-chat-bubble-left-ellipsis')
            ->modalWidth('lg')
            ->form([
                Textarea::make('body')
                    ->label('Note')
                    ->helperText('Visible to staff only, never to the customer.')
                    ->required()
                    ->rows(4)
                    ->maxLength(5000),
            ])
            ->action(function (Model $record, array $data): void {
                app(WorkItemService::class)->addNote(self::itemOrSync($record), auth()->user(), $data['body']);

                Notification::make()->title('Note added')->success()->send();
            });
    }

    public static function priorityAction(): Action
    {
        return Action::make('workPriority')
            ->visible(fn (Model $record): bool => WorkAccess::canManage(auth()->user(), self::item($record)))
            ->label('Set priority')
            ->icon('heroicon-o-flag')
            ->modalWidth('sm')
            ->fillForm(fn (Model $record): array => ['priority' => self::item($record)?->priority ?? 'normal'])
            ->form([
                Select::make('priority')
                    ->options(WorkItem::PRIORITIES)
                    ->required(),
            ])
            ->action(function (Model $record, array $data): void {
                app(WorkItemService::class)->setPriority(self::itemOrSync($record), $data['priority'], auth()->user());

                Notification::make()->title('Priority set to '.WorkItem::PRIORITIES[$data['priority']])->success()->send();
            });
    }

    /**
     * Active staff to pick from, narrowed to a department when one is chosen.
     * The CEO is always listed: anything can be sent up to them.
     *
     * @return array<int, string>
     */
    public static function staffOptions(mixed $departmentId, ?int $except = null): array
    {
        return User::query()
            ->whereNull('deactivated_at')
            ->when($except, fn ($query) => $query->whereKeyNot($except))
            ->when($departmentId, fn ($query) => $query->where(fn ($query) => $query
                ->where('department_id', $departmentId)
                ->orWhere('is_admin', true)))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    private static function item(Model $record): ?WorkItem
    {
        // Queried, not the loaded relation: after Claim the page must see the
        // new owner, not the one it was rendered with.
        return $record->workItem()->with(['owner', 'department'])->first();
    }

    /** Bookings from before the backfill get their work item on first touch. */
    public static function itemOrSync(Model $record): WorkItem
    {
        $service = app(WorkItemService::class);

        return $service->for($record) ?? $service->sync($record);
    }
}
