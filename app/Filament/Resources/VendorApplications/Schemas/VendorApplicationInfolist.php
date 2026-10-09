<?php

namespace App\Filament\Resources\VendorApplications\Schemas;

use App\Filament\Resources\VendorApplications\VendorApplicationResource;
use App\Models\VendorApplication;
use App\Models\VendorApplicationDocument;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VendorApplicationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Review')->schema([
                TextEntry::make('reference')->copyable()->weight('bold'),
                TextEntry::make('status')->badge()
                    ->formatStateUsing(fn (string $state) => VendorApplication::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => VendorApplication::STATUS_COLORS[$state] ?? 'gray'),
                TextEntry::make('vendor_code')->label('Vendor ID')->placeholder('Assigned on approval')->copyable(),
                TextEntry::make('created_at')->label('Submitted')->dateTime('j M Y, g:i A'),
                TextEntry::make('documents_verified_at')->label('Documents verified')->placeholder('-')
                    ->formatStateUsing(fn ($state, VendorApplication $record) => $state->format('j M Y').' by '.($record->documentsVerifiedBy?->name ?? 'former user')),
                TextEntry::make('compliance_completed_at')->label('Compliance completed')->placeholder('-')
                    ->formatStateUsing(fn ($state, VendorApplication $record) => $state->format('j M Y').' by '.($record->complianceCompletedBy?->name ?? 'former user')),
                TextEntry::make('approved_at')->label('Approved')->placeholder('-')
                    ->formatStateUsing(fn ($state, VendorApplication $record) => $state->format('j M Y').' by '.($record->approvedBy?->name ?? 'former user')),
                TextEntry::make('rejected_at')->label('Rejected')->placeholder('-')
                    ->formatStateUsing(fn ($state, VendorApplication $record) => $state->format('j M Y').' by '.($record->rejectedBy?->name ?? 'former user')),
                TextEntry::make('approved_services')->label('Approved services')->placeholder('-')
                    ->state(fn (VendorApplication $record) => $record->approved_services ? array_values($record->serviceLabels($record->approved_services)) : null)
                    ->badge()->color('success'),
                TextEntry::make('risk_rating')->placeholder('-')
                    ->formatStateUsing(fn ($state) => VendorApplication::RISK_RATINGS[$state] ?? $state)
                    ->badge()->color(fn ($state) => ['low' => 'success', 'medium' => 'warning', 'high' => 'danger'][$state] ?? 'gray'),
                TextEntry::make('next_review_on')->label('Next review')->date('j M Y')->placeholder('-'),
                TextEntry::make('decision_note')->label('Last note to vendor')->placeholder('-')->columnSpanFull(),
                TextEntry::make('internal_notes')->placeholder('-')->columnSpanFull(),
            ])->columns(4),

            Section::make('Company & business information')->schema([
                TextEntry::make('registered_name')->label('Registered name')->weight('bold'),
                TextEntry::make('trading_name')->placeholder('-'),
                TextEntry::make('registration_number')->label('Registration no.')->copyable(),
                TextEntry::make('tax_id')->label('TIN')->placeholder('-')->copyable(),
                TextEntry::make('country'),
                TextEntry::make('year_established')->placeholder('-'),
                TextEntry::make('website')->placeholder('-')
                    ->url(fn ($state) => $state ? (str_starts_with($state, 'http') ? $state : "https://$state") : null)->openUrlInNewTab(),
                TextEntry::make('business_email')->copyable(),
                TextEntry::make('business_phone')->copyable(),
                TextEntry::make('address')->columnSpan(2),
                TextEntry::make('locations_served')->columnSpan(2),
                TextEntry::make('business_types')->label('Business type')
                    ->state(fn (VendorApplication $record) => $record->businessTypeLabels())->badge()->color('gray')->columnSpan(2),
                TextEntry::make('services')->label('Services applied for')
                    ->state(fn (VendorApplication $record) => array_values($record->serviceLabels()))->badge()->columnSpan(2),
                TextEntry::make('service_other')->label('Other services')->placeholder('-'),
                TextEntry::make('works_with_other_platforms')->label('Works with other B2B platforms')
                    ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
                TextEntry::make('other_platforms_details')->label('Details')->placeholder('-')->columnSpan(2),
            ])->columns(4)->collapsible(),

            Section::make('Contacts')->schema([
                TextEntry::make('contact_name')->label('Authorised contact')
                    ->state(fn (VendorApplication $record) => "{$record->contact_name}, {$record->contact_title}"),
                TextEntry::make('contact_email')->label('Email')->copyable(),
                TextEntry::make('contact_phone')->label('Phone / WhatsApp')->copyable(),
                TextEntry::make('operations_name')->label('Operations / 24-hour')->placeholder('-'),
                TextEntry::make('operations_email')->label('Email')->placeholder('-')->copyable(),
                TextEntry::make('operations_phone')->label('Phone')->placeholder('-')->copyable(),
                TextEntry::make('accounts_name')->label('Accounts')->placeholder('-'),
                TextEntry::make('accounts_email')->label('Email')->placeholder('-')->copyable(),
                TextEntry::make('accounts_phone')->label('Phone')->placeholder('-')->copyable(),
            ])->columns(3)->collapsible(),

            ...self::serviceSections(),

            Section::make('Bookings & payment')->schema([
                TextEntry::make('booking_channels')->label('Receives bookings by')
                    ->state(fn (VendorApplication $record) => array_map(fn ($key) => config("vendor_onboarding.booking_channels.$key", $key), $record->booking_channels ?? []))
                    ->badge()->color('gray'),
                TextEntry::make('booking_email')->label('Booking email')->copyable(),
                TextEntry::make('confirmation_time')->label('Confirms bookings')->placeholder('-'),
                TextEntry::make('rate_model')->formatStateUsing(fn ($state) => config("vendor_onboarding.rate_models.$state", $state)),
                TextEntry::make('settlement_currency'),
                TextEntry::make('payment_terms')->placeholder('-'),
                TextEntry::make('bank_name')->label('Bank')
                    ->state(fn (VendorApplication $record) => self::bank($record, $record->bank_name)),
                TextEntry::make('account_name')
                    ->state(fn (VendorApplication $record) => self::bank($record, $record->account_name)),
                TextEntry::make('account_number')
                    ->state(fn (VendorApplication $record) => self::bank($record, $record->account_number, mask: true))
                    ->copyable(fn () => VendorApplicationResource::canSeeBankDetails(auth()->user())),
                TextEntry::make('references')->placeholder('-')->columnSpanFull(),
            ])->columns(3)->collapsible(),

            Section::make('Documents')
                ->description('Click a file to open it. Expired documents are shown in red.')
                ->schema([
                    RepeatableEntry::make('documents')->hiddenLabel()->schema([
                        TextEntry::make('label')->label('Document')
                            ->formatStateUsing(fn (string $state, VendorApplicationDocument $record) => $record->service
                                ? $state.' ('.config("vendor_onboarding.services.{$record->service}.label").')'
                                : $state),
                        TextEntry::make('original_name')->label('File')
                            ->url(fn (VendorApplicationDocument $record) => route('admin.vendor-documents.show', $record))
                            ->openUrlInNewTab()->color('primary')->icon('heroicon-o-eye'),
                        TextEntry::make('expires_on')->label('Expires')->date('j M Y')->placeholder('-')
                            ->color(fn (VendorApplicationDocument $record) => $record->isExpired() ? 'danger' : null),
                        TextEntry::make('status')->badge()
                            ->formatStateUsing(fn ($state) => VendorApplicationDocument::STATUSES[$state] ?? $state)
                            ->color(fn ($state) => ['verified' => 'success', 'rejected' => 'danger'][$state] ?? 'gray'),
                        TextEntry::make('review_note')->label('Note')->placeholder('-'),
                    ])->columns(5),
                ]),

            Section::make('Declaration')->schema([
                TextEntry::make('declarant_name')->label('Signed by')
                    ->state(fn (VendorApplication $record) => "{$record->declarant_name}, {$record->declarant_title}"),
                TextEntry::make('declared_at')->label('Signed')->dateTime('j M Y, g:i A'),
                TextEntry::make('submitted_ip')->label('From IP address')->placeholder('-'),
            ])->columns(3)->collapsed(),
        ]);
    }

    /** One section per service in the config, shown only when the vendor applied for it. */
    private static function serviceSections(): array
    {
        $sections = [];

        foreach (config('vendor_onboarding.services') as $service => $config) {
            $entries = [];
            foreach ($config['fields'] as $field => $def) {
                $entries[] = TextEntry::make("service_details.$service.$field")
                    ->label($def['label'])
                    ->state(fn (VendorApplication $record) => self::answer($record->service_details[$service][$field] ?? null))
                    ->placeholder('-')
                    ->columnSpan(in_array($def['type'], ['textarea', 'checkboxes'], true) ? 2 : 1);
            }

            $sections[] = Section::make($config['label'])
                ->schema($entries)
                ->columns(4)
                ->collapsible()
                ->visible(fn (?VendorApplication $record) => in_array($service, $record?->services ?? [], true));
        }

        return $sections;
    }

    private static function answer(mixed $value): ?string
    {
        return match (true) {
            is_array($value) => $value === [] ? null : implode(', ', $value),
            $value === 'yes' => 'Yes',
            $value === 'no' => 'No',
            blank($value) => null,
            default => (string) $value,
        };
    }

    private static function bank(VendorApplication $record, ?string $value, bool $mask = false): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (VendorApplicationResource::canSeeBankDetails(auth()->user())) {
            return $value;
        }

        return $mask ? str_repeat('•', max(strlen($value) - 4, 0)).substr($value, -4) : 'Visible to finance';
    }
}
