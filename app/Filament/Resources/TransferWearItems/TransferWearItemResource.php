<?php

namespace App\Filament\Resources\TransferWearItems;

use App\Filament\Resources\TransferWearItems\Pages\CreateTransferWearItem;
use App\Filament\Resources\TransferWearItems\Pages\EditTransferWearItem;
use App\Filament\Resources\TransferWearItems\Pages\ListTransferWearItems;
use App\Models\TransferWearItem;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TransferWearItemResource extends Resource
{
    protected static ?string $model = TransferWearItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|\UnitEnum|null $navigationGroup = 'Car Hire & Transfer';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Transfer Wear Items';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('vehicle_type')
                ->options(['saloon' => 'Saloon', 'suv' => 'SUV', 'van' => 'Van', 'bus' => 'Bus', 'luxury' => 'Luxury'])
                ->required(),
            TextInput::make('name')
                ->required()
                ->maxLength(60)
                ->helperText('e.g. "AC", "Year", "Neatness".'),
            TextInput::make('percentage')
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->step(0.1)
                ->suffix('%')
                ->required()
                ->helperText('Percentage of the Base Fare this item adds to Tear & Wear.'),
            TextInput::make('sort_order')
                ->numeric()
                ->default(0)
                ->helperText('Lower numbers show first.'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('vehicle_type')
            ->columns([
                TextColumn::make('vehicle_type')->badge()->sortable(),
                TextColumn::make('name')->searchable()->sortable()->weight('bold'),
                TextColumn::make('percentage')->suffix('%')->sortable(),
                TextColumn::make('sort_order')->label('Order')->sortable(),
            ])
            ->filters([
                SelectFilter::make('vehicle_type')->options(['saloon' => 'Saloon', 'suv' => 'SUV', 'van' => 'Van', 'bus' => 'Bus', 'luxury' => 'Luxury']),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransferWearItems::route('/'),
            'create' => CreateTransferWearItem::route('/create'),
            'edit' => EditTransferWearItem::route('/{record}/edit'),
        ];
    }
}
