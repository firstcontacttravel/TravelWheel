<?php

namespace App\Filament\Resources\CountryGroups;

use App\Filament\Resources\CountryGroups\Pages\CreateCountryGroup;
use App\Filament\Resources\CountryGroups\Pages\EditCountryGroup;
use App\Filament\Resources\CountryGroups\Pages\ListCountryGroups;
use App\Models\CountryGroup;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CountryGroupResource extends Resource
{
    protected static ?string $model = CountryGroup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'Visa Catalogue';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Reusable country group')->schema([
                TextInput::make('name')->required()->live(onBlur: true)->afterStateUpdated(fn (?string $state, Set $set) => $set('slug', Str::slug($state ?? ''))),
                TextInput::make('slug')->required()->unique(ignoreRecord: true),
                Textarea::make('description')->columnSpanFull(),
                Select::make('countries')->relationship('countries', 'name', modifyQueryUsing: fn ($query) => $query->where('is_active', true))->multiple()->searchable()->preload()->required()->columnSpanFull(),
                TextInput::make('version')->numeric()->default(1)->minValue(1)->required(),
                Toggle::make('is_active')->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('name')->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('slug')->copyable(),
            TextColumn::make('countries_count')->counts('countries')->label('Countries'),
            TextColumn::make('version')->badge(),
            IconColumn::make('is_active')->boolean(),
        ])->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListCountryGroups::route('/'), 'create' => CreateCountryGroup::route('/create'), 'edit' => EditCountryGroup::route('/{record}/edit')];
    }
}
