<?php

namespace App\Filament\Resources\VisaVendors;

use App\Filament\Resources\VisaVendors\Pages\CreateVisaVendor;
use App\Filament\Resources\VisaVendors\Pages\EditVisaVendor;
use App\Filament\Resources\VisaVendors\Pages\ListVisaVendors;
use App\Models\VisaVendor;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VisaVendorResource extends Resource
{
    protected static ?string $model = VisaVendor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|\UnitEnum|null $navigationGroup = 'Visa Catalogue';

    protected static ?int $navigationSort = 25;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Vendor details')->description('Internal supplier information. Customers never see these details.')->schema([
                TextInput::make('name')->label('Company name')->required()->maxLength(255),
                TextInput::make('contact_person')->label('Contact person')->maxLength(255),
                TextInput::make('email')->email()->required()->maxLength(255)->helperText('Visa applications and their documents will be sent here.'),
                TextInput::make('phone')->tel()->maxLength(50),
                Textarea::make('address')->rows(3)->columnSpanFull(),
                Textarea::make('notes')->label('Internal notes')->rows(4)->columnSpanFull(),
                Toggle::make('is_active')->label('Vendor is active')->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('name')->columns([
            TextColumn::make('name')->label('Company')->searchable()->sortable()->weight('bold'),
            TextColumn::make('contact_person')->searchable()->placeholder('-'),
            TextColumn::make('email')->searchable()->copyable(),
            TextColumn::make('phone')->copyable()->placeholder('-'),
            TextColumn::make('products_count')->counts('products')->label('Visa products'),
            IconColumn::make('is_active')->boolean(),
        ])->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVisaVendors::route('/'),
            'create' => CreateVisaVendor::route('/create'),
            'edit' => EditVisaVendor::route('/{record}/edit'),
        ];
    }
}
