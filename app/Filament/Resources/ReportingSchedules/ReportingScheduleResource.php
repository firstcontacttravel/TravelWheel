<?php

namespace App\Filament\Resources\ReportingSchedules;

use App\Filament\Resources\ReportingSchedules\Pages\CreateReportingSchedule;
use App\Filament\Resources\ReportingSchedules\Pages\EditReportingSchedule;
use App\Filament\Resources\ReportingSchedules\Pages\ListReportingSchedules;
use App\Models\ReportingSchedule;
use App\Support\Reporting\ReportingAccess;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReportingScheduleResource extends Resource
{
    protected static ?string $model = ReportingSchedule::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;
    protected static string|\UnitEnum|null $navigationGroup = 'Insights';
    protected static ?string $navigationLabel = 'Scheduled Reports';
    protected static ?int $navigationSort = 42;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(150),
            Select::make('report_key')->required()->options([
                'transactions' => 'All transactions', 'reconciliation' => 'Reconciliation',
                'operations' => 'Operations backlog', 'exceptions' => 'Exceptions',
            ]),
            Select::make('format')->options(['csv' => 'CSV', 'xlsx' => 'Excel'])->default('xlsx')->required(),
            Select::make('frequency')->options(['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'])->required(),
            TagsInput::make('recipients')->nestedRecursiveRules(['email'])->required()->columnSpanFull(),
            KeyValue::make('filters')->helperText('Optional report filters such as from, to, products, or payment_status.')->columnSpanFull(),
            DateTimePicker::make('next_send_at')->seconds(false)->required(),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('next_send_at')->columns([
            TextColumn::make('name')->searchable()->weight('bold'),
            TextColumn::make('report_key')->formatStateUsing(fn ($state) => str($state)->headline())->badge(),
            TextColumn::make('frequency')->badge(),
            TextColumn::make('format')->badge(),
            TextColumn::make('recipients')->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : $state)->limit(45),
            TextColumn::make('next_send_at')->dateTime()->sortable(),
            TextColumn::make('last_sent_at')->since(),
            IconColumn::make('is_active')->boolean(),
        ])->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListReportingSchedules::route('/'), 'create' => CreateReportingSchedule::route('/create'), 'edit' => EditReportingSchedule::route('/{record}/edit')];
    }

    public static function canViewAny(): bool { return ReportingAccess::canManage(auth()->user()); }
}
