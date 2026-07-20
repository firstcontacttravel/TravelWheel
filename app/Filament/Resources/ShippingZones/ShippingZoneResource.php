<?php

namespace App\Filament\Resources\ShippingZones;

use App\Filament\Resources\ShippingZones\Pages\CreateShippingZone;
use App\Filament\Resources\ShippingZones\Pages\EditShippingZone;
use App\Filament\Resources\ShippingZones\Pages\ListShippingZones;
use App\Models\ShippingZone;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ShippingZoneResource extends Resource
{
    protected static ?string $model = ShippingZone::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static string|\UnitEnum|null $navigationGroup = 'Air Cargo';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Shipping Zones';

    protected static ?string $recordTitleAttribute = 'zone_name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('zone_name')
                ->required()
                ->unique(ignoreRecord: true)
                ->helperText('Must match the "Zone N" format used by the shipping calculator, e.g. "Zone 1".'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('zone_name')
            ->columns([
                TextColumn::make('zone_name')->label('Zone')->searchable()->weight('bold'),
                TextColumn::make('documentPrices_count')->label('Document prices')->counts('documentPrices'),
                TextColumn::make('packagePrices_count')->label('Package prices')->counts('packagePrices'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShippingZones::route('/'),
            'create' => CreateShippingZone::route('/create'),
            'edit' => EditShippingZone::route('/{record}/edit'),
        ];
    }
}
