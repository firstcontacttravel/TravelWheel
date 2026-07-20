<?php

namespace App\Filament\Resources\Protocols;

use App\Filament\Resources\Protocols\Pages\CreateProtocol;
use App\Filament\Resources\Protocols\Pages\EditProtocol;
use App\Filament\Resources\Protocols\Pages\ListProtocols;
use App\Models\Protocol;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProtocolResource extends Resource
{
    protected static ?string $model = Protocol::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|\UnitEnum|null $navigationGroup = 'Protocol';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Pricing & Locations';

    protected static ?string $recordTitleAttribute = 'location';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('location')->required()->maxLength(225),
            TextInput::make('airport')->required()->maxLength(225),
            TextInput::make('service')->required()->maxLength(50),
            TextInput::make('price1')->label('VIP Price')->numeric()->required()->prefix('₦'),
            TextInput::make('price2')->label('Regular Price')->numeric()->required()->prefix('₦'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('location')
            ->columns([
                TextColumn::make('location')->searchable()->sortable(),
                TextColumn::make('airport')->searchable(),
                TextColumn::make('service')->badge(),
                TextColumn::make('price1')->label('VIP')->money('NGN'),
                TextColumn::make('price2')->label('Regular')->money('NGN'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProtocols::route('/'),
            'create' => CreateProtocol::route('/create'),
            'edit' => EditProtocol::route('/{record}/edit'),
        ];
    }
}
