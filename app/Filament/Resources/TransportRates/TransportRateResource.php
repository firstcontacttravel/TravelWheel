<?php

namespace App\Filament\Resources\TransportRates;

use App\Filament\Resources\TransportRates\Pages\EditTransportRate;
use App\Filament\Resources\TransportRates\Pages\ListTransportRates;
use App\Models\TransportRate;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TransportRateResource extends Resource
{
    protected static ?string $model = TransportRate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static string|\UnitEnum|null $navigationGroup = 'Car Hire & Transfer';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Rates';

    protected static ?string $recordTitleAttribute = 'vehicle_type';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('vehicle_type')->disabled(),

            TextInput::make('transfer_base_regular')->numeric()->required()->prefix('₦')->label('Base Fare — Regular')->helperText('Shared by Car Hire and Transfer.'),
            TextInput::make('transfer_base_standard')->numeric()->required()->prefix('₦')->label('Base Fare — Standard')->helperText('Shared by Car Hire and Transfer.'),
            TextInput::make('transfer_base_executive')->numeric()->required()->prefix('₦')->label('Base Fare — Executive')->helperText('Shared by Car Hire and Transfer.'),
            TextInput::make('transfer_fuel_rate_per_minute')->numeric()->required()->prefix('₦')->label('Fuel Rate (per minute)')->helperText('Drive time for Transfer, rental duration for Car Hire.'),
            TextInput::make('transfer_admin_fee_percent')->numeric()->step(0.1)->minValue(0)->maxValue(100)->required()->suffix('%')->label('Admin Fee')->helperText('Applied to (Base Fare + Tear & Wear + Fuel) for both products.'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('vehicle_type')
            ->columns([
                TextColumn::make('vehicle_type')->badge()->sortable(),
                TextColumn::make('transfer_base_regular')->money('NGN')->label('Regular'),
                TextColumn::make('transfer_base_standard')->money('NGN')->label('Standard'),
                TextColumn::make('transfer_base_executive')->money('NGN')->label('Executive'),
                TextColumn::make('transfer_fuel_rate_per_minute')->money('NGN')->label('Fuel /min'),
                TextColumn::make('transfer_admin_fee_percent')->suffix('%')->label('Admin Fee'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransportRates::route('/'),
            'edit' => EditTransportRate::route('/{record}/edit'),
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
