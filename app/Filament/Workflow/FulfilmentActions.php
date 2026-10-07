<?php

namespace App\Filament\Workflow;

use App\Workflow\FulfilmentService;
use App\Workflow\ServiceWorkflow;
use App\Workflow\WorkflowRegistry;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * The Progress menu on a service booking: move to the next step, cancel with
 * a reason, and (Finance only) mark a payment received. Used on both the
 * list rows and the booking's page.
 */
class FulfilmentActions
{
    public static function group(): ActionGroup
    {
        return ActionGroup::make([
            self::advanceAction(),
            self::markPaidAction(),
            self::cancelAction(),
        ])
            ->label('Progress')
            ->icon('heroicon-o-forward')
            ->color('primary')
            ->button();
    }

    /** The same three actions for a table row: a compact menu, not a button. */
    public static function rowGroup(): ActionGroup
    {
        return ActionGroup::make([
            self::advanceAction(),
            self::markPaidAction(),
            self::cancelAction(),
        ])
            ->label('Progress')
            ->icon('heroicon-o-forward')
            ->color('gray');
    }

    public static function advanceAction(): Action
    {
        return Action::make('workAdvance')
            ->label('Move to next step')
            ->icon('heroicon-o-forward')
            ->color('primary')
            ->visible(fn (Model $record): bool => self::workflow($record)->nextSteps($record) !== [])
            ->modalDescription(fn (Model $record): string => 'Now: '.self::currentLabel($record))
            ->modalWidth('md')
            ->form(fn (Model $record): array => [
                Select::make('step')
                    ->label('Move to')
                    ->options(self::workflow($record)->nextSteps($record))
                    ->default(array_key_first(self::workflow($record)->nextSteps($record)))
                    ->required(),
                Textarea::make('note')
                    ->label('Note')
                    ->helperText('Optional. Added to the booking\'s history.')
                    ->rows(3)
                    ->maxLength(2000),
            ])
            ->action(function (Model $record, array $data, Action $action): void {
                self::attempt($action, fn () => app(FulfilmentService::class)->advance($record, $data['step'], auth()->user(), $data['note'] ?? null));

                Notification::make()->title('Moved to '.self::currentLabel($record->fresh()))->success()->send();
            });
    }

    public static function cancelAction(): Action
    {
        return Action::make('workCancel')
            ->label('Cancel booking')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn (Model $record): bool => $record->getAttribute('fulfilment_status') !== 'cancelled')
            ->modalDescription('This records the booking as cancelled in the admin. It does not refund the customer; arrange that with Finance.')
            ->form([
                Textarea::make('reason')->label('Why?')->required()->rows(3)->maxLength(2000),
            ])
            ->action(function (Model $record, array $data, Action $action): void {
                self::attempt($action, fn () => app(FulfilmentService::class)->cancel($record, auth()->user(), $data['reason']));

                Notification::make()->title('Cancelled')->success()->send();
            });
    }

    public static function markPaidAction(): Action
    {
        return Action::make('workMarkPaid')
            ->label('Mark payment received')
            ->icon('heroicon-o-banknotes')
            ->color('success')
            ->authorize(fn (): bool => auth()->user()?->canHandleMoney() ?? false)
            ->visible(fn (Model $record): bool => ! self::workflow($record)->isPaid($record)
                && $record->getAttribute('fulfilment_status') !== 'cancelled')
            ->modalDescription('Only after the money has reached the company account. Card and online payments are recorded automatically.')
            ->form([
                Textarea::make('note')->label('Payment details')->helperText('Transfer reference, amount, date.')->required()->rows(3)->maxLength(2000),
            ])
            ->action(function (Model $record, array $data, Action $action): void {
                self::attempt($action, fn () => app(FulfilmentService::class)->markPaid($record, auth()->user(), $data['note']));

                Notification::make()->title('Payment recorded')->success()->send();
            });
    }

    private static function workflow(Model $record): ServiceWorkflow
    {
        return app(WorkflowRegistry::class)->forSubject($record);
    }

    private static function currentLabel(Model $record): string
    {
        $workflow = self::workflow($record);

        return $workflow->stages()[$workflow->stageFor($record)]['label'] ?? '';
    }

    private static function attempt(Action $action, Closure $call): void
    {
        try {
            $call();
        } catch (InvalidArgumentException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
            $action->halt();
        }
    }
}
