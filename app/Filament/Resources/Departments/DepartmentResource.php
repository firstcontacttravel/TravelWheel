<?php

namespace App\Filament\Resources\Departments;

use App\Filament\Resources\Departments\Pages\CreateDepartment;
use App\Filament\Resources\Departments\Pages\EditDepartment;
use App\Filament\Resources\Departments\Pages\ListDepartments;
use App\Filament\Resources\Departments\RelationManagers\StaffRelationManager;
use App\Models\Department;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static string|\UnitEnum|null $navigationGroup = 'Team';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Departments';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(100)
                ->live(onBlur: true)
                ->afterStateUpdated(function (string $operation, ?string $state, Set $set): void {
                    if ($operation === 'create') {
                        $set('slug', Str::slug((string) $state));
                    }
                }),
            TextInput::make('slug')
                ->required()
                ->maxLength(100)
                ->alphaDash()
                ->unique(ignoreRecord: true)
                // Code refers to departments by slug (Finance gates refunds),
                // so it is fixed once the department exists.
                ->disabledOn('edit')
                ->helperText('Used internally. Cannot be changed later.'),
            Textarea::make('description')
                ->label('What this department handles')
                ->rows(2)
                ->maxLength(500)
                ->columnSpanFull(),
            Select::make('linear_team')
                ->label('Linear team')
                ->options(Department::LINEAR_TEAMS)
                ->default('travelwheel')
                ->required()
                ->helperText('Escalations to this department become issues in this Linear team.'),
            TextInput::make('linear_label')
                ->label('Linear label')
                ->maxLength(100)
                ->helperText('Tells departments apart inside the shared Travelwheel team.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->paginated(false)
            ->columns([
                TextColumn::make('name')
                    ->weight('semibold')
                    ->description(fn (Department $record): ?string => $record->description)
                    ->wrap(),
                TextColumn::make('users_count')
                    ->label('Staff')
                    ->counts('users')
                    ->alignEnd(),
                TextColumn::make('linear_team')
                    ->label('Linear')
                    ->formatStateUsing(fn (string $state): string => Department::LINEAR_TEAMS[$state] ?? $state)
                    ->description(fn (Department $record): ?string => $record->linear_label)
                    ->badge()
                    ->color('gray'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
            StaffRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDepartments::route('/'),
            'create' => CreateDepartment::route('/create'),
            'edit' => EditDepartment::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    /** A department with people in it cannot disappear from under them. */
    public static function canDelete(Model $record): bool
    {
        return static::canViewAny() && $record->users()->doesntExist();
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
