<?php

namespace App\Filament\Workflow;

use App\Models\Department;
use App\Models\Escalation;
use App\Models\User;
use App\Models\WorkItem;
use App\Services\Linear\LinearClient;
use App\Workflow\EscalationService;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Raising an escalation (in the Work menu) and answering one (the Escalation
 * menu, shown only when one is waiting on you or was raised by you).
 *
 * Named "work…" like the rest of the Work menu so the panel's activity feed
 * leaves them out; the work history already records each step.
 */
class EscalationActions
{
    public static function responseGroup(): ActionGroup
    {
        return ActionGroup::make([
            self::acceptAction(),
            self::resolveAction(),
            self::declineAction(),
            self::withdrawAction(),
        ])
            ->label('Escalation')
            ->icon('heroicon-o-arrow-up-right')
            ->color('warning')
            ->button();
    }

    public static function escalateAction(): Action
    {
        return Action::make('workEscalate')
            ->label('Escalate')
            ->icon('heroicon-o-arrow-up-right')
            ->modalHeading('Escalate')
            ->modalDescription('Ask another department or person to step in. They are told in the admin and by email.')
            ->modalWidth('lg')
            ->form([
                Radio::make('mode')
                    ->label('What should happen to this booking?')
                    ->options(Escalation::MODES)
                    ->default(Escalation::MODE_HELP)
                    ->required(),
                Select::make('department_id')
                    ->label('Department')
                    ->options(fn (): array => Department::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('person_id', null))
                    ->requiredWithout('person_id')
                    ->validationMessages(['required_without' => 'Choose a department, a person, or both.']),
                Select::make('person_id')
                    ->label('Person')
                    ->placeholder('Anyone in the department')
                    ->options(fn (Get $get): array => WorkItemActions::staffOptions($get('department_id'), auth()->id()))
                    ->searchable(),
                Select::make('priority')
                    ->options(WorkItem::PRIORITIES)
                    ->default('normal')
                    ->required(),
                Textarea::make('reason')
                    ->label('What do you need?')
                    ->helperText('Be specific: they may not have seen this booking before. If it goes to Linear this text goes too, so leave out passport and card numbers.')
                    ->required()
                    ->rows(4)
                    ->maxLength(2000),
                Toggle::make('linear')
                    ->label('Also track it in Linear')
                    ->helperText(fn (Get $get): string => self::goesToIt($get('department_id'))
                        ? 'Escalations to IT always go to Linear.'
                        : 'Creates an issue in the TravelWheel team. Completing it there closes this escalation.')
                    ->disabled(fn (Get $get): bool => self::goesToIt($get('department_id')))
                    ->visible(fn (): bool => app(LinearClient::class)->isConfigured()),
            ])
            ->action(function (Model $record, array $data, Action $action): void {
                $escalation = self::attempt($action, fn () => app(EscalationService::class)->raise(
                    WorkItemActions::itemOrSync($record),
                    auth()->user(),
                    $data['mode'],
                    filled($data['department_id'] ?? null) ? Department::find($data['department_id']) : null,
                    filled($data['person_id'] ?? null) ? User::find($data['person_id']) : null,
                    $data['reason'],
                    $data['priority'],
                    (bool) ($data['linear'] ?? false),
                ));

                Notification::make()->title('Escalated to '.$escalation->targetLabel())->success()->send();
            });
    }

    public static function acceptAction(): Action
    {
        return Action::make('workEscalationAccept')
            ->label(fn (Model $record): string => self::waitingOnMe($record)?->mode === Escalation::MODE_HANDOFF ? 'Accept and take over' : 'Accept, I\'m on it')
            ->icon('heroicon-o-check')
            ->visible(fn (Model $record): bool => self::waitingOnMe($record)?->status === Escalation::STATUS_OPEN)
            ->requiresConfirmation()
            ->modalDescription(fn (Model $record): string => self::summary(self::waitingOnMe($record)))
            ->action(function (Model $record, Action $action): void {
                self::attempt($action, fn () => app(EscalationService::class)->accept(self::waitingOnMe($record), auth()->user()));

                Notification::make()->title('Accepted')->success()->send();
            });
    }

    public static function resolveAction(): Action
    {
        return Action::make('workEscalationResolve')
            ->label('Resolve')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->visible(fn (Model $record): bool => self::waitingOnMe($record)?->mode === Escalation::MODE_HELP)
            ->modalDescription(fn (Model $record): string => self::summary(self::waitingOnMe($record)))
            ->form([
                Textarea::make('note')->label('What did you do?')->required()->rows(3)->maxLength(2000),
            ])
            ->action(function (Model $record, array $data, Action $action): void {
                self::attempt($action, fn () => app(EscalationService::class)->resolve(self::waitingOnMe($record), auth()->user(), $data['note']));

                Notification::make()->title('Resolved and handed back')->success()->send();
            });
    }

    public static function declineAction(): Action
    {
        return Action::make('workEscalationDecline')
            ->label('Decline')
            ->icon('heroicon-o-x-mark')
            ->color('danger')
            ->visible(fn (Model $record): bool => self::waitingOnMe($record) !== null)
            ->modalDescription(fn (Model $record): string => self::summary(self::waitingOnMe($record)))
            ->form([
                Textarea::make('note')->label('Why?')->helperText('They will see this, and can escalate to someone else.')->required()->rows(3)->maxLength(2000),
            ])
            ->action(function (Model $record, array $data, Action $action): void {
                self::attempt($action, fn () => app(EscalationService::class)->decline(self::waitingOnMe($record), auth()->user(), $data['note']));

                Notification::make()->title('Declined')->success()->send();
            });
    }

    public static function withdrawAction(): Action
    {
        return Action::make('workEscalationWithdraw')
            ->label('Withdraw my escalation')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('gray')
            ->visible(fn (Model $record): bool => self::raisedByMe($record) !== null)
            ->requiresConfirmation()
            ->modalDescription(fn (Model $record): string => self::summary(self::raisedByMe($record)))
            ->action(function (Model $record, Action $action): void {
                self::attempt($action, fn () => app(EscalationService::class)->withdraw(self::raisedByMe($record), auth()->user()));

                Notification::make()->title('Withdrawn')->success()->send();
            });
    }

    /** The newest active escalation on this booking that the signed-in person may answer. */
    public static function waitingOnMe(Model $record): ?Escalation
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        return self::active($record)->first(fn (Escalation $escalation) => $escalation->canBeRespondedToBy($user)
            && $escalation->raised_by !== $user->getKey());
    }

    public static function raisedByMe(Model $record): ?Escalation
    {
        $user = auth()->user();

        return self::active($record)->first(fn (Escalation $escalation) => $escalation->raised_by === $user?->getKey());
    }

    private static function active(Model $record)
    {
        $item = $record->workItem()->first();

        return $item
            ? $item->escalations()->active()->with(['raiser', 'toUser', 'toDepartment'])->get()
            : collect();
    }

    private static function goesToIt(mixed $departmentId): bool
    {
        return filled($departmentId) && Department::query()->whereKey($departmentId)->value('linear_team') === 'it';
    }

    private static function summary(?Escalation $escalation): string
    {
        if (! $escalation) {
            return '';
        }

        return ($escalation->raiser?->name ?? 'Someone').' asked '.$escalation->targetLabel().': "'.$escalation->reason.'"';
    }

    /** Runs a service call, turning a refusal into a message on the form instead of an error page. */
    private static function attempt(Action $action, Closure $call): mixed
    {
        try {
            return $call();
        } catch (InvalidArgumentException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
            $action->halt();
        }

        return null;
    }
}
