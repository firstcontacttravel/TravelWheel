<?php

namespace App\Filament\Resources\SupportProductPrices;

use App\Filament\Resources\SupportProductPrices\Pages\EditSupportProductPrice;
use App\Filament\Resources\SupportProductPrices\Pages\ListSupportProductPrices;
use App\Models\SupportProductPrice;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SupportProductPriceResource extends Resource
{
    protected static ?string $model = SupportProductPrice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Support Requests';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Product Prices';

    protected static ?string $recordTitleAttribute = 'label';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('product')->label('Product key')->disabled(),
            TextInput::make('label')->required()->maxLength(255),
            TextInput::make('amount')
                ->label('Price')
                ->numeric()
                ->minValue(0)
                ->required()
                ->prefix('₦')
                ->helperText('This is the fixed fee shown to clients for this support product.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('label')
            ->columns([
                TextColumn::make('label')->searchable(),
                TextColumn::make('product')->label('Key')->badge()->sortable(),
                TextColumn::make('amount')->label('Price')->money('NGN')->sortable(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupportProductPrices::route('/'),
            'edit' => EditSupportProductPrice::route('/{record}/edit'),
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
