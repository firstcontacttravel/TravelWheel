<?php

namespace App\Filament\Resources\FlightBookings\Schemas;

use App\Support\Admin\FlightBookingPresentation;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FlightBookingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Html::make(fn ($record) => FlightBookingPresentation::workspaceSummary($record))
                    ->columnSpanFull(),

                Grid::make([
                    'default' => 1,
                    'xl' => 12,
                ])
                    ->schema([
                        Section::make('Itinerary')
                            ->description('Booked journey, fare, and selected flight.')
                            ->schema([
                                Html::make(fn ($record) => FlightBookingPresentation::flight($record->flight_snapshot))
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan([
                                'default' => 1,
                                'xl' => 8,
                            ]),

                        Grid::make(1)
                            ->schema([
                                Section::make('Status')
                                    ->schema([
                                        Html::make(fn ($record) => FlightBookingPresentation::ticketStatusCard($record))
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1),

                                Section::make('Payment')
                                    ->schema([
                                        TextEntry::make('payment_status')
                                            ->label('Status')
                                            ->badge()
                                            ->color(fn (?string $state): string => match ($state) {
                                                'paid' => 'success',
                                                'failed' => 'danger',
                                                'awaiting_bank_transfer', 'pending' => 'warning',
                                                default => 'gray',
                                            })
                                            ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->headline()->toString() : '-'),
                                        TextEntry::make('payment_method')->label('Method')->badge()->placeholder('-')->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->headline()->toString() : '-'),
                                        TextEntry::make('payment_reference')->label('Reference')->copyable()->placeholder('-')->columnSpanFull(),
                                        TextEntry::make('payment_charged_amount')->label('Charged')->numeric()->placeholder('-'),
                                        TextEntry::make('payment_currency')->label('Currency')->placeholder('-'),
                                        TextEntry::make('payment_verified_at')->label('Verified')->dateTime()->placeholder('-'),
                                        IconEntry::make('payment_receipt_sent')->label('Receipt sent')->boolean(),
                                    ])
                                    ->columns(2),

                                Section::make('Pricing')
                                    ->schema([
                                        TextEntry::make('supplier_price')
                                            ->label('Supplier fare')
                                            ->state(fn ($record): string => self::money($record->supplier_price, $record->currency))
                                            ->placeholder('-'),
                                        TextEntry::make('markup_amount')
                                            ->label('Service charge')
                                            ->state(fn ($record): string => self::money($record->markup_amount, $record->currency))
                                            ->placeholder('-'),
                                        TextEntry::make('markup_category')
                                            ->label('Service charge category')
                                            ->badge()
                                            ->placeholder('-')
                                            ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->headline()->toString() : '-'),
                                        TextEntry::make('total_price')
                                            ->label('Customer total')
                                            ->state(fn ($record): string => self::money($record->total_price, $record->currency))
                                            ->placeholder('-'),
                                    ])
                                    ->columns(2),

                                Section::make('Customer')
                                    ->schema([
                                        TextEntry::make('booking_ref')->label('Booking ref')->copyable()->placeholder('-'),
                                        TextEntry::make('unique_id')->label('UniqueID')->copyable()->placeholder('-'),
                                        TextEntry::make('contact_email')->label('Email')->copyable()->placeholder('-')->columnSpanFull(),
                                        TextEntry::make('contact_phone')->label('Phone')->copyable()->placeholder('-'),
                                        TextEntry::make('adult_count')->label('Adults')->numeric(),
                                        TextEntry::make('child_count')->label('Children')->numeric(),
                                        TextEntry::make('infant_count')->label('Infants')->numeric(),
                                    ])
                                    ->columns(2),
                            ])
                            ->columnSpan([
                                'default' => 1,
                                'xl' => 4,
                            ]),
                    ])
                    ->columnSpanFull(),

                Section::make('Passengers')
                    ->description('Passenger snapshot captured during checkout.')
                    ->schema([
                        Html::make(fn ($record) => FlightBookingPresentation::passengers($record->passengers_snapshot))
                            ->columnSpanFull(),
                    ]),

                Grid::make([
                    'default' => 1,
                    'xl' => 2,
                ])
                    ->schema([
                        Section::make('Extra Services')
                            ->description('Baggage, meals, and service add-ons.')
                            ->schema([
                                Html::make(fn ($record) => FlightBookingPresentation::extras($record->extra_services_snapshot))
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Latest Trip Details')
                            ->description('Latest ticket status and airline reference.')
                            ->schema([
                                Html::make(fn ($record) => FlightBookingPresentation::latestTripDetails(
                                    $record->ticketingRecords()
                                        ->whereIn('action', ['trip_details_fetched', 'eticket_resent'])
                                        ->latest()
                                        ->first(),
                                ))
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Payment Verification History')
                            ->description('Manual and gateway verification audit trail.')
                            ->schema([
                                Html::make(fn ($record) => FlightBookingPresentation::paymentVerificationHistory(
                                    $record->paymentVerificationRecords()->with('verifier')->latest()->limit(10)->get(),
                                ))
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Section::make('Ticketing History')
                    ->description('Ticket order, trip details, alerts, and e-ticket actions.')
                    ->schema([
                        Html::make(fn ($record) => FlightBookingPresentation::ticketingHistory(
                            $record->ticketingRecords()->with('performer')->latest()->limit(10)->get(),
                        ))
                            ->columnSpanFull(),
                    ]),

                Section::make('Post-Ticketing Requests')
                    ->description('Refund, void, reissue, cancellation, and PTR status history.')
                    ->schema([
                        Html::make(fn ($record) => FlightBookingPresentation::postTicketingHistory(
                            $record->postTicketingRequests()->with('admin')->latest()->limit(10)->get(),
                        ))
                            ->columnSpanFull(),
                    ]),

            ]);
    }

    private static function money(mixed $amount, ?string $currency): string
    {
        return trim(($currency ?: 'NGN') . ' ' . number_format((float) $amount, 2));
    }
}
