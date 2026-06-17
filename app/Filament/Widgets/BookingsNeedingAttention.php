<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\FlightBookings\FlightBookingResource;
use App\Models\FlightBooking;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class BookingsNeedingAttention extends TableWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Bookings Needing Attention')
            ->description('Payment and ticketing records that should be reviewed first.')
            ->query(
                FlightBooking::query()
                    ->where(function (Builder $query): void {
                        $query
                            ->where('payment_status', 'awaiting_bank_transfer')
                            ->orWhere(function (Builder $query): void {
                                $query
                                    ->where('payment_status', 'paid')
                                    ->where('ticket_ordered', false);
                            })
                            ->orWhere(function (Builder $query): void {
                                $query
                                    ->where('payment_status', 'paid')
                                    ->whereIn('booking_status', ['failed', 'ticketing_failed']);
                            });
                    })
                    ->latest()
            )
            ->columns([
                TextColumn::make('booking_ref')
                    ->label('Booking')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),
                TextColumn::make('route')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('airline')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('queue')
                    ->label('Queue')
                    ->badge()
                    ->state(fn (FlightBooking $record): string => self::queueLabel($record))
                    ->color(fn (string $state): string => match ($state) {
                        'Awaiting transfer' => 'warning',
                        'Ticketing failed' => 'danger',
                        'Ready to ticket' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('total_price')
                    ->label('Amount')
                    ->formatStateUsing(fn (FlightBooking $record): string => self::money($record->total_price, $record->currency))
                    ->description(fn (FlightBooking $record): string => (float) $record->markup_amount > 0
                        ? 'Service charge: ' . self::money($record->markup_amount, $record->currency)
                        : 'No service charge'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->since()
                    ->sortable(),
            ])
            ->recordUrl(fn (FlightBooking $record): string => FlightBookingResource::getUrl('view', ['record' => $record]))
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10]);
    }

    private static function queueLabel(FlightBooking $record): string
    {
        if ($record->payment_status === 'awaiting_bank_transfer') {
            return 'Awaiting transfer';
        }

        if ($record->payment_status === 'paid' && in_array($record->booking_status, ['failed', 'ticketing_failed'], true)) {
            return 'Ticketing failed';
        }

        if ($record->payment_status === 'paid' && ! $record->ticket_ordered) {
            return 'Ready to ticket';
        }

        return 'Review';
    }

    private static function money(mixed $amount, ?string $currency): string
    {
        return trim(($currency ?: 'NGN') . ' ' . number_format((float) $amount, 2));
    }
}
