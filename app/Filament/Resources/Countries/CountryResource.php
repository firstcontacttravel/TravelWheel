<?php

namespace App\Filament\Resources\Countries;

use App\Filament\Resources\Countries\Pages\CreateCountry;
use App\Filament\Resources\Countries\Pages\EditCountry;
use App\Filament\Resources\Countries\Pages\ListCountries;
use App\Models\Country;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CountryResource extends Resource
{
    protected static ?string $model = Country::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static string|\UnitEnum|null $navigationGroup = 'Visa Catalogue';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Country')->schema([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('alpha2')->label('ISO alpha-2')->required()->length(2)->unique(ignoreRecord: true)->dehydrateStateUsing(fn ($state) => strtoupper($state)),
                TextInput::make('alpha3')->label('ISO alpha-3')->length(3)->unique(ignoreRecord: true)->dehydrateStateUsing(fn ($state) => filled($state) ? strtoupper($state) : null),
                TextInput::make('region')->maxLength(100),
                Toggle::make('is_active')->label('Available in selectors')->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('name')->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('alpha2')->label('ISO')->searchable()->badge(),
            TextColumn::make('region')->placeholder('—')->sortable(),
            TextColumn::make('visa_products_count')->label('Visa products')->counts('visaProducts'),
            IconColumn::make('is_active')->boolean()->label('Active'),
        ])->filters([
            TernaryFilter::make('is_active')->label('Selector availability'),
        ])->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCountries::route('/'),
            'create' => CreateCountry::route('/create'),
            'edit' => EditCountry::route('/{record}/edit'),
        ];
    }
}
