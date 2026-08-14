<?php

namespace App\Filament\Resources\Lounges;

use App\Filament\Resources\Lounges\Pages\CreateLounge;
use App\Filament\Resources\Lounges\Pages\EditLounge;
use App\Filament\Resources\Lounges\Pages\ListLounges;
use App\Models\Lounge;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LoungeResource extends Resource
{
    protected static ?string $model = Lounge::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|\UnitEnum|null $navigationGroup = 'Lounge';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Lounges';

    protected static ?string $recordTitleAttribute = 'brand_name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('lounge_id')->required()->maxLength(50),
            TextInput::make('brand_name')->required()->maxLength(50),
            TextInput::make('email')->email()->required()->maxLength(100),
            TextInput::make('phone_no')->tel()->required()->maxLength(50),
            TextInput::make('location')->required()->maxLength(50),
            TextInput::make('airport')->required()->maxLength(50),
            TextInput::make('service')->maxLength(50),
            TextInput::make('terminal')->required()->maxLength(50),
            Textarea::make('description')->required()->columnSpanFull(),
            TextInput::make('facilities1')->label('Facility 1')->required(),
            TextInput::make('facilities2')->label('Facility 2')->required(),
            TextInput::make('facilities3')->label('Facility 3')->required(),
            TextInput::make('facilities4')->label('Facility 4')->required(),
            TextInput::make('facilities5')->label('Facility 5')->required(),
            Section::make('Pricing')
                ->description('Vendor price is what the lounge charges us per passenger type. Markup is our service charge, applied on top of every tier. The public sees Vendor + Markup — that total is calculated automatically and is not stored separately.')
                ->columns(4)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('given_PriceA')
                        ->label('Vendor Price (Adult)')
                        ->numeric()->required()->minValue(0)->prefix('₦')
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set, callable $get) => $set('priceA', Lounge::totalPrice($state, $get('markup_price')))),
                    TextInput::make('given_PriceB')
                        ->label('Vendor Price (Child)')
                        ->numeric()->required()->minValue(0)->prefix('₦')
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set, callable $get) => $set('priceB', Lounge::totalPrice($state, $get('markup_price')))),
                    TextInput::make('given_PriceC')
                        ->label('Vendor Price (Infant)')
                        ->numeric()->required()->minValue(0)->prefix('₦')
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set, callable $get) => $set('priceC', Lounge::totalPrice($state, $get('markup_price')))),
                    TextInput::make('markup_price')
                        ->label('Markup (all tiers)')
                        ->numeric()->required()->minValue(0)->prefix('₦')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            $set('priceA', Lounge::totalPrice($get('given_PriceA'), $state));
                            $set('priceB', Lounge::totalPrice($get('given_PriceB'), $state));
                            $set('priceC', Lounge::totalPrice($get('given_PriceC'), $state));
                        }),
                    TextInput::make('priceA')
                        ->label('Total (Adult) — shown to public')
                        ->numeric()->prefix('₦')->disabled()->dehydrated(false),
                    TextInput::make('priceB')
                        ->label('Total (Child) — shown to public')
                        ->numeric()->prefix('₦')->disabled()->dehydrated(false),
                    TextInput::make('priceC')
                        ->label('Total (Infant) — shown to public')
                        ->numeric()->prefix('₦')->disabled()->dehydrated(false),
                ]),
            TextInput::make('pics1')->label('Image 1 filename')->required()->helperText('Filename only, e.g. lounge1.jpg — file must exist in public/assets/lounge/'),
            TextInput::make('pics2')->label('Image 2 filename')->required(),
            TextInput::make('pics3')->label('Image 3 filename')->required(),
            TextInput::make('pics4')->label('Image 4 filename')->required(),
            TextInput::make('pics5')->label('Image 5 filename')->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('brand_name')
            ->columns([
                ImageColumn::make('pics1')->label('')->getStateUsing(fn (Lounge $record): string => asset('assets/lounge/' . $record->pics1)),
                TextColumn::make('brand_name')->searchable()->description(fn (Lounge $record): string => $record->location),
                TextColumn::make('airport')->searchable(),
                TextColumn::make('terminal'),
                TextColumn::make('given_PriceA')->label('Vendor (Adult)')->money('NGN'),
                TextColumn::make('given_PriceB')->label('Vendor (Child)')->money('NGN'),
                TextColumn::make('given_PriceC')->label('Vendor (Infant)')->money('NGN'),
                TextColumn::make('markup_price')->label('Markup')->money('NGN'),
                TextColumn::make('priceA')->label('Total (Adult)')->money('NGN')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('priceB')->label('Total (Child)')->money('NGN')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('priceC')->label('Total (Infant)')->money('NGN')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLounges::route('/'),
            'create' => CreateLounge::route('/create'),
            'edit' => EditLounge::route('/{record}/edit'),
        ];
    }
}
