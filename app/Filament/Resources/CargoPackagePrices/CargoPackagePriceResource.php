<?php

namespace App\Filament\Resources\CargoPackagePrices;

use App\Filament\Resources\CargoPackagePrices\Pages\CreateCargoPackagePrice;
use App\Filament\Resources\CargoPackagePrices\Pages\EditCargoPackagePrice;
use App\Filament\Resources\CargoPackagePrices\Pages\ListCargoPackagePrices;
use App\Models\CargoPackagePrice;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CargoPackagePriceResource extends Resource
{
    protected static ?string $model = CargoPackagePrice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static string|\UnitEnum|null $navigationGroup = 'Air Cargo';

    protected static ?int $navigationSort = 40;

    protected static ?string $navigationLabel = 'Package Pricing';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('zone_id')
                ->label('Zone')
                ->relationship('zone', 'zone_name')
                ->required()
                ->searchable()
                ->columnSpanFull(),
            TextInput::make('weight_0_5')->label('Up to 0.5kg')->numeric()->required()->prefix('₦'),
            TextInput::make('weight_1_0')->label('Up to 1.0kg')->numeric()->required()->prefix('₦'),
            TextInput::make('weight_1_5')->label('Up to 1.5kg')->numeric()->required()->prefix('₦'),
            TextInput::make('weight_2_0')->label('Up to 2.0kg')->numeric()->required()->prefix('₦'),
            TextInput::make('weight_2_5')->label('Up to 2.5kg')->numeric()->required()->prefix('₦'),
            TextInput::make('weight_3_0')->label('Up to 3.0kg')->numeric()->required()->prefix('₦'),
            TextInput::make('weight_3_5')->label('Up to 3.5kg')->numeric()->required()->prefix('₦'),
            TextInput::make('weight_4_0')->label('Up to 4.0kg')->numeric()->required()->prefix('₦'),
            TextInput::make('weight_4_5')->label('Up to 4.5kg')->numeric()->required()->prefix('₦'),
            TextInput::make('weight_5_0')->label('Up to 5.0kg')->numeric()->required()->prefix('₦'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('zone.zone_name')->label('Zone')->searchable()->weight('bold'),
                TextColumn::make('weight_0_5')->label('0.5kg')->money('NGN'),
                TextColumn::make('weight_1_0')->label('1.0kg')->money('NGN'),
                TextColumn::make('weight_1_5')->label('1.5kg')->money('NGN'),
                TextColumn::make('weight_2_0')->label('2.0kg')->money('NGN'),
                TextColumn::make('weight_2_5')->label('2.5kg')->money('NGN'),
                TextColumn::make('weight_3_0')->label('3.0kg')->money('NGN'),
                TextColumn::make('weight_3_5')->label('3.5kg')->money('NGN'),
                TextColumn::make('weight_4_0')->label('4.0kg')->money('NGN'),
                TextColumn::make('weight_4_5')->label('4.5kg')->money('NGN'),
                TextColumn::make('weight_5_0')->label('5.0kg')->money('NGN'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCargoPackagePrices::route('/'),
            'create' => CreateCargoPackagePrice::route('/create'),
            'edit' => EditCargoPackagePrice::route('/{record}/edit'),
        ];
    }
}
