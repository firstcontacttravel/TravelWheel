<?php

namespace App\Filament\Resources\VisaProducts\Tables;

use App\Enums\VisaProductFamily;
use App\Enums\VisaPublicationStatus;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class VisaProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->weight('bold')->description(fn ($record) => $record->slug),
                TextColumn::make('destinationCountry.name')->label('Destination')->searchable()->sortable(),
                TextColumn::make('family')->badge()->formatStateUsing(fn ($state) => VisaProductFamily::options()[$state instanceof \BackedEnum ? $state->value : $state] ?? (string) $state),
                TextColumn::make('category')->badge()->searchable(),
                TextColumn::make('publication_status')->label('Status')->badge()->color(fn ($state) => match ($state instanceof \BackedEnum ? $state->value : $state) {
                    'published' => 'success', 'archived' => 'gray', default => 'warning'
                })->formatStateUsing(fn ($state) => str($state instanceof \BackedEnum ? $state->value : $state)->headline()),
                TextColumn::make('version')->badge()->sortable(),
                TextColumn::make('updated_at')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('publication_status')->options(VisaPublicationStatus::options()),
                SelectFilter::make('family')->options(VisaProductFamily::options()),
                SelectFilter::make('destination_country_id')->label('Destination')->relationship('destinationCountry', 'name')->searchable()->preload(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
