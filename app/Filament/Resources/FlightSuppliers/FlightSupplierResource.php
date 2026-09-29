<?php

namespace App\Filament\Resources\FlightSuppliers;

use App\Filament\Resources\FlightSuppliers\Pages\ListFlightSuppliers;
use App\Models\FlightSupplierEvent;
use App\Models\FlightSupplierSetting;
use App\Services\Flights\FlightSupplierBreaker;
use App\Services\Flights\FlightSupplierControl;
use App\Services\Flights\FlightSupplierRegistry;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Flight APIs: switch each supplier on or off, and set the order searches
 * try them in. Every change goes through FlightSupplierControl, which records
 * it in the API's history.
 */
class FlightSupplierResource extends Resource
{
    protected static ?string $model = FlightSupplierSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Flights';

    protected static ?int $navigationSort = 45;

    protected static ?string $navigationLabel = 'Flight APIs';

    protected static ?string $modelLabel = 'flight API';

    protected static ?string $pluralModelLabel = 'Flight APIs';

    private const TIMEZONE = 'Africa/Lagos';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('key', app(FlightSupplierRegistry::class)->keys())
            ->with('disabledBy');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('priority')
            ->paginated(false)
            ->description('Searches try switched-on APIs from the top down. The first one that finds flights fills the results page; the others add theirs while the customer browses.')
            ->columns([
                TextColumn::make('priority')
                    ->label('Order')
                    ->formatStateUsing(fn (int $state): string => '#'.$state),
                TextColumn::make('key')
                    ->label('API')
                    ->formatStateUsing(fn (string $state): string => self::label($state))
                    ->description(fn (FlightSupplierSetting $record): string => $record->key)
                    ->weight('semibold'),
                TextColumn::make('status')
                    ->label('Status')
                    ->state(fn (FlightSupplierSetting $record): string => self::status($record))
                    ->badge()
                    ->color(fn (FlightSupplierSetting $record): string => match (true) {
                        ! $record->isAvailable() => $record->re_enable_at ? 'warning' : 'danger',
                        $record->isAutoPaused() => 'danger',
                        $record->isOnTrial() => 'warning',
                        default => 'success',
                    })
                    ->description(fn (FlightSupplierSetting $record): ?string => $record->isAvailable() && $record->breaker_state === FlightSupplierSetting::BREAKER_OPEN
                        ? $record->breaker_reason
                        : null)
                    ->wrap(),
                TextColumn::make('recent')
                    ->label('Recent searches')
                    ->state(function (FlightSupplierSetting $record): string {
                        $stats = app(FlightSupplierBreaker::class)->recentStats($record);
                        $window = $record->cutoffThresholds()['window_minutes'];

                        return $stats['calls'] === 0
                            ? "None in {$window} min"
                            : "{$stats['failed']} of {$stats['calls']} failed · {$window} min";
                    })
                    ->description(fn (FlightSupplierSetting $record): string => $record->auto_cutoff
                        ? 'Automatic cut-off on'
                        : 'Automatic cut-off off'),
                TextColumn::make('disabled_reason')
                    ->label('Reason')
                    ->wrap()
                    ->placeholder('—'),
                TextColumn::make('disabledBy.name')
                    ->label('Switched off by')
                    ->placeholder('—'),
                TextColumn::make('disabled_at')
                    ->label('Off since')
                    ->dateTime('d M Y, H:i', self::TIMEZONE)
                    ->placeholder('—'),
            ])
            ->recordActions([
                self::switchOffAction(),
                self::switchOnAction(),
                self::resumeNowAction(),
                self::cutoffSettingsAction(),
                self::moveAction('moveUp', -1),
                self::moveAction('moveDown', 1),
                self::historyAction(),
            ]);
    }

    private static function switchOffAction(): Action
    {
        return Action::make('switchOff')
            ->label('Switch off')
            ->icon(Heroicon::OutlinedPower)
            ->color('danger')
            ->visible(fn (FlightSupplierSetting $record): bool => $record->isAvailable())
            ->modalHeading(fn (FlightSupplierSetting $record): string => 'Switch off '.self::label($record->key).'?')
            ->modalDescription(fn (FlightSupplierSetting $record): string => self::isOnlyOneOn($record)
                ? 'This is the only API switched on. Customers will not be able to search for flights until an API is switched back on.'
                : 'New searches stop using it straight away. Bookings already made with it are not affected.')
            ->modalSubmitActionLabel('Switch off')
            ->form([
                Textarea::make('reason')
                    ->label('Reason')
                    ->helperText('Any reason — failing, funding, maintenance. Shown to other admins and kept in the history.')
                    ->required()
                    ->maxLength(1000)
                    ->rows(3),
                DateTimePicker::make('re_enable_at')
                    ->label('Switch back on automatically at')
                    ->helperText('Optional. Leave empty to keep it off until someone switches it on.')
                    ->timezone(self::TIMEZONE)
                    ->seconds(false)
                    ->minDate(now()),
            ])
            ->action(function (FlightSupplierSetting $record, array $data): void {
                try {
                    app(FlightSupplierControl::class)->disable(
                        $record->key,
                        (string) $data['reason'],
                        auth()->user(),
                        filled($data['re_enable_at'] ?? null) ? Carbon::parse($data['re_enable_at']) : null,
                    );
                } catch (InvalidArgumentException $exception) {
                    Notification::make()->title('Not switched off')->body($exception->getMessage())->danger()->send();

                    return;
                }

                Notification::make()->title(self::label($record->key).' switched off')->success()->send();
            });
    }

    private static function switchOnAction(): Action
    {
        return Action::make('switchOn')
            ->label('Switch on')
            ->icon(Heroicon::OutlinedPower)
            ->color('success')
            ->visible(fn (FlightSupplierSetting $record): bool => ! $record->isAvailable())
            ->requiresConfirmation()
            ->modalHeading(fn (FlightSupplierSetting $record): string => 'Switch on '.self::label($record->key).'?')
            ->modalDescription('New searches start using it straight away.')
            ->modalSubmitActionLabel('Switch on')
            ->action(function (FlightSupplierSetting $record): void {
                app(FlightSupplierControl::class)->enable($record->key, auth()->user());

                Notification::make()->title(self::label($record->key).' switched on')->success()->send();
            });
    }

    private static function resumeNowAction(): Action
    {
        return Action::make('resumeNow')
            ->label('Resume now')
            ->icon(Heroicon::OutlinedPlay)
            ->color('warning')
            ->visible(fn (FlightSupplierSetting $record): bool => $record->isAvailable()
                && $record->breaker_state === FlightSupplierSetting::BREAKER_OPEN)
            ->requiresConfirmation()
            ->modalHeading(fn (FlightSupplierSetting $record): string => 'Resume '.self::label($record->key).' now?')
            ->modalDescription('Ends the automatic pause straight away. If searches keep failing it will be paused again.')
            ->modalSubmitActionLabel('Resume now')
            ->action(function (FlightSupplierSetting $record): void {
                app(FlightSupplierControl::class)->resumeNow($record->key, auth()->user());

                Notification::make()->title(self::label($record->key).' resumed')->success()->send();
            });
    }

    private static function cutoffSettingsAction(): Action
    {
        $defaults = (array) config('flights.cutoff', []);

        return Action::make('cutoffSettings')
            ->label('Automatic cut-off')
            ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
            ->color('gray')
            ->modalHeading(fn (FlightSupplierSetting $record): string => self::label($record->key).' — automatic cut-off')
            ->modalDescription('Pauses this API on its own when too many of its searches and price checks fail, and brings it back once they succeed. Errors and timeouts count as failures; finding no flights does not. Leave a number empty to use the default shown.')
            ->modalSubmitActionLabel('Save')
            ->fillForm(fn (FlightSupplierSetting $record): array => [
                'auto_cutoff' => $record->auto_cutoff,
                'min_calls' => $record->cutoff_min_calls,
                'failure_percent' => $record->cutoff_failure_percent,
                'window_minutes' => $record->cutoff_window_minutes,
                'pause_minutes' => $record->cutoff_pause_minutes,
            ])
            ->form([
                Toggle::make('auto_cutoff')
                    ->label('Pause automatically when failing')
                    ->helperText('Off: only an admin can take this API out of searches.')
                    ->live(),
                TextInput::make('failure_percent')
                    ->label('Pause when this share fail')
                    ->numeric()->integer()->minValue(1)->maxValue(100)
                    ->suffix('%')
                    ->placeholder((string) ($defaults['failure_percent'] ?? 50))
                    ->visible(fn ($get): bool => (bool) $get('auto_cutoff')),
                TextInput::make('min_calls')
                    ->label('…of at least this many calls')
                    ->numeric()->integer()->minValue(1)->maxValue(1000)
                    ->placeholder((string) ($defaults['min_calls'] ?? 10))
                    ->visible(fn ($get): bool => (bool) $get('auto_cutoff')),
                TextInput::make('window_minutes')
                    ->label('…within')
                    ->numeric()->integer()->minValue(1)->maxValue(240)
                    ->suffix('minutes')
                    ->placeholder((string) ($defaults['window_minutes'] ?? 5))
                    ->visible(fn ($get): bool => (bool) $get('auto_cutoff')),
                TextInput::make('pause_minutes')
                    ->label('Pause for')
                    ->numeric()->integer()->minValue(1)->maxValue(1440)
                    ->suffix('minutes')
                    ->helperText('Then it is tried again; '.($defaults['trial_successes'] ?? 3).' successes in a row resume it.')
                    ->placeholder((string) ($defaults['pause_minutes'] ?? 10))
                    ->visible(fn ($get): bool => (bool) $get('auto_cutoff')),
            ])
            ->action(function (FlightSupplierSetting $record, array $data): void {
                app(FlightSupplierControl::class)->updateCutoff($record->key, $data, auth()->user());

                Notification::make()->title('Automatic cut-off saved')->success()->send();
            });
    }

    private static function moveAction(string $name, int $direction): Action
    {
        return Action::make($name)
            ->label($direction < 0 ? 'Move up' : 'Move down')
            ->icon($direction < 0 ? Heroicon::OutlinedArrowUp : Heroicon::OutlinedArrowDown)
            ->color('gray')
            ->iconButton()
            ->tooltip($direction < 0 ? 'Try earlier' : 'Try later')
            ->hidden(function (FlightSupplierSetting $record) use ($direction): bool {
                $keys = app(FlightSupplierControl::class)->settings()->pluck('key')->values();
                $position = $keys->search($record->key);

                return $direction < 0 ? $position === 0 : $position === $keys->count() - 1;
            })
            ->action(fn (FlightSupplierSetting $record) => app(FlightSupplierControl::class)->move($record->key, $direction, auth()->user()));
    }

    private static function historyAction(): Action
    {
        return Action::make('history')
            ->label('History')
            ->icon(Heroicon::OutlinedClock)
            ->color('gray')
            ->modalHeading(fn (FlightSupplierSetting $record): string => self::label($record->key).' history')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->modalWidth('lg')
            ->modalContent(fn (FlightSupplierSetting $record) => view('filament.booking.feed', [
                'items' => FlightSupplierEvent::query()
                    ->where('supplier_key', $record->key)
                    ->with('user')
                    ->latest('created_at')
                    ->latest('id')
                    ->limit(50)
                    ->get()
                    ->map(fn (FlightSupplierEvent $event): array => self::historyItem($event))
                    ->all(),
                'empty' => 'No changes recorded yet.',
            ]));
    }

    /** @return array{title:string, when:string, body:string|null, tone:string} */
    private static function historyItem(FlightSupplierEvent $event): array
    {
        $who = $event->user?->name ?? 'System';
        $details = $event->details ?? [];

        [$title, $tone, $body] = match ($event->action) {
            'disabled' => [
                "Switched off by {$who}",
                'critical',
                trim($event->reason.(filled($details['re_enable_at'] ?? null)
                    ? ' — set to switch back on '.self::lagos($details['re_enable_at'])
                    : '')),
            ],
            'enabled' => ["Switched on by {$who}", 'positive', null],
            're_enabled_on_schedule' => ['Switched back on as scheduled', 'positive', filled($details['was_off_for'] ?? null) ? 'Had been off for: '.$details['was_off_for'] : null],
            'moved' => ["Moved from #{$details['from']} to #{$details['to']} by {$who}", 'info', null],
            'registered' => ['Added', 'idle', $event->reason],
            'auto_paused' => ['Paused automatically', 'critical', $event->reason],
            'auto_paused_again' => ['Paused again — still failing', 'critical', $event->reason],
            'auto_resumed' => ['Resumed automatically — searches working', 'positive', null],
            'cutoff_cleared' => ["Pause ended by {$who}", 'positive', null],
            'cutoff_settings_changed' => ["Automatic cut-off changed by {$who}", 'info', self::cutoffSummary($details['after'] ?? [])],
            default => [str($event->action)->headline()->toString(), 'idle', $event->reason],
        };

        return [
            'title' => $title,
            'when' => $event->created_at?->timezone(self::TIMEZONE)->format('d M Y, H:i') ?? '',
            'body' => $body,
            'tone' => $tone,
        ];
    }

    private static function cutoffSummary(array $after): string
    {
        if (! ($after['auto_cutoff'] ?? true)) {
            return 'Turned off.';
        }

        $limits = (new FlightSupplierSetting([
            'cutoff_min_calls' => $after['cutoff_min_calls'] ?? null,
            'cutoff_failure_percent' => $after['cutoff_failure_percent'] ?? null,
            'cutoff_window_minutes' => $after['cutoff_window_minutes'] ?? null,
            'cutoff_pause_minutes' => $after['cutoff_pause_minutes'] ?? null,
        ]))->cutoffThresholds();

        return "Pause when {$limits['failure_percent']}% of at least {$limits['min_calls']} calls fail within {$limits['window_minutes']} min; pause for {$limits['pause_minutes']} min.";
    }

    private static function status(FlightSupplierSetting $record): string
    {
        // The manual switch comes first: an API switched off by hand is off,
        // whatever the automatic cut-off thinks.
        if ($record->isAvailable() && $record->isAutoPaused()) {
            return 'Paused automatically · retrying '.$record->breaker_retry_at->timezone(self::TIMEZONE)->format('H:i');
        }

        if ($record->isAvailable() && $record->isOnTrial()) {
            return 'On trial after a pause';
        }

        if ($record->enabled) {
            return 'On';
        }

        if ($record->isAvailable()) {
            return 'On (scheduled)';
        }

        return $record->re_enable_at
            ? 'Off until '.$record->re_enable_at->timezone(self::TIMEZONE)->format('d M, H:i')
            : 'Off';
    }

    private static function isOnlyOneOn(FlightSupplierSetting $record): bool
    {
        return app(FlightSupplierControl::class)->enabledKeys() === [$record->key];
    }

    private static function label(string $key): string
    {
        $registry = app(FlightSupplierRegistry::class);

        return $registry->has($key) ? $registry->get($key)->label() : $key;
    }

    private static function lagos(string $iso): string
    {
        return Carbon::parse($iso)->timezone(self::TIMEZONE)->format('d M Y, H:i');
    }

    public static function getPages(): array
    {
        return ['index' => ListFlightSuppliers::route('/')];
    }

    /**
     * Full administrators only, like System Health: these switches decide
     * whether customers can search for flights at all.
     */
    public static function canViewAny(): bool
    {
        return auth()->user()?->isVisaAdministrator() ?? false;
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
