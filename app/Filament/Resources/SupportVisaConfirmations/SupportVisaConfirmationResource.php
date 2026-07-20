<?php

namespace App\Filament\Resources\SupportVisaConfirmations;

use App\Filament\Resources\SupportVisaConfirmations\Pages\ListSupportVisaConfirmations;
use App\Filament\Resources\SupportVisaConfirmations\Pages\ViewSupportVisaConfirmation;
use App\Filament\Resources\SupportVisaConfirmations\Tables\SupportVisaConfirmationsTable;
use App\Models\SupportVisaConfirmation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SupportVisaConfirmationResource extends Resource
{
    protected static ?string $model = SupportVisaConfirmation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Support Requests';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Visa Confirmation';

    protected static ?string $recordTitleAttribute = 'payment_reference';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return SupportVisaConfirmationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupportVisaConfirmations::route('/'),
            'view' => ViewSupportVisaConfirmation::route('/{record}'),
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
