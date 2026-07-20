<?php

namespace App\Filament\Resources\FleetCars;

use App\Filament\Resources\FleetCars\Pages\CreateFleetCar;
use App\Filament\Resources\FleetCars\Pages\EditFleetCar;
use App\Filament\Resources\FleetCars\Pages\ListFleetCars;
use App\Models\FleetCar;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FleetCarResource extends Resource
{
    protected static ?string $model = FleetCar::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|\UnitEnum|null $navigationGroup = 'Car Hire & Transfer';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Fleet';

    protected static ?string $recordTitleAttribute = 'car_name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('service_type')
                ->options(['car_hire' => 'Car Hire', 'transfer' => 'Transfer'])
                ->required()
                ->live(),
            Select::make('vehicle_type')
                ->options(['saloon' => 'Saloon', 'suv' => 'SUV', 'van' => 'Van', 'bus' => 'Bus', 'luxury' => 'Luxury'])
                ->required(),
            Select::make('category')
                ->options(['Regular' => 'Regular', 'Standard' => 'Standard', 'Executive' => 'Executive'])
                ->visible(fn (Get $get): bool => $get('service_type') === 'car_hire')
                ->required(fn (Get $get): bool => $get('service_type') === 'car_hire'),
            TextInput::make('car_name')->required()->maxLength(100),
            TextInput::make('passengers')->maxLength(60)->helperText('e.g. "1 – 3 Passengers"'),
            Textarea::make('features')
                ->helperText('One feature per line, e.g. "Air conditioning"')
                ->rows(4)
                ->afterStateHydrated(fn ($component, $state) => $component->state(is_array($state) ? implode("\n", $state) : $state))
                ->dehydrateStateUsing(fn ($state) => array_values(array_filter(array_map('trim', explode("\n", (string) $state))))),
            FileUpload::make('images')
                ->multiple()
                ->image()
                ->reorderable()
                ->disk('public')
                ->directory('fleet')
                ->columnSpanFull(),
            Toggle::make('is_active')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('vehicle_type')
            ->columns([
                ImageColumn::make('images')->label('Photo')->getStateUsing(fn (FleetCar $record) => $record->images[0] ?? null)->disk('public'),
                TextColumn::make('car_name')->searchable()->sortable()->weight('bold'),
                TextColumn::make('service_type')->badge()->sortable(),
                TextColumn::make('vehicle_type')->badge()->sortable(),
                TextColumn::make('category')->placeholder('-'),
                TextColumn::make('passengers')->placeholder('-'),
                IconColumn::make('is_active')->boolean()->sortable(),
            ])
            ->filters([
                SelectFilter::make('service_type')->options(['car_hire' => 'Car Hire', 'transfer' => 'Transfer']),
                SelectFilter::make('vehicle_type')->options(['saloon' => 'Saloon', 'suv' => 'SUV', 'van' => 'Van', 'bus' => 'Bus', 'luxury' => 'Luxury']),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFleetCars::route('/'),
            'create' => CreateFleetCar::route('/create'),
            'edit' => EditFleetCar::route('/{record}/edit'),
        ];
    }
}
