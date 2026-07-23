<?php

namespace App\Filament\Resources\ReportingTargets;

use App\Filament\Resources\ReportingTargets\Pages\CreateReportingTarget;
use App\Filament\Resources\ReportingTargets\Pages\EditReportingTarget;
use App\Filament\Resources\ReportingTargets\Pages\ListReportingTargets;
use App\Models\ReportingTarget;
use App\Support\Reporting\ReportingAccess;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReportingTargetResource extends Resource
{
    protected static ?string $model = ReportingTarget::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;
    protected static string|\UnitEnum|null $navigationGroup = 'Insights';
    protected static ?string $navigationLabel = 'Report Targets';
    protected static ?int $navigationSort = 41;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('label')->required()->maxLength(150),
            Select::make('product')->options(collect(config('reporting.products'))->mapWithKeys(fn ($item, $key) => [$key => $item['label']]))->placeholder('All products'),
            Select::make('metric')->required()->options([
                'verified_collections' => 'Verified collections',
                'travelwheel_revenue' => 'TravelWheel revenue',
                'gross_value' => 'Gross booking value',
                'transactions' => 'Transactions',
                'paid_transactions' => 'Paid transactions',
            ]),
            TextInput::make('target_value')->numeric()->minValue(0)->required(),
            DatePicker::make('period_start')->required(),
            DatePicker::make('period_end')->required()->afterOrEqual('period_start'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('period_start', 'desc')->columns([
            TextColumn::make('label')->searchable()->weight('bold'),
            TextColumn::make('product')->formatStateUsing(fn ($state) => $state ? config("reporting.products.{$state}.label", str($state)->headline()) : 'All products')->badge(),
            TextColumn::make('metric')->formatStateUsing(fn ($state) => str($state)->replace('_', ' ')->headline()),
            TextColumn::make('target_value')->numeric()->sortable(),
            TextColumn::make('period_start')->date(),
            TextColumn::make('period_end')->date(),
        ])->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListReportingTargets::route('/'), 'create' => CreateReportingTarget::route('/create'), 'edit' => EditReportingTarget::route('/{record}/edit')];
    }

    public static function canViewAny(): bool { return ReportingAccess::canManage(auth()->user()); }
}
