<?php

namespace App\Filament\Resources\ReportingExportAudits;

use App\Filament\Resources\ReportingExportAudits\Pages\ListReportingExportAudits;
use App\Models\ReportingExportAudit;
use App\Support\Reporting\ReportingAccess;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ReportingExportAuditResource extends Resource
{
    protected static ?string $model = ReportingExportAudit::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentArrowDown;
    protected static string|\UnitEnum|null $navigationGroup = 'Insights';
    protected static ?string $navigationLabel = 'Export Audit';
    protected static ?int $navigationSort = 44;

    public static function table(Table $table): Table
    {
        return $table->defaultSort('exported_at', 'desc')->columns([
            TextColumn::make('user.name')->label('Exported by')->searchable(),
            TextColumn::make('report_key')->formatStateUsing(fn ($state) => str($state)->headline())->badge(),
            TextColumn::make('format')->badge(),
            TextColumn::make('row_count')->numeric()->sortable(),
            TextColumn::make('ip_address'),
            TextColumn::make('exported_at')->dateTime()->sortable(),
        ]);
    }

    public static function getPages(): array { return ['index' => ListReportingExportAudits::route('/')]; }
    public static function canViewAny(): bool { return ReportingAccess::canManage(auth()->user()); }
    public static function canCreate(): bool { return false; }
    public static function canEdit(Model $record): bool { return false; }
    public static function canDelete(Model $record): bool { return false; }
    public static function canDeleteAny(): bool { return false; }
}
