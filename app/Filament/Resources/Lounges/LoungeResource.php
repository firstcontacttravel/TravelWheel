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
            TextInput::make('priceA')->label('Adult Price')->numeric()->required()->prefix('₦'),
            TextInput::make('priceB')->label('Child Price')->numeric()->required()->prefix('₦'),
            TextInput::make('priceC')->label('Infant Price')->numeric()->required()->prefix('₦'),
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
                TextColumn::make('priceA')->label('Adult')->money('NGN'),
                TextColumn::make('priceB')->label('Child')->money('NGN'),
                TextColumn::make('priceC')->label('Infant')->money('NGN'),
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
