<?php

namespace App\Filament\Resources\ProtocolBookings\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProtocolBookingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Booking')
                    ->schema([
                        TextEntry::make('trans_id')->label('Reference')->copyable(),
                        TextEntry::make('ref_id')->label('Payment ref')->copyable(),
                        TextEntry::make('status')->badge()->color(fn (string $state): string => match ($state) {
                            'Successful' => 'success',
                            'Failed', 'Cancelled' => 'danger',
                            default => 'warning',
                        }),
                        TextEntry::make('paymentoption')->label('Payment method')->badge(),
                        TextEntry::make('created_at')->label('Booked')->dateTime(),
                    ])
                    ->columns(3),

                Section::make('Customer')
                    ->schema([
                        TextEntry::make('fullname')
                            ->label('Passenger name(s)')
                            ->formatStateUsing(function ($state): string {
                                if (is_string($state)) {
                                    $state = json_decode($state, true) ?? [$state];
                                }

                                return implode(', ', (array) ($state ?? []));
                            }),
                        TextEntry::make('email')->copyable(),
                        TextEntry::make('phone')->label('Phone')->copyable(),
                    ])
                    ->columns(3),

                Section::make('Service & Trip')
                    ->schema([
                        TextEntry::make('service_type')->label('Type'),
                        TextEntry::make('service'),
                        TextEntry::make('package'),
                        TextEntry::make('airline'),
                        TextEntry::make('airport'),
                        TextEntry::make('state'),
                        TextEntry::make('travel_date')->date(),
                        TextEntry::make('d_time')->label('Flight time'),
                        TextEntry::make('passenger')->label('Passengers')->numeric(),
                    ])
                    ->columns(3),

                Section::make('Travel Documents')
                    ->schema([
                        TextEntry::make('reservationCode')
                            ->label('PNR(s)')
                            ->formatStateUsing(fn ($state): string => implode(', ', array_filter((array) ($state ?? []))))
                            ->placeholder('-'),
                        TextEntry::make('eTicketNo')
                            ->label('E-ticket(s)')
                            ->formatStateUsing(fn ($state): string => implode(', ', array_filter((array) ($state ?? []))))
                            ->placeholder('-'),
                        TextEntry::make('noOfBags')
                            ->label('Bag(s)')
                            ->formatStateUsing(fn ($state): string => implode(', ', array_filter((array) ($state ?? []))))
                            ->placeholder('-'),
                        TextEntry::make('means_id')->label('Means of ID')->placeholder('-'),
                    ])
                    ->columns(4),

                Section::make('Optional Request')
                    ->schema([
                        TextEntry::make('optional_request')->label('Request')->placeholder('-'),
                        TextEntry::make('optionalRequestOption')->label('Vehicle')->placeholder('-'),
                        TextEntry::make('optionalRequestAddress')->label('Address')->placeholder('-'),
                    ])
                    ->columns(3),

                Section::make('Next of Kin')
                    ->schema([
                        TextEntry::make('nextOfKin_fullname')->label('Name')->placeholder('-'),
                        TextEntry::make('nextOfKin_phone')->label('Phone')->placeholder('-'),
                    ])
                    ->columns(2),

                Section::make('Pricing')
                    ->schema([
                        TextEntry::make('amount')->label('Total')->money('NGN'),
                        TextEntry::make('vat')->label('VAT')->money('NGN'),
                    ])
                    ->columns(2),
            ]);
    }
}
