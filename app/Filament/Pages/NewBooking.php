<?php

namespace App\Filament\Pages;

use App\Filament\Booking\StaffBookingActions;
use App\Models\StaffBooking;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Where the CEO, Finance and Operations book for a customer without going to
 * the website, and follow those bookings until they're paid.
 */
class NewBooking extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.new-booking';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPlusCircle;

    protected static ?int $navigationSort = -1;

    protected static ?string $navigationLabel = 'New Booking';

    protected static ?string $title = 'New Booking';

    public static function canAccess(): bool
    {
        return auth()->user()?->canBookForCustomers() ?? false;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Choose a product above. The customer pays through an emailed link, or Finance records their payment.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('bookCarHire')
                ->label('Car Hire')
                ->icon('heroicon-o-truck')
                ->url(fn (): string => BookGroundTransport::getUrl(['tab' => 'car'])),
            Action::make('bookTransfer')
                ->label("Pick up 'n' Drop off")
                ->icon('heroicon-o-map-pin')
                ->url(fn (): string => BookGroundTransport::getUrl(['tab' => 'transfer'])),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(StaffBooking::query()->with(['bookable', 'creator', 'recorder']))
            ->heading('Bookings made by staff')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference')
                    ->state(fn (StaffBooking $record): ?string => $record->bookable?->payment_reference)
                    ->copyable()
                    ->weight('bold')
                    ->description(fn (StaffBooking $record): string => $record->productLabel()),
                TextColumn::make('customer')
                    ->state(fn (StaffBooking $record): ?string => $record->bookable?->full_name)
                    ->description(fn (StaffBooking $record): ?string => $record->bookable?->email),
                TextColumn::make('amount')
                    ->state(fn (StaffBooking $record) => $record->bookable?->amount)
                    ->money('NGN'),
                TextColumn::make('payment')
                    ->state(fn (StaffBooking $record): string => ucfirst((string) $record->bookable?->payment_status))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Paid', 'Confirmed', 'Completed' => 'success',
                        'Cancelled', 'Failed' => 'danger',
                        default => 'warning',
                    })
                    ->description(fn (StaffBooking $record): ?string => $record->recorded_at
                        ? (StaffBooking::RECORDED_METHODS[$record->recorded_method] ?? $record->recorded_method).' recorded by '.$record->recorder?->name
                        : ($record->link_sent_at ? 'Link sent '.$record->link_sent_at->diffForHumans() : null)),
                TextColumn::make('creator.name')->label('Booked by'),
                TextColumn::make('created_at')->label('Created')->since()->sortable(),
            ])
            ->recordActions(StaffBookingActions::all())
            ->emptyStateHeading('No staff bookings yet');
    }
}
