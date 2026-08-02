<?php

namespace App\Filament\Resources\TransportRates;

use App\Filament\Resources\TransportRates\Pages\EditTransportRate;
use App\Filament\Resources\TransportRates\Pages\ListTransportRates;
use App\Models\TransportRate;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
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
            TextInput::make('vehicle_type')->disabled()->columnSpanFull(),

            Section::make('Car Hire Base Fare')
                ->description('Hourly rental with a driver.')
                ->columns(3)
                ->schema([
                    TextInput::make('carhire_base_regular')->numeric()->required()->prefix('₦')->label('Regular'),
                    TextInput::make('carhire_base_standard')->numeric()->required()->prefix('₦')->label('Standard'),
                    TextInput::make('carhire_base_executive')->numeric()->required()->prefix('₦')->label('Executive'),
                ]),

            Section::make('Pickup & Dropoff Base Fare')
                ->description('One-way point-to-point transfer.')
                ->columns(3)
                ->schema([
                    TextInput::make('transfer_base_regular')->numeric()->required()->prefix('₦')->label('Regular'),
                    TextInput::make('transfer_base_standard')->numeric()->required()->prefix('₦')->label('Standard'),
                    TextInput::make('transfer_base_executive')->numeric()->required()->prefix('₦')->label('Executive'),
                ]),

            Section::make('Shared Rates')
                ->description('Applied the same way to both Car Hire and Pickup & Dropoff.')
                ->columns(2)
                ->schema([
                    TextInput::make('transfer_fuel_rate_per_minute')->numeric()->required()->prefix('₦')->label('Fuel Rate (per minute)')->helperText('Drive time for Pickup & Dropoff, rental duration for Car Hire.'),
                    TextInput::make('transfer_admin_fee_percent')->numeric()->step(0.1)->minValue(0)->maxValue(100)->required()->suffix('%')->label('Admin Fee')->helperText('Applied to (Base Fare + Tear & Wear + Fuel).'),
                ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('vehicle_type')
            ->columns([
                TextColumn::make('vehicle_type')->badge()->sortable(),

                TextColumn::make('carhire_base_regular')->money('NGN')->label('Regular')
                    ->visible(fn ($livewire) => ($livewire->activeTab ?? 'car_hire') === 'car_hire'),
                TextColumn::make('carhire_base_standard')->money('NGN')->label('Standard')
                    ->visible(fn ($livewire) => ($livewire->activeTab ?? 'car_hire') === 'car_hire'),
                TextColumn::make('carhire_base_executive')->money('NGN')->label('Executive')
                    ->visible(fn ($livewire) => ($livewire->activeTab ?? 'car_hire') === 'car_hire'),

                TextColumn::make('transfer_base_regular')->money('NGN')->label('Regular')
                    ->visible(fn ($livewire) => ($livewire->activeTab ?? 'car_hire') === 'pickup_dropoff'),
                TextColumn::make('transfer_base_standard')->money('NGN')->label('Standard')
                    ->visible(fn ($livewire) => ($livewire->activeTab ?? 'car_hire') === 'pickup_dropoff'),
                TextColumn::make('transfer_base_executive')->money('NGN')->label('Executive')
                    ->visible(fn ($livewire) => ($livewire->activeTab ?? 'car_hire') === 'pickup_dropoff'),

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
