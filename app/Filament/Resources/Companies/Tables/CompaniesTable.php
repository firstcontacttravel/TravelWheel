<?php

namespace App\Filament\Resources\Companies\Tables;

use App\Models\Company;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Company')->searchable()->weight('bold'),
                TextColumn::make('cac_number')->label('CAC number')->searchable()->copyable(),
                TextColumn::make('contact_name')->label('Contact')->searchable(['contact_name', 'contact_email'])
                    ->description(fn (Company $record): string => $record->contact_email),
                TextColumn::make('status')->badge()
                    ->formatStateUsing(fn (string $state): string => Company::STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => Company::STATUS_COLORS[$state] ?? 'gray'),
                TextColumn::make('users_count')->label('Users')->counts('users'),
                TextColumn::make('created_at')->label('Added')->dateTime('j M Y', 'Africa/Lagos')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(Company::STATUSES),
            ])
            ->recordActions([ViewAction::make()]);
    }
}
