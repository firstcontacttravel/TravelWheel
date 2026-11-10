<?php

namespace App\Filament\Resources\TravelFlexApplications;

use App\Filament\Resources\TravelFlexApplications\Pages\ListTravelFlexApplications;
use App\Filament\Resources\TravelFlexApplications\Pages\ViewTravelFlexApplication;
use App\Filament\Resources\TravelFlexApplications\Schemas\TravelFlexApplicationInfolist;
use App\Filament\Resources\TravelFlexApplications\Tables\TravelFlexApplicationsTable;
use App\Models\TravelFlexApplication;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TravelFlexApplicationResource extends Resource
{
    protected static ?string $model = TravelFlexApplication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?string $navigationLabel = 'TravelFlex';

    protected static string|\UnitEnum|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'TravelFlex Application';

    protected static ?string $pluralModelLabel = 'TravelFlex Applications';

    protected static ?string $recordTitleAttribute = 'booking_ref';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return TravelFlexApplicationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TravelFlexApplicationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTravelFlexApplications::route('/'),
            'view' => ViewTravelFlexApplication::route('/{record}'),
        ];
    }

    /** Credit applications, with customers' financial documents: IT stays out of them. */
    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user !== null && ($user->isAdmin() || ! $user->isIt());
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return false;
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
