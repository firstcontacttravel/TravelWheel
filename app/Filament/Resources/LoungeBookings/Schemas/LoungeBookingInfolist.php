<?php

namespace App\Filament\Resources\LoungeBookings\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LoungeBookingInfolist
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
                        TextEntry::make('payment_option')->label('Payment method')->badge(),
                        TextEntry::make('created_at')->label('Booked')->dateTime(),
                    ])
                    ->columns(3),

                Section::make('Customer')
                    ->schema([
                        TextEntry::make('fullname')->label('Name'),
                        TextEntry::make('email')->copyable(),
                        TextEntry::make('phone_no')->label('Phone')->copyable(),
                    ])
                    ->columns(3),

                Section::make('Lounge & Trip')
                    ->schema([
                        TextEntry::make('lounge_name')->label('Lounge'),
                        TextEntry::make('terminal'),
                        TextEntry::make('service'),
                        TextEntry::make('airline'),
                        TextEntry::make('travel_date')->date(),
                        TextEntry::make('d_time')->label('Departure time'),
                    ])
                    ->columns(3),

                Section::make('Passengers')
                    ->schema([
                        TextEntry::make('nop')->label('Total passengers')->numeric(),
                        TextEntry::make('noa')->label('Adults')->numeric(),
                        TextEntry::make('noc')->label('Children')->numeric(),
                        TextEntry::make('noi')->label('Infants')->numeric(),
                    ])
                    ->columns(4),

                Section::make('Pricing')
                    ->schema([
                        TextEntry::make('amount')->label('Total')->money('NGN'),
                        TextEntry::make('amountA')->label('Adult amount')->money('NGN')->placeholder('-'),
                        TextEntry::make('amountC')->label('Child amount')->money('NGN')->placeholder('-'),
                        TextEntry::make('vat')->label('VAT')->money('NGN'),
                    ])
                    ->columns(4),
            ]);
    }
}
