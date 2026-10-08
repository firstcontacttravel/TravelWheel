<?php

namespace App\Filament\Resources\Staff;

use App\Filament\Resources\Staff\Pages\CreateStaff;
use App\Filament\Resources\Staff\Pages\EditStaff;
use App\Filament\Resources\Staff\Pages\ListStaff;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Admin accounts. Only the CEO manages them. Everyone else is staff in one
 * department. Nobody is ever deleted, because their name is on payments,
 * tickets and decisions; they are deactivated instead.
 */
class StaffResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = 'Team';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Staff';

    protected static ?string $modelLabel = 'staff member';

    protected static ?string $pluralModelLabel = 'Staff';

    protected static ?string $slug = 'staff';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->email()
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            Select::make('department_id')
                ->label('Department')
                ->relationship('department', 'name')
                ->preload()
                ->required(fn (?User $record): bool => ! $record?->isAdmin())
                ->live()
                ->helperText('The team they belong to. Staff claim bookings and work the ones they own.'),
            Toggle::make('is_department_head')
                ->label('Head of department')
                ->helperText('Heads escalate and reassign bookings, take work over, set priority, and answer escalations sent to their department. A department can have more than one.')
                ->visible(fn (Get $get): bool => filled($get('department_id'))),
            TextInput::make('password')
                ->password()
                ->revealable()
                ->minLength(8)
                ->maxLength(255)
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->helperText(fn (string $operation): ?string => $operation === 'edit' ? 'Leave empty to keep the current password.' : null),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->description(fn (User $record): string => $record->email)
                    ->searchable(['name', 'email'])
                    ->sortable(),
                TextColumn::make('department.name')
                    ->label('Department')
                    ->state(fn (User $record): ?string => $record->department?->name ?? ($record->isAdmin() ? 'All departments' : null))
                    ->badge()
                    ->color('gray')
                    ->placeholder('None'),
                TextColumn::make('role')
                    ->label('Role')
                    ->state(fn (User $record): string => match (true) {
                        $record->isAdmin() => 'Admin',
                        $record->isDepartmentHead() => 'Head',
                        default => 'Staff',
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Admin' => 'primary',
                        'Head' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('status')
                    ->state(fn (User $record): string => $record->isDeactivated() ? 'Deactivated' : 'Active')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Active' ? 'success' : 'danger'),
                TextColumn::make('created_at')
                    ->label('Added')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('department_id')
                    ->label('Department')
                    ->relationship('department', 'name'),
                TernaryFilter::make('active')
                    ->label('Active')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNull('deactivated_at'),
                        false: fn (Builder $query): Builder => $query->whereNotNull('deactivated_at'),
                    ),
            ])
            ->recordActions([
                EditAction::make(),
                self::deactivateAction(),
                self::reactivateAction(),
            ]);
    }

    public static function deactivateAction(): Action
    {
        return Action::make('deactivate')
            ->label('Deactivate')
            ->icon('heroicon-o-no-symbol')
            ->color('danger')
            ->visible(fn (User $record): bool => ! $record->isDeactivated() && ! $record->isAdmin() && ! $record->is(auth()->user()))
            ->requiresConfirmation()
            ->modalDescription('They will be signed out of the admin and unable to sign back in. Everything they did stays on record under their name.')
            ->action(function (User $record): void {
                $record->update(['deactivated_at' => now()]);

                Notification::make()->title("{$record->name} deactivated")->success()->send();
            });
    }

    public static function reactivateAction(): Action
    {
        return Action::make('reactivate')
            ->label('Reactivate')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('gray')
            ->visible(fn (User $record): bool => $record->isDeactivated())
            ->requiresConfirmation()
            ->action(function (User $record): void {
                $record->update(['deactivated_at' => null]);

                Notification::make()->title("{$record->name} reactivated")->success()->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaff::route('/'),
            'create' => CreateStaff::route('/create'),
            'edit' => EditStaff::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
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
