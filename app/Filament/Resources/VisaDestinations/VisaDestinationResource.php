<?php

namespace App\Filament\Resources\VisaDestinations;

use App\Filament\Resources\VisaDestinations\Pages\CreateVisaDestination;
use App\Filament\Resources\VisaDestinations\Pages\EditVisaDestination;
use App\Filament\Resources\VisaDestinations\Pages\ListVisaDestinations;
use App\Models\VisaDestination;
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

class VisaDestinationResource extends Resource
{
    protected static ?string $model = VisaDestination::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static string|\UnitEnum|null $navigationGroup = 'Visa Catalogue';

    protected static ?int $navigationSort = 15;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Regional visa destination')->description('Create a customer-selectable destination such as the Schengen Area. Eligibility country groups remain separate.')->schema([
                TextInput::make('name')->required()->maxLength(255)->live(onBlur: true)->afterStateUpdated(fn (?string $state, Set $set) => $set('slug', Str::slug($state ?? ''))),
                TextInput::make('slug')->required()->unique(ignoreRecord: true)->maxLength(255),
                Select::make('countries')->label('Member countries')->relationship('countries', 'name', modifyQueryUsing: fn ($query) => $query->where('is_active', true))->multiple()->searchable()->preload()->required()->columnSpanFull()->helperText('Selecting a member country in the customer widget can also return visas assigned to this regional destination.'),
                Textarea::make('description')->rows(3)->columnSpanFull(),
                Toggle::make('is_active')->label('Show as a customer destination')->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('name')->columns([
            TextColumn::make('name')->searchable()->sortable()->weight('bold'),
            TextColumn::make('countries_count')->counts('countries')->label('Member countries'),
            TextColumn::make('products_count')->counts('products')->label('Visa products'),
            IconColumn::make('is_active')->boolean(),
        ])->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVisaDestinations::route('/'),
            'create' => CreateVisaDestination::route('/create'),
            'edit' => EditVisaDestination::route('/{record}/edit'),
        ];
    }
}
