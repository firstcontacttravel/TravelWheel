<?php

namespace App\Filament\Resources\CompanyWalletTopUps;

use App\Filament\Resources\CompanyWalletTopUps\Pages\ListCompanyWalletTopUps;
use App\Filament\Resources\Companies\CompanyResource;
use App\Models\CompanyWalletTopUp;
use App\Services\Corporate\CompanyWalletTopUps;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Finance's queue of corporate wallet top-ups. Every action moves money, so each
 * is limited to Finance and the CEO on the server as well as on screen, and each
 * goes on the activity log through ActivityRecorder.
 */
class CompanyWalletTopUpResource extends Resource
{
    protected static ?string $model = CompanyWalletTopUp::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected static string|\UnitEnum|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 15;

    protected static ?string $navigationLabel = 'Wallet Top-ups';

    protected static ?string $modelLabel = 'wallet top-up';

    public static function canHandleMoney(): bool
    {
        return auth()->user()?->canHandleMoney() ?? false;
    }

    public static function canViewAny(): bool
    {
        return self::canHandleMoney();
    }

    public static function getNavigationBadge(): ?string
    {
        $waiting = CompanyWalletTopUp::where('status', 'pending')->where('method', 'bank_transfer')->count();

        return $waiting > 0 ? (string) $waiting : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Bank transfers waiting for confirmation';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['company', 'requestedBy', 'decidedBy']))
            ->columns([
                TextColumn::make('reference')->searchable()->copyable()->weight('bold'),
                TextColumn::make('company.name')->label('Company')->searchable()
                    ->url(fn (CompanyWalletTopUp $record): string => CompanyResource::getUrl('view', ['record' => $record->company_id])),
                TextColumn::make('method')->formatStateUsing(fn (string $state): string => CompanyWalletTopUp::METHODS[$state] ?? $state),
                TextColumn::make('amount')->label('To wallet')->money('NGN')->sortable(),
                TextColumn::make('fee_amount')->label('Card fee')->money('NGN')->toggleable(),
                TextColumn::make('bank_reference')->label('Their reference')->placeholder('-')->searchable()->toggleable(),
                TextColumn::make('status')->badge()
                    ->formatStateUsing(fn (string $state): string => CompanyWalletTopUp::STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => CompanyWalletTopUp::STATUS_COLORS[$state] ?? 'gray'),
                TextColumn::make('decidedBy.name')->label('Decided by')->placeholder('-')->toggleable(),
                TextColumn::make('created_at')->label('Recorded')->dateTime('j M Y, g:i A', 'Africa/Lagos')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(CompanyWalletTopUp::STATUSES),
                SelectFilter::make('method')->options(CompanyWalletTopUp::METHODS),
            ])
            ->recordActions([
                self::confirmAction(),
                self::rejectAction(),
                self::checkCardAction(),
            ]);
    }

    private static function confirmAction(): Action
    {
        return Action::make('confirm')
            ->label('Confirm')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->authorize(fn (): bool => self::canHandleMoney())
            ->visible(fn (CompanyWalletTopUp $record): bool => $record->isPending() && ! $record->isCard())
            ->modalDescription('Only confirm once the money is in the bank. This adds the amount below to the company\'s wallet straight away.')
            ->fillForm(fn (CompanyWalletTopUp $record): array => ['amount' => $record->amount])
            ->form([
                TextInput::make('amount')->label('Amount received')->numeric()->minValue(0.01)->required()->prefix('₦')
                    ->helperText('Change this if a different amount arrived.'),
                Textarea::make('note')->label('Note (optional)')->rows(2),
            ])
            ->action(function (CompanyWalletTopUp $record, array $data): void {
                app(CompanyWalletTopUps::class)->confirmBankTransfer($record, (float) $data['amount'], auth()->user(), $data['note'] ?? null);
                Notification::make()->success()->title('Top-up confirmed and added to the wallet')->send();
            });
    }

    private static function rejectAction(): Action
    {
        return Action::make('reject')
            ->label('Reject')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->authorize(fn (): bool => self::canHandleMoney())
            ->visible(fn (CompanyWalletTopUp $record): bool => $record->isPending() && ! $record->isCard())
            ->modalDescription('The company\'s Admins are emailed this reason.')
            ->form([
                Textarea::make('reason')->label('Reason (sent to the company)')->required()->rows(3),
            ])
            ->action(function (CompanyWalletTopUp $record, array $data): void {
                app(CompanyWalletTopUps::class)->rejectBankTransfer($record, $data['reason'], auth()->user());
                Notification::make()->success()->title('Top-up rejected')->send();
            });
    }

    /** For a card payment whose return trip and webhook both went missing. */
    private static function checkCardAction(): Action
    {
        return Action::make('checkCard')
            ->label('Check with SeerBit')
            ->icon('heroicon-o-arrow-path')
            ->color('gray')
            ->authorize(fn (): bool => self::canHandleMoney())
            ->visible(fn (CompanyWalletTopUp $record): bool => $record->isCard() && $record->status !== 'confirmed')
            ->requiresConfirmation()
            ->modalDescription('Asks SeerBit about this payment, and adds it to the wallet if it was paid in full.')
            ->action(function (CompanyWalletTopUp $record): void {
                $status = app(CompanyWalletTopUps::class)->settleCard($record);

                Notification::make()
                    ->title('SeerBit says: '.(CompanyWalletTopUp::STATUSES[$status] ?? $status))
                    ->color($status === 'confirmed' ? 'success' : 'warning')
                    ->send();
            });
    }

    public static function getPages(): array
    {
        return ['index' => ListCompanyWalletTopUps::route('/')];
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
