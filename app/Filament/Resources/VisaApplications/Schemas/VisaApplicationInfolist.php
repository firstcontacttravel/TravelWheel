<?php

namespace App\Filament\Resources\VisaApplications\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VisaApplicationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Application overview')->schema([
                TextEntry::make('reference')->copyable()->weight('bold'), TextEntry::make('status')->badge(), TextEntry::make('product.name')->label('Visa product'),
                TextEntry::make('product.family')->label('Flow')->badge(), TextEntry::make('contact_email')->copyable(), TextEntry::make('assignee.name')->label('Assigned officer')->placeholder('Shared queue'),
                TextEntry::make('arrival_date')->date(), TextEntry::make('departure_date')->date(), TextEntry::make('processingOption.name')->label('Processing option')->placeholder('-'),
            ])->columns(3),
            Section::make('Travellers')->schema([
                RepeatableEntry::make('travelers')->hiddenLabel()->schema([
                    TextEntry::make('reference')->label('Reference')->copyable(), TextEntry::make('traveler_type')->badge(), TextEntry::make('applicant_type')->badge()->placeholder('-'),
                    TextEntry::make('first_name'), TextEntry::make('last_name'), TextEntry::make('date_of_birth')->date()->placeholder('-'), TextEntry::make('passport_number')->copyable()->placeholder('-'), TextEntry::make('passport_expires_at')->date()->placeholder('-'), TextEntry::make('email')->placeholder('-'),
                ])->columns(3),
            ]),
            Section::make('Application answers')->collapsed()->schema([
                RepeatableEntry::make('answers')->hiddenLabel()->schema([TextEntry::make('question.label')->label('Question'), TextEntry::make('value.answer')->label('Answer')->placeholder('-')])->columns(2),
            ]),
            Section::make('Uploaded documents')->schema([
                RepeatableEntry::make('documents')->hiddenLabel()->schema([
                    TextEntry::make('requirement.name')->label('Requirement'), TextEntry::make('traveler.first_name')->label('Traveller')->placeholder('Application'), TextEntry::make('original_name')->url(fn ($record) => route('admin.visa.documents.application', $record))->openUrlInNewTab(), TextEntry::make('status')->badge(), TextEntry::make('review_note')->placeholder('-'),
                ])->columns(5),
            ]),
            Section::make('Additional-document requests')->schema([
                RepeatableEntry::make('additionalDocumentRequests')->hiddenLabel()->schema([TextEntry::make('title'), TextEntry::make('traveler.first_name')->label('Traveller')->placeholder('Application'), TextEntry::make('status')->badge(), TextEntry::make('due_at')->dateTime()->placeholder('-'), TextEntry::make('original_name')->label('Uploaded file')->placeholder('-')->url(fn ($record) => $record->path ? route('admin.visa.documents.requested', $record) : null)->openUrlInNewTab(), TextEntry::make('review_note')->placeholder('-')])->columns(3),
            ]),
            Section::make('Issued visa documents')->schema([
                RepeatableEntry::make('issuedDocuments')->hiddenLabel()->schema([TextEntry::make('version')->badge(), TextEntry::make('original_name')->url(fn ($record) => route('admin.visa.documents.issued', $record))->openUrlInNewTab(), TextEntry::make('issuer.name')->label('Issued by')->placeholder('System'), TextEntry::make('issued_at')->dateTime(), TextEntry::make('superseded_at')->dateTime()->placeholder('Current')])->columns(5),
            ]),
            Section::make('Pricing and payment')->schema([
                RepeatableEntry::make('payments')->hiddenLabel()->schema([TextEntry::make('reference')->copyable(), TextEntry::make('status')->badge(), TextEntry::make('expected_amount')->money(fn ($record) => $record->expected_currency), TextEntry::make('verified_amount')->money(fn ($record) => $record->verified_currency)->placeholder('-'), TextEntry::make('verified_at')->dateTime()->placeholder('-')])->columns(5),
            ]),
            Section::make('Internal notes')->schema([
                RepeatableEntry::make('internalNotes')->hiddenLabel()->schema([TextEntry::make('user.name')->label('Author')->placeholder('Former user'), TextEntry::make('body'), TextEntry::make('created_at')->since()])->columns(3),
            ]),
            Section::make('Status and audit history')->schema([
                RepeatableEntry::make('statusHistory')->hiddenLabel()->schema([TextEntry::make('to_status')->label('Status')->badge(), TextEntry::make('reason')->label('Public note')->placeholder('-'), TextEntry::make('metadata.internal_note')->label('Internal note')->placeholder('-'), TextEntry::make('created_at')->dateTime()])->columns(4),
                RepeatableEntry::make('auditEvents')->hiddenLabel()->schema([TextEntry::make('event_type')->badge(), TextEntry::make('summary'), TextEntry::make('user.name')->label('Actor')->placeholder('System'), TextEntry::make('created_at')->dateTime()])->columns(4),
            ])->collapsed(),
        ]);
    }
}
