<?php

namespace App\Filament\Resources\ExchangeRates;

use App\Filament\Resources\ExchangeRates\Pages\EditExchangeRate;
use App\Filament\Resources\ExchangeRates\Pages\ListExchangeRates;
use App\Models\ExchangeRate;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ExchangeRateResource extends Resource
{
    protected static ?string $model = ExchangeRate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 50;

    protected static ?string $navigationLabel = 'Exchange Rates';

    protected static ?string $recordTitleAttribute = 'currency';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('currency')->disabled(),
            TextInput::make('rate')
                ->numeric()
                ->required()
                ->helperText('NGN per 1 unit of this currency. Used to convert flight fares from the airline API (USD) to Naira.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('currency')
            ->columns([
                TextColumn::make('currency')->badge(),
                TextColumn::make('rate')->numeric(decimalPlaces: 2),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExchangeRates::route('/'),
            'edit' => EditExchangeRate::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
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
