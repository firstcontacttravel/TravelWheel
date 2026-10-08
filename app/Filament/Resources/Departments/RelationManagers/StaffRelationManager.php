<?php

namespace App\Filament\Resources\Departments\RelationManagers;

use App\Filament\Resources\Staff\StaffResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * The people in a department, and the quickest way to add someone to it.
 * Moving someone between departments is done on their staff page.
 */
class StaffRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $title = 'Staff';

    protected static ?string $modelLabel = 'staff member';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('email')->email()->required()->maxLength(255)->unique(User::class, 'email'),
            TextInput::make('password')->password()->revealable()->required()->minLength(8)->maxLength(255),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->description(fn (User $record): string => $record->email),
                TextColumn::make('head')
                    ->label('Role')
                    ->state(fn (User $record): string => $record->is_department_head ? 'Head' : 'Staff')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Head' ? 'info' : 'gray'),
                TextColumn::make('status')
                    ->state(fn (User $record): string => $record->isDeactivated() ? 'Deactivated' : 'Active')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Active' ? 'success' : 'danger'),
            ])
            ->headerActions([
                CreateAction::make()->label('Add staff'),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (User $record): string => StaffResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
