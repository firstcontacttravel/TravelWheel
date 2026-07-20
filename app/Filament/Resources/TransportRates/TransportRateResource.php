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
            TextInput::make('price_regular')->numeric()->required()->prefix('₦'),
            TextInput::make('price_standard')->numeric()->required()->prefix('₦'),
            TextInput::make('price_executive')->numeric()->required()->prefix('₦'),
            TextInput::make('fuel_rate_per_km')->numeric()->required()->prefix('₦')->helperText('Per-km fuel surcharge added to car hire pricing.'),
            TextInput::make('hourly_rate')->numeric()->required()->prefix('₦')->helperText('Per-hour rate added to car hire pricing.'),
            TextInput::make('transfer_rate_per_km')->numeric()->required()->prefix('₦')->helperText('Per-km rate used for Transfer bookings.'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('vehicle_type')
            ->columns([
                TextColumn::make('vehicle_type')->badge()->sortable(),
                TextColumn::make('price_regular')->money('NGN')->label('Regular'),
                TextColumn::make('price_standard')->money('NGN')->label('Standard'),
                TextColumn::make('price_executive')->money('NGN')->label('Executive'),
                TextColumn::make('fuel_rate_per_km')->money('NGN')->label('Fuel /km'),
                TextColumn::make('hourly_rate')->money('NGN')->label('Hourly'),
                TextColumn::make('transfer_rate_per_km')->money('NGN')->label('Transfer /km'),
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
