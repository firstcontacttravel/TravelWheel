<?php

namespace App\Filament\Pages;

use App\Filament\Booking\StaffBookingActions;
use App\Models\StaffBooking;
use App\Services\Corporate\CorporateContext;
use App\Services\Flights\StaffFlightBooking;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
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
            Action::make('bookFlight')
                ->label('Flight')
                ->icon('heroicon-o-paper-airplane')
                ->modalHeading('Book a flight for a customer')
                ->modalDescription("Opens the website's flight search. Choose a fare and enter the passengers and the customer's contact details: the fare is then held with the airline, and the booking is listed here. Fares paid by card at booking (SkyLink and WebFare) can't be held, so they can't be chosen.")
                ->modalSubmitActionLabel('Open flight search')
                ->form([
                    Toggle::make('send_link')
                        ->label('Email the customer a payment link once the fare is held')
                        ->default(true),
                ])
                ->action(function (array $data): void {
                    if (app(CorporateContext::class)->hasActingSession()) {
                        Notification::make()->title('Stop booking for the company first')->body("You're booking for a company on the website. Stop that from the bar at the bottom of its pages.")->danger()->send();

                        return;
                    }

                    app(StaffFlightBooking::class)->start(auth()->user(), (bool) $data['send_link']);
                    $this->redirect(url('/'));
                }),
            Action::make('bookCarHire')
                ->label('Car Hire')
                ->icon('heroicon-o-truck')
                ->url(fn (): string => BookGroundTransport::getUrl(['tab' => 'car'])),
            Action::make('bookTransfer')
                ->label("Pick up 'n' Drop off")
                ->icon('heroicon-o-map-pin')
                ->url(fn (): string => BookGroundTransport::getUrl(['tab' => 'transfer'])),
            Action::make('bookTravelAssist')
                ->label('Travel Assist')
                ->icon('heroicon-o-lifebuoy')
                ->url(fn (): string => BookTravelAssist::getUrl()),
            Action::make('bookLounge')
                ->label('Lounge')
                ->icon('heroicon-o-building-office')
                ->url(fn (): string => BookLounge::getUrl()),
            Action::make('bookProtocol')
                ->label('Protocol')
                ->icon('heroicon-o-user-group')
                ->url(fn (): string => BookProtocol::getUrl()),
            Action::make('bookAirCargo')
                ->label('Air Cargo')
                ->icon('heroicon-o-paper-airplane')
                ->url(fn (): string => BookAirCargo::getUrl()),
            Action::make('bookInsurance')
                ->label('Insurance')
                ->icon('heroicon-o-shield-check')
                ->url(fn (): string => BookInsurance::getUrl()),
            Action::make('bookVisa')
                ->label('Visa')
                ->icon('heroicon-o-identification')
                ->visible(fn (): bool => (bool) config('visa.enabled'))
                ->url(fn (): string => BookVisa::getUrl()),
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
                    ->state(fn (StaffBooking $record): ?string => $record->bookingReference())
                    ->copyable()
                    ->weight('bold')
                    ->description(fn (StaffBooking $record): string => $record->productLabel()),
                TextColumn::make('customer')
                    ->state(fn (StaffBooking $record): ?string => $record->customerName())
                    ->description(fn (StaffBooking $record): ?string => $record->customerEmail()),
                TextColumn::make('amount')
                    ->state(fn (StaffBooking $record) => $record->total())
                    ->money('NGN')
                    ->placeholder('Priced when finished'),
                TextColumn::make('payment')
                    ->state(fn (StaffBooking $record): string => ucfirst(str_replace('_', ' ', (string) $record->paymentState())))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Paid', 'Confirmed', 'Completed' => 'success',
                        'Cancelled', 'Failed', 'Expired' => 'danger',
                        'Billed with main fee' => 'info',
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
