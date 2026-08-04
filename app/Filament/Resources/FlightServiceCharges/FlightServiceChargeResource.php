<?php

namespace App\Filament\Resources\FlightServiceCharges;

use App\Filament\Resources\FlightServiceCharges\Pages\EditFlightServiceCharge;
use App\Filament\Resources\FlightServiceCharges\Pages\ListFlightServiceCharges;
use App\Models\FlightServiceCharge;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class FlightServiceChargeResource extends Resource
{
    protected static ?string $model = FlightServiceCharge::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 51;

    protected static ?string $navigationLabel = 'Flight Service Charges';

    protected static ?string $recordTitleAttribute = 'cabin';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('route_category')
                ->label('Route')
                ->formatStateUsing(fn ($state): string => self::routeLabel((string) $state))
                ->disabled(),
            TextInput::make('cabin')
                ->formatStateUsing(fn ($state): string => self::cabinLabel((string) $state))
                ->disabled(),
            TextInput::make('amount')
                ->label('Charge per passenger')
                ->numeric()
                ->minValue(0)
                ->required()
                ->prefix('₦')
                ->helperText('This amount is multiplied by the number of passengers in the airline fare breakdown.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('route_category')
            ->columns([
                TextColumn::make('route_category')
                    ->label('Route')
                    ->formatStateUsing(fn ($state): string => self::routeLabel((string) $state))
                    ->badge()
                    ->sortable(),
                TextColumn::make('cabin')
                    ->formatStateUsing(fn ($state): string => self::cabinLabel((string) $state))
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Per passenger')
                    ->money('NGN')
                    ->sortable(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFlightServiceCharges::route('/'),
            'edit' => EditFlightServiceCharge::route('/{record}/edit'),
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

    private static function routeLabel(string $category): string
    {
        return match ($category) {
            'domestic' => 'Domestic (within Nigeria)',
            'from_nigeria' => 'Starts in Nigeria',
            'touches_nigeria' => 'Inbound / touches Nigeria',
            'not_nigeria' => 'Outside Nigeria',
            default => str($category)->replace('_', ' ')->title()->toString(),
        };
    }

    private static function cabinLabel(string $cabin): string
    {
        return str($cabin)->replace('_', ' ')->title()->toString();
    }
}
