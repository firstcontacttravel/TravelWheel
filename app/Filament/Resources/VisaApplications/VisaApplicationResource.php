<?php

namespace App\Filament\Resources\VisaApplications;

use App\Filament\Resources\VisaApplications\Pages\ListVisaApplications;
use App\Filament\Resources\VisaApplications\Pages\ViewVisaApplication;
use App\Filament\Resources\VisaApplications\Schemas\VisaApplicationInfolist;
use App\Filament\Resources\VisaApplications\Tables\VisaApplicationsTable;
use App\Models\VisaApplication;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class VisaApplicationResource extends Resource
{
    protected static ?string $model = VisaApplication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|\UnitEnum|null $navigationGroup = 'Visa Operations';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return VisaApplicationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VisaApplicationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return ['index' => ListVisaApplications::route('/'), 'view' => ViewVisaApplication::route('/{record}')];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->canViewVisaOperations() ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->canViewVisaOperations() ?? false;
    }

    public static function canEdit(Model $record): bool
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
