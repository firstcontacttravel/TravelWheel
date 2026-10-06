<?php

namespace App\Filament\Resources\ActivityLogs;

use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Models\ActivityLog;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

/**
 * Who did what, read-only. Nothing here can be edited or deleted, including
 * by the CEO, or it would stop being a receipt.
 */
class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'Team';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Activity Log';

    protected static ?string $modelLabel = 'activity';

    protected static ?string $pluralModelLabel = 'Activity Log';

    private const TIMEZONE = 'Africa/Lagos';

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('created_at')->label('When')->dateTime('d M Y, H:i:s', self::TIMEZONE),
            TextEntry::make('user.name')->label('Who')->placeholder('System'),
            TextEntry::make('department.name')->label('Department')->placeholder('-'),
            TextEntry::make('action')->label('Action')->fontFamily('mono'),
            TextEntry::make('description')->columnSpanFull(),
            TextEntry::make('subject_type')
                ->label('Record')
                ->state(fn (ActivityLog $record): string => self::subjectLabel($record))
                ->columnSpanFull(),
            TextEntry::make('properties')
                ->label('Details')
                ->state(fn (ActivityLog $record): HtmlString => new HtmlString(
                    '<pre class="tc-mono whitespace-pre-wrap text-xs">'
                    .e(json_encode($record->properties ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))
                    .'</pre>'
                ))
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(50)
            ->columns([
                TextColumn::make('created_at')
                    ->label('When')
                    ->since()
                    ->tooltip(fn (ActivityLog $record): string => $record->created_at->timezone(self::TIMEZONE)->format('d M Y, H:i:s'))
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Who')
                    ->description(fn (ActivityLog $record): ?string => $record->department?->name)
                    ->placeholder('System')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('What')
                    ->wrap()
                    ->searchable(),
                TextColumn::make('subject_type')
                    ->label('Record')
                    ->state(fn (ActivityLog $record): string => self::subjectLabel($record))
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('Who')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('department_id')
                    ->label('Department')
                    ->relationship('department', 'name'),
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date))),
            ])
            ->recordActions([
                ViewAction::make()->label('Details'),
            ]);
    }

    private static function subjectLabel(ActivityLog $record): string
    {
        if (! $record->subject_type) {
            return '-';
        }

        return str(class_basename($record->subject_type))->headline().' #'.$record->subject_id;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivityLogs::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
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
