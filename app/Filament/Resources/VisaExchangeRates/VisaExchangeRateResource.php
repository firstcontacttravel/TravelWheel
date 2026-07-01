<?php

namespace App\Filament\Resources\VisaExchangeRates;

use App\Filament\Resources\VisaExchangeRates\Pages\CreateVisaExchangeRate;
use App\Filament\Resources\VisaExchangeRates\Pages\EditVisaExchangeRate;
use App\Filament\Resources\VisaExchangeRates\Pages\ListVisaExchangeRates;
use App\Models\VisaExchangeRate;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VisaExchangeRateResource extends Resource
{
    protected static ?string $model = VisaExchangeRate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static string|\UnitEnum|null $navigationGroup = 'Visa Catalogue';

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([Section::make('Checkout conversion rate')->schema([
            TextInput::make('source_currency')->required()->length(3)->formatStateUsing(fn ($state) => strtoupper((string) $state)),
            Select::make('target_currency')->options(['NGN' => 'NGN'])->default('NGN')->required(),
            TextInput::make('rate')->numeric()->minValue(0.000001)->required(),
            TextInput::make('source')->default('manual')->required(),
            DateTimePicker::make('effective_from')->default(now())->required(),
            DateTimePicker::make('effective_until')->after('effective_from'),
            Toggle::make('is_active')->default(true),
        ])->columns(2)]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('effective_from', 'desc')->columns([
            TextColumn::make('source_currency')->badge(), TextColumn::make('target_currency')->badge(),
            TextColumn::make('rate')->numeric(decimalPlaces: 6), TextColumn::make('source'),
            TextColumn::make('effective_from')->dateTime()->sortable(), TextColumn::make('effective_until')->dateTime(),
            IconColumn::make('is_active')->boolean(),
        ])->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListVisaExchangeRates::route('/'), 'create' => CreateVisaExchangeRate::route('/create'), 'edit' => EditVisaExchangeRate::route('/{record}/edit')];
    }
}
