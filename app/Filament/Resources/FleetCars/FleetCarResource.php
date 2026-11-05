<?php

namespace App\Filament\Resources\FleetCars;

use App\Filament\Concerns\EditableByPricingStaff;
use App\Filament\Resources\FleetCars\Pages\CreateFleetCar;
use App\Filament\Resources\FleetCars\Pages\EditFleetCar;
use App\Filament\Resources\FleetCars\Pages\ListFleetCars;
use App\Models\FleetCar;
use App\Models\VendorApplication;
use App\Support\GroundStates;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FleetCarResource extends Resource
{
    use EditableByPricingStaff;

    protected static ?string $model = FleetCar::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|\UnitEnum|null $navigationGroup = 'Travel Connections';

    protected static ?int $navigationSort = 50;

    protected static ?string $navigationLabel = 'Fleet';

    protected static ?string $recordTitleAttribute = 'car_name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('service_type')
                ->options(['car_hire' => 'Car Hire', 'transfer' => 'Transfer'])
                ->required()
                ->live(),
            Select::make('state')
                ->options(fn (): array => GroundStates::grouped())
                ->default(GroundStates::default())
                ->searchable()
                ->required()
                ->in(fn (): array => array_keys(GroundStates::all()))
                ->helperText('Where this car operates. Customers can book in a state as soon as it has an active car, e.g. add a car in FCT Abuja to open Abuja.'),
            Select::make('vehicle_type')
                ->options(['saloon' => 'Saloon', 'suv' => 'SUV', 'van' => 'Van', 'bus' => 'Bus', 'luxury' => 'Luxury'])
                ->required()
                ->live()
                // Luxury has its own year ranges, so re-derive the category
                ->afterStateUpdated(function (Get $get, Set $set): void {
                    $year = (int) $get('year');
                    $set('category', $year ? FleetCar::categoryForYear($year, $get('vehicle_type')) : null);
                }),
            TextInput::make('year')
                ->label('Year of Vehicle')
                ->numeric()
                ->minValue(2005)
                ->maxValue((int) date('Y') + 1)
                // Required for new cars only: many existing cars were added
                // before Year existed and keep their category until a year is set
                ->required(fn (string $operation): bool => $operation === 'create')
                // A year outside the vehicle type's ranges would save the car
                // with no category, and it would never show on the booking page
                ->rule(fn (Get $get) => function (string $attribute, $value, \Closure $fail) use ($get): void {
                    if (blank($value) || blank($get('vehicle_type'))) {
                        return;
                    }
                    if (FleetCar::categoryForYear((int) $value, $get('vehicle_type')) === null) {
                        $ranges = collect(array_keys(FleetCar::yearRangesFor($get('vehicle_type'))))
                            ->map(fn (string $c): string => $c.' '.FleetCar::yearRangeLabel($c, $get('vehicle_type')))
                            ->implode(', ');
                        $fail("A {$get('vehicle_type')} from {$value} doesn't fit any category ({$ranges}).");
                    }
                })
                ->live(onBlur: true)
                ->afterStateUpdated(function (Get $get, Set $set): void {
                    $year = (int) $get('year');
                    $set('category', $year ? FleetCar::categoryForYear($year, $get('vehicle_type')) : null);
                })
                ->helperText(fn (Get $get): string => 'Category is derived automatically: '.collect(array_keys(FleetCar::yearRangesFor($get('vehicle_type'))))->map(fn (string $c): string => $c.' '.FleetCar::yearRangeLabel($c, $get('vehicle_type')))->implode(' · ').'.'),
            TextInput::make('category')
                ->label('Category (auto)')
                ->disabled()
                ->dehydrated(false)
                ->placeholder('Enter a year to compute')
                ->helperText('Set automatically from Year of Vehicle when you save.'),
            TextInput::make('car_name')->required()->maxLength(100),
            TextInput::make('colour')->maxLength(100)->placeholder('e.g. Black, White and Grey'),
            TextInput::make('passengers')->maxLength(60)->helperText('e.g. "1 – 3 Passengers"'),
            Textarea::make('features')
                ->helperText('One feature per line, e.g. "Air conditioning"')
                ->rows(4)
                ->afterStateHydrated(fn ($component, $state) => $component->state(is_array($state) ? implode("\n", $state) : $state))
                ->dehydrateStateUsing(fn ($state) => array_values(array_filter(array_map('trim', explode("\n", (string) $state))))),
            FileUpload::make('images')
                ->multiple()
                ->image()
                ->reorderable()
                ->disk('fleet_assets')
                ->directory('fleet')
                ->helperText('Exterior photos only (no interior/dashboard shots). Upload in this order so the public page labels them correctly: 1) Front view, 2) Back view, 3) Side view. Any extra photos after those three are shown without a label.')
                ->columnSpanFull(),
            Toggle::make('is_active')->default(true),
            self::vendorSection(),
        ])->columns(2);
    }

    /**
     * Who supplies the car and what they charge us, from their rate card.
     * Admin only: customers never see it, and customer prices still come
     * from the Car Hire / Transfer rate settings.
     */
    private static function vendorSection(): Section
    {
        $naira = fn ($state): string => '₦'.number_format((float) $state);

        return Section::make('Vendor')
            ->description('Who supplies this car and their rates to us. Admin only — never shown to customers.')
            ->icon(Heroicon::OutlinedBuildingStorefront)
            ->collapsible()
            ->columnSpanFull()
            ->columns(2)
            ->schema([
                Select::make('vendor_application_id')
                    ->label('Registered partner')
                    ->relationship('vendor', 'registered_name', fn ($query) => $query->where('status', 'approved'))
                    ->getOptionLabelFromRecordUsing(fn (VendorApplication $vendor): string => trim(($vendor->trading_name ?: $vendor->registered_name).' '.($vendor->vendor_code ? "({$vendor->vendor_code})" : '')))
                    ->searchable(['registered_name', 'trading_name', 'vendor_code'])
                    ->preload()
                    ->live()
                    // Copy the partner's contact details in; they stay editable
                    ->afterStateUpdated(function ($state, Set $set): void {
                        $vendor = $state ? VendorApplication::find($state) : null;
                        if ($vendor) {
                            $set('vendor_name', $vendor->trading_name ?: $vendor->registered_name);
                            $set('vendor_contact_name', $vendor->operations_name ?: $vendor->contact_name);
                            $set('vendor_phone', $vendor->operations_phone ?: $vendor->contact_phone);
                            $set('vendor_email', $vendor->operations_email ?: $vendor->booking_email ?: $vendor->contact_email);
                        }
                    })
                    ->helperText('An approved vendor from the partner registration form. Leave empty and type the details below for a vendor who has not registered.')
                    ->columnSpanFull(),
                TextInput::make('vendor_name')->label('Vendor / company')->maxLength(255)->placeholder('e.g. Cooperate Foot'),
                TextInput::make('vendor_contact_name')->label('Contact person')->maxLength(255),
                TextInput::make('vendor_phone')->label('Phone')->tel()->maxLength(60),
                TextInput::make('vendor_email')->label('Email')->email()->maxLength(255),
                Repeater::make('vendor_hourly_rates')
                    ->label('Hourly rates')
                    ->schema([
                        TextInput::make('hours')->numeric()->minValue(1)->required()->suffix('hrs'),
                        TextInput::make('price')->numeric()->minValue(0)->required()->prefix('₦'),
                        TextInput::make('note')->maxLength(100)->placeholder('e.g. Extension only'),
                    ])
                    ->columns(3)
                    ->defaultItems(0)
                    ->addActionLabel('Add an hourly rate')
                    ->itemLabel(fn (array $state): ?string => filled($state['hours'] ?? null) ? $state['hours'].' hrs — '.$naira($state['price'] ?? 0) : null)
                    ->collapsible()
                    ->columnSpanFull(),
                Repeater::make('vendor_trip_rates')
                    ->label('Airport & station rates')
                    ->schema([
                        TextInput::make('service')
                            ->required()
                            ->maxLength(100)
                            ->datalist([
                                'Domestic airport pickup / drop-off',
                                'International airport drop-off',
                                'International airport pickup',
                                'Train station pickup / drop-off',
                            ]),
                        TextInput::make('price')->numeric()->minValue(0)->required()->prefix('₦'),
                    ])
                    ->columns(2)
                    ->defaultItems(0)
                    ->addActionLabel('Add an airport or station rate')
                    ->itemLabel(fn (array $state): ?string => filled($state['service'] ?? null) ? $state['service'].' — '.$naira($state['price'] ?? 0) : null)
                    ->collapsible()
                    ->columnSpanFull(),
                Textarea::make('vendor_notes')
                    ->label('Vendor terms & notes')
                    ->rows(3)
                    ->placeholder('e.g. Model year 2013. Priority chauffeur +₦10,000. Trips before 7am or past midnight +₦10,000.')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('vehicle_type')
            ->columns([
                ImageColumn::make('images')->label('Photo')->getStateUsing(fn (FleetCar $record) => $record->images[0] ?? null)->disk('fleet_assets'),
                TextColumn::make('car_name')->searchable()->sortable()->weight('bold'),
                TextColumn::make('service_type')->badge()->sortable(),
                TextColumn::make('state')->formatStateUsing(fn (?string $state): string => GroundStates::name($state))->badge()->color('info')->sortable(),
                TextColumn::make('vehicle_type')->badge()->sortable(),
                TextColumn::make('year')->sortable()->placeholder('-'),
                TextColumn::make('category')->placeholder('-'),
                TextColumn::make('passengers')->placeholder('-'),
                TextColumn::make('vendor_name')->label('Vendor')->searchable()->sortable()->placeholder('-')
                    ->description(fn (FleetCar $record): ?string => $record->vendor_phone)->toggleable(),
                IconColumn::make('is_active')->boolean()->sortable(),
            ])
            ->filters([
                SelectFilter::make('service_type')->options(['car_hire' => 'Car Hire', 'transfer' => 'Transfer']),
                SelectFilter::make('state')->options(fn (): array => GroundStates::all())->searchable(),
                SelectFilter::make('vendor_name')->label('Vendor')
                    ->options(fn (): array => FleetCar::whereNotNull('vendor_name')->distinct()->orderBy('vendor_name')->pluck('vendor_name', 'vendor_name')->all()),
                SelectFilter::make('vehicle_type')->options(['saloon' => 'Saloon', 'suv' => 'SUV', 'van' => 'Van', 'bus' => 'Bus', 'luxury' => 'Luxury']),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFleetCars::route('/'),
            'create' => CreateFleetCar::route('/create'),
            'edit' => EditFleetCar::route('/{record}/edit'),
        ];
    }
}
