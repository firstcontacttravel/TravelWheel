<?php

namespace App\Filament\Resources\TravelFlexApplications\Schemas;

use App\Support\Admin\FlightBookingPresentation;
use App\Support\Admin\TravelFlexPresentation;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TravelFlexApplicationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Html::make(fn ($record) => TravelFlexPresentation::workspaceSummary($record))
                    ->columnSpanFull(),

                Section::make('Application')
                    ->description('Application, provider, payment, and review state.')
                    ->schema([
                        TextEntry::make('booking_ref')->copyable()->placeholder('-'),
                        TextEntry::make('unique_id')->label('UniqueID')->copyable()->placeholder('-'),
                        TextEntry::make('application_status')->badge(),
                        TextEntry::make('financing_status')->label('Fast Credit decision')->badge(),
                        TextEntry::make('provider_status')->badge(),
                        TextEntry::make('payment_status')->badge(),
                        TextEntry::make('deposit_status')->badge(),
                        TextEntry::make('approval_expires_at')->dateTime()->placeholder('-'),
                        TextEntry::make('provider_email_sent_at')->dateTime()->placeholder('-'),
                        TextEntry::make('reviewer.name')->label('Reviewed by')->placeholder('-'),
                        TextEntry::make('reviewed_at')->dateTime()->placeholder('-'),
                        TextEntry::make('admin_note')->placeholder('-')->columnSpanFull(),
                        TextEntry::make('provider_email_error')->placeholder('-')->columnSpanFull(),
                    ])
                    ->columns(3),

                Section::make('Provider Handoff')
                    ->description('Provider package readiness, email status, and review handoff.')
                    ->schema([
                        Html::make(fn ($record) => TravelFlexPresentation::providerHandoff($record))->columnSpanFull(),
                    ]),

                Section::make('Applicant')
                    ->schema([
                        Html::make(fn ($record) => TravelFlexPresentation::applicant($record))->columnSpanFull(),
                    ]),

                Section::make('Employment')
                    ->schema([
                        Html::make(fn ($record) => TravelFlexPresentation::employment($record))->columnSpanFull(),
                    ]),

                Section::make('Repayment Plan')
                    ->schema([
                        Html::make(fn ($record) => TravelFlexPresentation::plan($record))->columnSpanFull(),
                    ]),

                Section::make('Flight Itinerary')
                    ->description('Complete held itinerary captured when the customer submitted the TravelFlex application.')
                    ->schema([
                        Html::make(fn ($record) => FlightBookingPresentation::flight(
                            $record->booking?->flight_snapshot ?: $record->booking?->itinerary_snapshot
                        ))
                            ->columnSpanFull(),
                    ]),

                Section::make('Documents')
                    ->description('Uploaded documents are stored locally and should be treated as sensitive.')
                    ->schema([
                        Html::make(fn ($record) => TravelFlexPresentation::documents($record))->columnSpanFull(),
                    ]),

                Section::make('Linked Booking')
                    ->description('Flight booking connected to this TravelFlex application.')
                    ->schema([
                        TextEntry::make('booking.booking_ref')->label('Booking ref')->copyable()->placeholder('-'),
                        TextEntry::make('booking.route')->placeholder('-'),
                        TextEntry::make('booking.airline')->placeholder('-'),
                        TextEntry::make('booking.booking_status')->badge()->placeholder('-'),
                        TextEntry::make('booking.payment_status')->badge()->placeholder('-'),
                        TextEntry::make('booking.contact_email')->copyable()->placeholder('-'),
                    ])
                    ->columns(3),
            ]);
    }
}
