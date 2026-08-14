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
use Filament\Schemas\Components\Section;
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
            Section::make('Pricing')
                ->description('Vendor price is what the provider charges us per plan. Markup is our service charge, applied on top of both plans. The public sees Vendor + Markup — that total is calculated automatically and is not stored separately.')
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('Given_Price1')
                        ->label('Vendor Price (VIP)')
                        ->numeric()->required()->minValue(0)->prefix('₦')
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set, callable $get) => $set('price1', Protocol::totalPrice($state, $get('markup_price')))),
                    TextInput::make('Given_Price2')
                        ->label('Vendor Price (Regular)')
                        ->numeric()->required()->minValue(0)->prefix('₦')
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set, callable $get) => $set('price2', Protocol::totalPrice($state, $get('markup_price')))),
                    TextInput::make('markup_price')
                        ->label('Markup (both plans)')
                        ->numeric()->required()->minValue(0)->prefix('₦')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            $set('price1', Protocol::totalPrice($get('Given_Price1'), $state));
                            $set('price2', Protocol::totalPrice($get('Given_Price2'), $state));
                        }),
                    TextInput::make('price1')
                        ->label('Total (VIP) — shown to public')
                        ->numeric()->prefix('₦')->disabled()->dehydrated(false),
                    TextInput::make('price2')
                        ->label('Total (Regular) — shown to public')
                        ->numeric()->prefix('₦')->disabled()->dehydrated(false),
                ]),
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
                TextColumn::make('Given_Price1')->label('Vendor (VIP)')->money('NGN'),
                TextColumn::make('Given_Price2')->label('Vendor (Regular)')->money('NGN'),
                TextColumn::make('markup_price')->label('Markup')->money('NGN'),
                TextColumn::make('price1')->label('Total (VIP)')->money('NGN')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('price2')->label('Total (Regular)')->money('NGN')->toggleable(isToggledHiddenByDefault: true),
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
