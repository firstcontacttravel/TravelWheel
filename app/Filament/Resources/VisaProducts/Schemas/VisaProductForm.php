<?php

namespace App\Filament\Resources\VisaProducts\Schemas;

use App\Enums\VisaEligibilityMode;
use App\Enums\VisaProductFamily;
use App\Enums\VisaPublicationStatus;
use App\Models\Country;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class VisaProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make([
                Step::make('Basics')->description('Destination and customer-facing details')->icon('heroicon-o-identification')->schema([
                    Section::make('Product identity')->description('Start with the information customers use to recognize and compare this visa.')->schema([
                        Select::make('destination_country_id')->label('Destination')->options(fn () => self::countries())->searchable()->preload()->required(),
                        TextInput::make('name')->required()->maxLength(255)->live(onBlur: true)->afterStateUpdated(fn (?string $state, Set $set) => $set('slug', Str::slug($state ?? ''))),
                        TextInput::make('slug')->required()->unique(ignoreRecord: true)->maxLength(255)->helperText('Generated from the name. Keep it stable after publication.'),
                        Select::make('family')->options(VisaProductFamily::options())->default('standard')->required(),
                        Select::make('category')->options(self::categories())->searchable()->required(),
                        Select::make('entry_type')->options(['single' => 'Single entry', 'multiple' => 'Multiple entry', 'transit' => 'Transit', 'other' => 'Other'])->default('single')->required(),
                        TextInput::make('validity_days')->numeric()->minValue(1)->suffix('days'),
                        TextInput::make('maximum_stay_days')->numeric()->minValue(1)->suffix('days'),
                        DateTimePicker::make('effective_from')->label('Available from')->helperText('Optional product availability date.'),
                        DateTimePicker::make('effective_until')->label('Available until')->after('effective_from')->helperText('Leave empty for no scheduled end date.'),
                        Select::make('publication_status')->options(VisaPublicationStatus::options())->default('draft')->disabled()->dehydrated(),
                        TextInput::make('version')->numeric()->disabled()->dehydrated()->default(1),
                    ])->columns(2),
                    Section::make('Customer content')->description('Optional explanations shown to customers.')->schema([
                        Textarea::make('summary')->rows(2)->columnSpanFull(),
                        Textarea::make('description')->rows(4)->columnSpanFull(),
                        Textarea::make('processing_disclaimer')->rows(2),
                        Textarea::make('issuance_disclaimer')->rows(2),
                        Textarea::make('important_notes')->rows(2)->columnSpanFull(),
                    ])->columns(2)->collapsible()->collapsed(),
                ]),

                Step::make('Eligibility')->description('Choose who can apply')->icon('heroicon-o-globe-alt')->schema([
                    Section::make('Nationality eligibility')->description('Use All nationalities unless approved restrictions exist. Exclusions override inclusions.')->schema([
                        Select::make('eligibility_mode')->options(VisaEligibilityMode::options())->default('all')->required()->live(),
                    ]),
                    Section::make('Eligibility rules')->description('Rules are only needed when eligibility mode is Rules.')->visible(fn (Get $get) => $get('eligibility_mode') === 'rules')->schema([
                        Repeater::make('eligibilityRules')->relationship()->defaultItems(0)->label('Rules')->schema([
                            Select::make('rule_type')->label('Rule')->options([
                                'include_country' => 'Include one country', 'exclude_country' => 'Exclude one country',
                                'include_group' => 'Include a country group', 'exclude_group' => 'Exclude a country group',
                                'residence_country' => 'Require residence country', 'manual_review' => 'Require manual review',
                            ])->required()->live(),
                            Select::make('country_id')->label('Country')->options(fn () => self::countries())->searchable()->preload()->visible(fn (Get $get) => in_array($get('rule_type'), ['include_country', 'exclude_country', 'residence_country'], true)),
                            Select::make('country_group_id')->label('Country group')->relationship('countryGroup', 'name', modifyQueryUsing: fn ($query) => $query->where('is_active', true))->searchable()->preload()->createOptionForm([
                                TextInput::make('name')->required()->live(onBlur: true)->afterStateUpdated(fn (?string $state, Set $set) => $set('slug', Str::slug($state ?? ''))),
                                Hidden::make('slug'),
                                Select::make('countries')->relationship('countries', 'name', modifyQueryUsing: fn ($query) => $query->where('is_active', true))->multiple()->searchable()->preload()->required(),
                                Hidden::make('version')->default(1), Hidden::make('is_active')->default(true),
                            ])->noSearchResultsMessage('No groups yet. Create one here or under Visa Catalogue > Country Groups.')->visible(fn (Get $get) => in_array($get('rule_type'), ['include_group', 'exclude_group'], true)),
                            Textarea::make('public_message')->label('Customer message')->rows(2)->columnSpanFull(),
                            Toggle::make('is_active')->label('Rule is active')->default(true),
                        ])->columns(2)->orderColumn('sort_order')->collapsible()->itemLabel(fn (array $state): ?string => isset($state['rule_type']) ? Str::headline($state['rule_type']) : null)->columnSpanFull(),
                    ]),
                ]),

                Step::make('Processing & pricing')->description('Timelines and transparent fees')->icon('heroicon-o-banknotes')->schema([
                    Section::make('Processing options')->description('Add options first. They immediately become available in fee components below.')->schema([
                        Repeater::make('processingOptions')->relationship()->defaultItems(0)->live()->schema([
                            Hidden::make('code')->default(fn () => (string) Str::uuid()),
                            TextInput::make('name')->required(),
                            TextInput::make('minimum_business_days')->label('Minimum business days')->numeric()->minValue(0)->required(),
                            TextInput::make('maximum_business_days')->label('Maximum business days')->numeric()->minValue(0)->gte('minimum_business_days')->required(),
                            Textarea::make('description')->rows(2)->columnSpanFull(),
                            Toggle::make('is_active')->label('Option is available')->default(true),
                        ])->columns(2)->orderColumn('sort_order')->collapsible()->itemLabel(fn (array $state): ?string => $state['name'] ?? null)->columnSpanFull(),
                    ]),
                    Section::make('Fee components')->description('Add one clear line item for each amount. Authority-direct fees are not collected online.')->schema([
                        Repeater::make('fees')->relationship()->defaultItems(0)->schema([
                            TextInput::make('name')->required(),
                            Select::make('fee_type')->options(['government' => 'Government or visa fee', 'biometrics' => 'Biometrics', 'service' => 'TravelWheel service', 'processing' => 'Processing surcharge', 'payment' => 'Payment processing', 'document' => 'Document handling', 'other' => 'Other'])->required(),
                            Select::make('traveler_type')->options(['all' => 'All travelers', 'adult' => 'Adult', 'child' => 'Child', 'infant' => 'Infant'])->default('all')->required(),
                            Select::make('calculation_basis')->options(['per_traveler' => 'Per applicable traveler', 'per_application' => 'Once per application'])->default('per_traveler')->required(),
                            Select::make('processing_option_code')->label('Processing option')->options(fn (Get $get): array => self::namedCodes($get('../../processingOptions')))->placeholder('All processing options')->helperText('Leave empty when the fee applies to every option.'),
                            Select::make('currency')->options(self::currencies())->default('NGN')->searchable()->required(),
                            TextInput::make('amount')->numeric()->minValue(0)->required(),
                            Select::make('payee')->options(['travelwheel' => 'TravelWheel', 'authority' => 'Authority or embassy'])->default('travelwheel')->required()->live(),
                            Toggle::make('pay_online')->label('Collect online')->default(true)->disabled(fn (Get $get) => $get('payee') === 'authority')->dehydrateStateUsing(fn ($state, Get $get) => $get('payee') === 'authority' ? false : (bool) $state),
                            Select::make('conditions.nationality_country_id')->label('Only for nationality')->options(fn () => self::countries())->searchable()->preload()->placeholder('All nationalities')->helperText('Use only when this fee changes for one nationality.')->dehydrated(fn ($state) => filled($state)),
                            Toggle::make('is_active')->label('Fee is active')->default(true),
                        ])->columns(2)->orderColumn('sort_order')->collapsible()->itemLabel(fn (array $state): ?string => $state['name'] ?? null)->columnSpanFull(),
                    ]),
                ]),

                Step::make('Optional services')->description('TravelWheel assistance offered before uploads')->icon('heroicon-o-sparkles')->schema([
                    Section::make('Optional TravelWheel services')->description('Service-specific document requirements are configured in the next step.')->schema([
                        Repeater::make('optionalServices')->relationship()->defaultItems(0)->live()->schema([
                            Hidden::make('code')->default(fn () => (string) Str::uuid()),
                            Select::make('service_type')->options(['flight' => 'Flight assistance', 'hotel' => 'Hotel assistance', 'insurance' => 'Travel insurance', 'document' => 'Document assistance', 'other' => 'Other'])->required(),
                            TextInput::make('name')->required(),
                            Textarea::make('description')->rows(2)->columnSpanFull(),
                            Textarea::make('customer_disclaimer')->rows(2)->columnSpanFull(),
                            Select::make('pricing_model')->options(['fixed' => 'Fixed amount', 'per_traveler' => 'Per traveler', 'external_quote' => 'External quote', 'included' => 'Included'])->default('fixed')->required()->live(),
                            Select::make('currency')->options(self::currencies())->searchable()->visible(fn (Get $get) => in_array($get('pricing_model'), ['fixed', 'per_traveler'], true)),
                            TextInput::make('amount')->numeric()->minValue(0)->visible(fn (Get $get) => in_array($get('pricing_model'), ['fixed', 'per_traveler'], true)),
                            Toggle::make('is_active')->label('Service is available')->default(true),
                        ])->columns(2)->orderColumn('sort_order')->collapsible()->itemLabel(fn (array $state): ?string => $state['name'] ?? null)->columnSpanFull(),
                    ]),
                ]),

                Step::make('Requirements')->description('Visa and selected-service uploads')->icon('heroicon-o-document-text')->schema([
                    Section::make('Document requirements')->description('Link a requirement to a service when it should appear only after that service is selected.')->schema([
                        Repeater::make('requirements')->relationship()->defaultItems(0)->schema([
                            Select::make('optional_service_code')->label('Related TravelWheel service')->options(fn (Get $get): array => self::namedCodes($get('../../optionalServices')))->placeholder('General visa requirement')->helperText('A linked upload appears only when the customer chooses that service.'),
                            TextInput::make('name')->required(),
                            Select::make('category')->options(['passport' => 'Passport', 'identity' => 'Identity', 'financial' => 'Financial', 'employment' => 'Employment', 'education' => 'Education', 'health' => 'Health', 'travel' => 'Travel', 'supporting_document' => 'Supporting document', 'other' => 'Other'])->default('supporting_document')->required(),
                            Select::make('scope')->options(['traveler' => 'Per traveler', 'application' => 'Per application'])->default('traveler')->required(),
                            Select::make('requirement_state')->options(['required' => 'Required', 'optional' => 'Optional', 'conditional' => 'Conditional'])->default('required')->required(),
                            Textarea::make('description')->rows(2)->columnSpanFull(),
                            TagsInput::make('accepted_mime_types')->label('Accepted formats')->suggestions(['application/pdf', 'image/jpeg', 'image/png']),
                            TextInput::make('maximum_file_size_kb')->label('Maximum size')->numeric()->minValue(1)->default(10240)->suffix('KB'),
                            TextInput::make('minimum_validity_days')->numeric()->minValue(0)->suffix('days'),
                            Textarea::make('guidance')->rows(2)->columnSpanFull(),
                            Select::make('conditions.traveler_type')->label('Only for traveler type')->options(['adult' => 'Adult', 'child' => 'Child', 'infant' => 'Infant'])->multiple()->placeholder('All traveler types')->dehydrated(fn ($state) => filled($state)),
                            Select::make('conditions.applicant_type')->label('Only for applicant type')->options(['individual' => 'Adult individual', 'company' => 'Company applicant', 'minor_nigerian' => 'Nigerian minor', 'minor_foreign' => 'Foreign minor'])->multiple()->placeholder('All applicant types')->dehydrated(fn ($state) => filled($state)),
                            Toggle::make('is_active')->label('Requirement is active')->default(true),
                        ])->columns(2)->orderColumn('sort_order')->collapsible()->itemLabel(fn (array $state): ?string => $state['name'] ?? null)->columnSpanFull(),
                    ]),
                ]),

                Step::make('Questions')->description('Only information not already collected')->icon('heroicon-o-list-bullet')->schema([
                    Section::make('Application questions')->description('Trip, traveler, contact, and passport details already exist. Add only genuinely additional questions.')->schema([
                        Repeater::make('questions')->relationship()->defaultItems(0)->schema([
                            TextInput::make('label')->label('Question')->required()->live(onBlur: true)->afterStateUpdated(fn (?string $state, Set $set) => $set('key', Str::slug($state ?? '', '_'))),
                            Hidden::make('key')->default(fn () => 'question_'.Str::lower(Str::random(8))),
                            Select::make('section')->options(['additional' => 'Additional information', 'employment' => 'Employment', 'travel_history' => 'Travel history', 'host' => 'Host or sponsor', 'family' => 'Family information', 'health' => 'Health information', 'other' => 'Other'])->default('additional')->required(),
                            Select::make('input_type')->label('Answer format')->options(['text' => 'Short text', 'textarea' => 'Long text', 'email' => 'Email address', 'tel' => 'Phone number', 'number' => 'Number', 'date' => 'Date', 'select' => 'Dropdown options', 'checkbox' => 'Single checkbox'])->default('text')->required()->live(),
                            Select::make('scope')->label('Ask this question')->options(['application' => 'Once for the application', 'traveler' => 'For each traveler'])->default('application')->required(),
                            Toggle::make('is_required')->label('Customer must answer')->default(false),
                            Textarea::make('help_text')->label('Help text')->rows(2)->columnSpanFull(),
                            TagsInput::make('options')->label('Dropdown choices')->helperText('Type a choice and press Enter.')->visible(fn (Get $get) => $get('input_type') === 'select')->required(fn (Get $get) => $get('input_type') === 'select')->columnSpanFull(),
                            Toggle::make('is_active')->label('Question is active')->default(true),
                        ])->columns(2)->orderColumn('sort_order')->collapsible()->itemLabel(fn (array $state): ?string => $state['label'] ?? null)->columnSpanFull(),
                    ]),
                ]),
            ])->columnSpanFull(),
        ]);
    }

    private static function countries(): array
    {
        return Country::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all();
    }

    private static function namedCodes(mixed $items): array
    {
        return collect($items ?? [])->filter(fn ($item) => filled($item['code'] ?? null) && filled($item['name'] ?? null))->mapWithKeys(fn ($item) => [$item['code'] => $item['name']])->all();
    }

    private static function categories(): array
    {
        return ['tourist' => 'Tourist', 'business' => 'Business', 'study' => 'Study', 'work' => 'Work', 'transit' => 'Transit', 'family' => 'Family', 'medical' => 'Medical', 'conference' => 'Conference or event', 'other' => 'Other'];
    }

    private static function currencies(): array
    {
        return ['NGN' => 'NGN - Nigerian Naira', 'USD' => 'USD - US Dollar', 'GBP' => 'GBP - British Pound', 'EUR' => 'EUR - Euro', 'CAD' => 'CAD - Canadian Dollar', 'AUD' => 'AUD - Australian Dollar', 'ZAR' => 'ZAR - South African Rand', 'AED' => 'AED - UAE Dirham'];
    }
}
