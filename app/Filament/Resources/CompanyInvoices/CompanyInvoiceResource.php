<?php

namespace App\Filament\Resources\CompanyInvoices;

use App\Filament\Resources\CompanyInvoices\Pages\ListCompanyInvoices;
use App\Models\CompanyInvoice;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/** Every company's invoices and credit notes, read-only, for Finance and the CEO. */
class CompanyInvoiceResource extends Resource
{
    protected static ?string $model = CompanyInvoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Corporate';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Invoices';

    protected static ?string $modelLabel = 'invoice';

    public static function canViewAny(): bool
    {
        return auth()->user()?->canHandleMoney() ?? false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('issued_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with('company'))
            ->columns([
                TextColumn::make('number')->searchable()->copyable()->weight('bold'),
                TextColumn::make('type')->badge()
                    ->formatStateUsing(fn (string $state): string => CompanyInvoice::TYPES[$state] ?? $state)
                    ->color(fn (string $state): string => $state === 'credit_note' ? 'warning' : 'gray'),
                TextColumn::make('company.name')->label('Company')->searchable(),
                TextColumn::make('booking_details.reference')->label('Booking')->placeholder('-'),
                TextColumn::make('total')->money('NGN')->sortable(),
                TextColumn::make('vat_amount')->label('VAT')->money('NGN')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('issued_at')->label('Issued')->dateTime('j M Y', 'Africa/Lagos')->sortable(),
            ])
            ->filters([
                SelectFilter::make('company_id')->label('Company')->relationship('company', 'name')->searchable()->preload(),
                SelectFilter::make('type')->options(CompanyInvoice::TYPES),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url(fn (CompanyInvoice $record): string => route('admin.corporate.invoice', $record)),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListCompanyInvoices::route('/')];
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
