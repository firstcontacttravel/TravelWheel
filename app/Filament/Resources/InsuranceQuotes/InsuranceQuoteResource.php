<?php

namespace App\Filament\Resources\InsuranceQuotes;

use App\Filament\Resources\InsuranceQuotes\Pages\ListInsuranceQuotes;
use App\Filament\Resources\InsuranceQuotes\Pages\ViewInsuranceQuote;
use App\Models\InsuranceQuote;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class InsuranceQuoteResource extends Resource
{
    protected static ?string $model = InsuranceQuote::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentMagnifyingGlass;

    protected static string|\UnitEnum|null $navigationGroup = 'Insurance';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Quotes';

    protected static ?string $recordTitleAttribute = 'quoteId';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('quoteId')->label('Reference')->searchable()->copyable(),
                TextColumn::make('email')->searchable()->description(fn (InsuranceQuote $record): string => (string) $record->phone_no),
                TextColumn::make('purposeOfTravel')->label('Purpose'),
                TextColumn::make('coverBegins')->label('Cover Period')->description(fn (InsuranceQuote $record): string => (string) $record->coverEnds),
                TextColumn::make('noOfPeople')
                    ->label('Travelers')
                    ->formatStateUsing(fn (InsuranceQuote $record): string => "{$record->noOfPeople} adult(s), {$record->noOfChildren} child(ren)"),
                TextColumn::make('amount')->money('NGN'),
                TextColumn::make('created_at')->since()->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInsuranceQuotes::route('/'),
            'view' => ViewInsuranceQuote::route('/{record}'),
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
