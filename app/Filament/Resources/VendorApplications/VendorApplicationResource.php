<?php

namespace App\Filament\Resources\VendorApplications;

use App\Filament\Resources\VendorApplications\Pages\ListVendorApplications;
use App\Filament\Resources\VendorApplications\Pages\ViewVendorApplication;
use App\Filament\Resources\VendorApplications\Schemas\VendorApplicationInfolist;
use App\Filament\Resources\VendorApplications\Tables\VendorApplicationsTable;
use App\Models\User;
use App\Models\VendorApplication;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Applications from the vendor / partner registration form. Staff review
 * them here; nothing is created or edited by hand.
 */
class VendorApplicationResource extends Resource
{
    protected static ?string $model = VendorApplication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Vendor Applications';

    protected static ?string $recordTitleAttribute = 'registered_name';

    public static function getNavigationBadge(): ?string
    {
        $waiting = VendorApplication::where('status', 'received')->count();

        return $waiting > 0 ? (string) $waiting : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'New applications waiting for review';
    }

    /** Bank details are for finance; everyone else reviewing sees them masked. */
    public static function canSeeBankDetails(?User $user): bool
    {
        return $user !== null && ($user->isAdmin() || $user->department?->slug === 'finance');
    }

    public static function infolist(Schema $schema): Schema
    {
        return VendorApplicationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VendorApplicationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVendorApplications::route('/'),
            'view' => ViewVendorApplication::route('/{record}'),
        ];
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
