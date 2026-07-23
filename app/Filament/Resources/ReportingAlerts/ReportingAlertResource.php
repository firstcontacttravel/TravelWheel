<?php

namespace App\Filament\Resources\ReportingAlerts;

use App\Filament\Resources\ReportingAlerts\Pages\ListReportingAlerts;
use App\Models\ReportingAlert;
use App\Support\Reporting\ReportingAccess;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ReportingAlertResource extends Resource
{
    protected static ?string $model = ReportingAlert::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;
    protected static string|\UnitEnum|null $navigationGroup = 'Insights';
    protected static ?string $navigationLabel = 'Report Alerts';
    protected static ?int $navigationSort = 43;

    public static function table(Table $table): Table
    {
        return $table->defaultSort('detected_at', 'desc')->columns([
            TextColumn::make('severity')->badge()->color(fn ($state) => $state === 'critical' ? 'danger' : 'warning'),
            TextColumn::make('product')->formatStateUsing(fn ($state) => config("reporting.products.{$state}.label", str($state)->headline()))->badge(),
            TextColumn::make('type')->formatStateUsing(fn ($state) => str($state)->replace('_', ' ')->headline()),
            TextColumn::make('message')->wrap()->searchable(),
            TextColumn::make('observed_value')->numeric(),
            TextColumn::make('detected_at')->dateTime()->sortable(),
            IconColumn::make('acknowledged_at')->label('Acknowledged')->boolean(fn ($state) => filled($state)),
            IconColumn::make('resolved_at')->label('Resolved')->boolean(fn ($state) => filled($state)),
        ])->filters([
            SelectFilter::make('severity')->options(['warning' => 'Warning', 'critical' => 'Critical']),
            SelectFilter::make('product')->options(collect(config('reporting.products'))->mapWithKeys(fn ($item, $key) => [$key => $item['label']])),
        ])->recordActions([
            Action::make('acknowledge')->visible(fn (ReportingAlert $record) => ! $record->acknowledged_at)
                ->action(fn (ReportingAlert $record) => $record->update(['acknowledged_by' => auth()->id(), 'acknowledged_at' => now()])),
            Action::make('resolve')->visible(fn (ReportingAlert $record) => ReportingAccess::canManage(auth()->user()) && ! $record->resolved_at)
                ->requiresConfirmation()->color('success')->action(fn (ReportingAlert $record) => $record->update(['resolved_at' => now()])),
        ]);
    }

    public static function getPages(): array { return ['index' => ListReportingAlerts::route('/')]; }
    public static function canViewAny(): bool { return ReportingAccess::canView(auth()->user()); }
    public static function canCreate(): bool { return false; }
    public static function canEdit(Model $record): bool { return false; }
    public static function canDelete(Model $record): bool { return false; }
    public static function canDeleteAny(): bool { return false; }
}
