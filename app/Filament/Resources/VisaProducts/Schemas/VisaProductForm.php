<?php

namespace App\Filament\Resources\VisaProducts\Schemas;

use App\Enums\VisaEligibilityMode;
use App\Enums\VisaProductFamily;
use App\Enums\VisaPublicationStatus;
use App\Models\Country;
use App\Services\VisaFormWorkflow;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
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
use Illuminate\Support\HtmlString;

class VisaProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make([
                Step::make('Basics')->description('Destination and customer-facing details')->icon('heroicon-o-identification')->schema([
                    Section::make('Product identity')->description('Start with the information customers use to recognize and compare this visa.')->schema([
                        Select::make('destination_country_id')->label('Country destination')->options(fn () => self::countries())->searchable()->preload()->live()->required(fn (Get $get) => blank($get('visa_destination_id')))->afterStateUpdated(fn ($state, Set $set) => filled($state) ? $set('visa_destination_id', null) : null)->helperText('Choose a country, or leave this empty and choose a regional destination.'),
                        Select::make('visa_destination_id')->label('Regional destination')->relationship('destination', 'name', modifyQueryUsing: fn ($query) => $query->where('is_active', true))->searchable()->preload()->live()->required(fn (Get $get) => blank($get('destination_country_id')))->afterStateUpdated(fn ($state, Set $set) => filled($state) ? $set('destination_country_id', null) : null)->helperText('For products such as a Schengen visa. Choose either a country or a region, never both.'),
                        Select::make('visa_vendor_id')->label('Vendor')->relationship('vendor', 'name', modifyQueryUsing: fn ($query) => $query->where('is_active', true))->searchable()->preload()->required()->helperText('Internal only. Customers will not see the selected vendor.'),
                        TextInput::make('name')->required()->maxLength(255)->live(onBlur: true)->afterStateUpdated(fn (?string $state, Set $set) => $set('slug', Str::slug($state ?? ''))),
                        Hidden::make('slug'),
                        Select::make('family')->options(VisaProductFamily::options())->default('standard')->required(),
                        Select::make('category')->label('Visa type')->options(self::categories())->searchable()->required()->helperText('Choose the purpose or official visa class customers will compare.'),
                        Select::make('entry_type')->label('Entry type')->options(self::entryTypes())->default('single')->required()->helperText('How many times the traveler may enter using this visa.'),
                        TextInput::make('validity_days')->numeric()->minValue(1)->suffix('days'),
                        TextInput::make('maximum_stay_days')->numeric()->minValue(1)->suffix('days'),
                        DatePicker::make('effective_from')->label('Available from')->helperText('Optional. The product is available for the entire selected day.'),
                        DatePicker::make('effective_until')->label('Available until')->afterOrEqual('effective_from')->helperText('Optional. The product remains available through the selected day.'),
                        Select::make('publication_status')->options(VisaPublicationStatus::options())->default('draft')->disabled()->dehydrated(),
                        Hidden::make('version')->default(1),
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
                            Select::make('traveler_type')->label('Who is this fee for?')->options(['all' => 'All travelers', 'adult' => 'Adults only', 'child' => 'Children only', 'infant' => 'Infants only'])->default('all')->required(),
                            Select::make('calculation_basis')->label('How often is it charged?')->options(['per_traveler' => 'For each applicable traveler', 'per_application' => 'Once for the whole application'])->default('per_traveler')->required(),
                            Select::make('processing_option_code')->label('Processing option')->options(fn (Get $get): array => self::namedCodes($get('../../processingOptions')))->placeholder('All processing options')->helperText('Leave empty when the fee applies to every option.'),
                            Select::make('currency')->options(self::currencies())->default('NGN')->searchable()->required(),
                            TextInput::make('amount')->numeric()->minValue(0)->required(),
                            Select::make('payee')->label('Who receives this fee?')->options(['travelwheel' => 'TravelWheel', 'authority' => 'Embassy or immigration authority'])->default('travelwheel')->required()->live(),
                            Toggle::make('pay_online')->label('Include in customer online payment')->default(true)->disabled(fn (Get $get) => $get('payee') === 'authority')->dehydrateStateUsing(fn ($state, Get $get) => $get('payee') === 'authority' ? false : (bool) $state),
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
                        Repeater::make('requirements')->relationship()->defaultItems(0)->live()->schema([
                            Select::make('optional_service_code')->label('Related TravelWheel service')->options(fn (Get $get): array => self::namedCodes($get('../../optionalServices')))->placeholder('General visa requirement')->helperText('A linked upload appears only when the customer chooses that service.'),
                            TextInput::make('name')->required(),
                            Select::make('category')->label('Document type')->options(['passport' => 'Passport', 'identity' => 'Identity', 'financial' => 'Financial', 'employment' => 'Employment', 'education' => 'Education', 'health' => 'Health', 'travel' => 'Travel', 'supporting_document' => 'Supporting document', 'other' => 'Other'])->default('supporting_document')->required(),
                            Select::make('scope')->label('Who uploads it?')->options(['traveler' => 'Each traveler uploads one', 'application' => 'One document for the whole application'])->default('traveler')->required(),
                            Select::make('requirement_state')->label('When is it required?')->options(['required' => 'Always required', 'optional' => 'Optional', 'conditional' => 'Only for selected applicant types'])->default('required')->required(),
                            Textarea::make('description')->rows(2)->columnSpanFull(),
                            TextInput::make('minimum_validity_days')->label('Passport validity required after travel')->numeric()->minValue(0)->suffix('days')->helperText('Leave empty when this document has no expiry requirement.'),
                            Textarea::make('guidance')->rows(2)->columnSpanFull(),
                            Select::make('conditions.traveler_type')->label('Show only for these travelers')->options(['adult' => 'Adult', 'child' => 'Child', 'infant' => 'Infant'])->multiple()->placeholder('Show for every traveler type')->dehydrated(fn ($state) => filled($state)),
                            Select::make('conditions.applicant_type')->label('Show only for these application profiles')->options(['individual' => 'Adult individual', 'company' => 'Company-sponsored adult', 'minor_nigerian' => 'Minor with Nigerian parent', 'minor_foreign' => 'Minor with foreign parent'])->multiple()->placeholder('Show for every application profile')->dehydrated(fn ($state) => filled($state)),
                            Toggle::make('is_active')->label('Requirement is active')->default(true),
                        ])->columns(2)->orderColumn('sort_order')->collapsible()->itemLabel(fn (array $state): ?string => $state['name'] ?? null)->columnSpanFull(),
                    ]),
                ]),

                Step::make('Questions')->description('Only information not already collected')->icon('heroicon-o-list-bullet')->schema([
                    Section::make('Application questions')->description('Trip, traveler, contact, and passport details already exist. Add only genuinely additional questions.')->schema([
                        Repeater::make('questions')->relationship()->defaultItems(0)->live()->schema([
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

                Step::make('Form designer')->description('Choose traveler and passport fields')->icon('heroicon-o-rectangle-stack')->schema([
                    Section::make('Application steps')
                        ->description('The workflow is assembled automatically. Questions, Services, and Documents appear only when you configured content in their earlier sections.')
                        ->schema([
                            Placeholder::make('trip_step_status')->label('Trip details')->content(fn () => self::stepStatus(true, 'Always included')),
                            Placeholder::make('questions_step_status')->label('Questions')->content(fn (Get $get) => self::stepStatus(self::activeItemCount($get('questions')) > 0, 'Enabled because application questions were added', 'Add a question to enable this step')),
                            Placeholder::make('services_step_status')->label('Services')->content(fn (Get $get) => self::stepStatus(self::activeItemCount($get('optionalServices')) > 0, 'Enabled because optional services were added', 'Add an optional service to enable this step')),
                            Placeholder::make('documents_step_status')->label('Documents')->content(fn (Get $get) => self::stepStatus(self::activeItemCount($get('requirements')) > 0, 'Enabled because document requirements were added', 'Add a document requirement to enable this step')),
                            Placeholder::make('review_step_status')->label('Review')->content(fn () => self::stepStatus(true, 'Always included')),
                            Placeholder::make('payment_step_status')->label('Payment')->content(fn () => self::stepStatus(true, 'Always included')),
                        ])->columns(3),
                    Section::make('Traveler fields')
                        ->description('Tick the information that customers must provide in the Travelers step. Unticked fields are hidden and are not validated.')
                        ->schema([
                            CheckboxList::make('form_configuration.traveler_fields')->hiddenLabel()->options(VisaFormWorkflow::TRAVELER_FIELDS)->default(array_keys(VisaFormWorkflow::TRAVELER_FIELDS))->bulkToggleable()->columns(2)->columnSpanFull(),
                        ]),
                    Section::make('Passport fields')
                        ->description('Tick the passport information that customers must provide. If no field is selected, the Passports step is omitted.')
                        ->schema([
                            CheckboxList::make('form_configuration.passport_fields')->hiddenLabel()->options(VisaFormWorkflow::PASSPORT_FIELDS)->default(array_keys(VisaFormWorkflow::PASSPORT_FIELDS))->bulkToggleable()->columns(2)->columnSpanFull(),
                        ]),
                    Section::make('Fixed customer flow')->schema([
                        \Filament\Schemas\Components\Text::make('Trip details → Travelers (when fields are selected) → Passports (when fields are selected) → Questions (automatic) → Services (automatic) → Documents (automatic) → Review → Payment.'),
                    ])->compact(),
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

    private static function activeItemCount(mixed $items): int
    {
        return collect($items ?? [])->filter(fn ($item) => (bool) ($item['is_active'] ?? true))->count();
    }

    private static function stepStatus(bool $enabled, string $enabledText, string $disabledText = ''): HtmlString
    {
        $background = $enabled ? '#ecfdf3' : '#f8fafc';
        $border = $enabled ? '#86efac' : '#cbd5e1';
        $color = $enabled ? '#166534' : '#64748b';
        $label = $enabled ? 'Enabled' : 'Not included';
        $copy = $enabled ? $enabledText : $disabledText;

        return new HtmlString("<div style=\"border:1px solid {$border};background:{$background};border-radius:10px;padding:12px\"><strong style=\"color:{$color};display:block;margin-bottom:4px\">{$label}</strong><small style=\"color:#475569\">{$copy}</small></div>");
    }

    private static function categories(): array
    {
        return [
            'tourist' => 'Tourist or visitor',
            'business' => 'Business',
            'study' => 'Study or student',
            'work' => 'Work or employment',
            'transit' => 'Transit',
            'family' => 'Family, spouse or dependant',
            'medical' => 'Medical treatment',
            'conference' => 'Conference or event',
            'cultural_sports' => 'Cultural or sports',
            'official_diplomatic' => 'Official or diplomatic',
            'religious' => 'Religious or pilgrimage',
            'journalist_media' => 'Journalist or media',
            'crew' => 'Crew member',
            'settlement_residence' => 'Settlement or residence',
            'other' => 'Other',
        ];
    }

    private static function entryTypes(): array
    {
        return [
            'single' => 'Single entry',
            'double' => 'Two entries',
            'multiple' => 'Multiple entry',
            'transit' => 'Airport transit',
            'other' => 'Other',
        ];
    }

    private static function currencies(): array
    {
        return ['NGN' => 'NGN - Nigerian Naira', 'USD' => 'USD - US Dollar', 'GBP' => 'GBP - British Pound', 'EUR' => 'EUR - Euro', 'CAD' => 'CAD - Canadian Dollar', 'AUD' => 'AUD - Australian Dollar', 'ZAR' => 'ZAR - South African Rand', 'AED' => 'AED - UAE Dirham'];
    }
}
