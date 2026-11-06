<?php

namespace App\Filament\Pages;

use App\Filament\Booking\StaffBookingActions;
use App\Models\InsuranceQuote;
use App\Services\InsuranceBookingService;
use App\Support\InsuranceOptions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Throwable;

/**
 * Staff buying travel insurance for a customer, step by step like the website:
 * a live quote from Sanlam Allianz, then the travellers. The policy is issued
 * once the customer has paid.
 */
class BookInsurance extends Page
{
    private const MAX_CHILDREN = 6;

    /** Changing any of these needs a new quote. */
    private const TRIP_FIELDS = ['bookingType', 'children', 'purpose', 'leadDob', 'email', 'phone', 'country', 'beginDate', 'endDate'];

    protected string $view = 'filament.pages.book-insurance';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'new-booking/insurance';

    protected static ?string $title = 'Book Insurance';

    public int $bookingType = InsuranceBookingService::INDIVIDUAL;

    public int $children = 0;

    public string $purpose = '';

    public string $leadDob = '';

    public string $email = '';

    public string $phone = '';

    public string $country = '';

    public string $beginDate = '';

    public string $endDate = '';

    #[Locked]
    public ?int $quoteId = null;

    public ?string $quoteError = null;

    /** The lead traveller. */
    public array $lead = [];

    /** A family's second adult. */
    public array $partner = [];

    /** @var list<array<string, string>> */
    public array $kids = [];

    /** Surname and address: the whole family's, or the one traveller's. */
    public array $home = ['surname' => '', 'state' => '', 'address' => '', 'zipcode' => ''];

    public array $nok = ['fullname' => '', 'address' => '', 'phone' => '', 'relationship' => ''];

    public bool $sendLink = true;

    public static function canAccess(): bool
    {
        return auth()->user()?->canBookForCustomers() ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back to bookings')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn (): string => NewBooking::getUrl()),
        ];
    }

    public function mount(): void
    {
        $this->lead = self::blankAdult();
        $this->partner = self::blankAdult();
    }

    public function updated(string $property): void
    {
        if (in_array(strtok($property, '.'), self::TRIP_FIELDS, true)) {
            $this->quoteId = null;
            $this->quoteError = null;
        }
    }

    public function setBookingType(int $type): void
    {
        $this->bookingType = $type === InsuranceBookingService::FAMILY ? InsuranceBookingService::FAMILY : InsuranceBookingService::INDIVIDUAL;
        $this->children = 0;
        $this->kids = [];
        $this->updated('bookingType');
    }

    public function changeChildren(int $by): void
    {
        $this->children = max(0, min(self::MAX_CHILDREN, $this->children + $by));
        $this->kids = array_slice(array_pad($this->kids, $this->children, self::blankChild()), 0, $this->children);
        $this->updated('children');
    }

    /** A live call to Sanlam, so it runs when asked for rather than on every change. */
    public function getQuote(InsuranceBookingService $insurance): void
    {
        $this->validate([
            'bookingType' => ['required', Rule::in([InsuranceBookingService::INDIVIDUAL, InsuranceBookingService::FAMILY])],
            'children' => ['integer', 'min:0', 'max:'.self::MAX_CHILDREN],
            'purpose' => ['required', Rule::in(InsuranceOptions::PURPOSES)],
            'leadDob' => ['required', 'date', 'before:today'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'country' => ['required', Rule::in(array_keys(InsuranceOptions::COUNTRIES))],
            'beginDate' => ['required', 'date', 'after_or_equal:today'],
            'endDate' => ['required', 'date', 'after:beginDate'],
        ], [], [
            'leadDob' => 'date of birth', 'country' => 'destination', 'beginDate' => 'cover start', 'endDate' => 'cover end',
        ]);

        $this->quoteId = null;
        $this->quoteError = null;

        try {
            $quote = $insurance->requestQuote([
                'dob' => $this->leadDob,
                'email' => $this->email,
                'phone_no' => $this->phone,
                'begin_date' => $this->beginDate,
                'end_date' => $this->endDate,
                'country' => $this->country,
                'purpose_of_travel' => $this->purpose,
                'travel_plan' => InsuranceOptions::planFor($this->country),
                'booking_type' => $this->bookingType,
                'nop' => 1,
                'noc' => $this->isFamily() ? $this->children : 0,
            ]);
        } catch (Throwable $e) {
            report($e);
            $this->quoteError = 'Sanlam could not give a quote right now. Check the details and try again.';

            return;
        }

        $this->quoteId = $quote->id;

        // The lead traveller's details start from what the quote was for
        $this->lead['dob'] = $this->lead['dob'] ?: $this->leadDob;
        $this->lead['email'] = $this->lead['email'] ?: $this->email;
        $this->lead['phone'] = $this->lead['phone'] ?: $this->phone;
    }

    public function create(InsuranceBookingService $insurance): void
    {
        $quote = $this->quote();

        if (! $quote) {
            Notification::make()->title('Get a quote first.')->danger()->send();

            return;
        }

        $this->validate($this->travellerRules(), [], $this->travellerNames());

        $booking = $insurance->createPending($quote, $this->bookingForm(), [
            'nok_fullname' => $this->nok['fullname'],
            'nok_address' => $this->nok['address'],
            'nok_phone' => $this->nok['phone'],
            'nok_relationship' => $this->nok['relationship'],
        ]);

        StaffBookingActions::finish($booking, ['send_link' => $this->sendLink]);

        $this->redirect(NewBooking::getUrl());
    }

    protected function getViewData(): array
    {
        $quote = $this->quote();

        return [
            'countries' => InsuranceOptions::COUNTRIES,
            'titles' => InsuranceOptions::TITLES,
            'states' => InsuranceOptions::STATES,
            'genders' => InsuranceOptions::GENDERS,
            'maritalStatuses' => InsuranceOptions::MARITAL_STATUSES,
            'purposes' => InsuranceOptions::PURPOSES,
            'plan' => filled($this->country) ? InsuranceOptions::planFor($this->country) : null,
            'quote' => $quote,
            'price' => $quote ? app(InsuranceBookingService::class)->price($quote) : null,
            'vatPercent' => InsuranceBookingService::VAT_PERCENT,
            'family' => $this->isFamily(),
        ];
    }

    private function quote(): ?InsuranceQuote
    {
        $quote = $this->quoteId ? InsuranceQuote::find($this->quoteId) : null;

        return $quote ? app(InsuranceBookingService::class)->quote($quote->quoteRequestId) : null;
    }

    private function isFamily(): bool
    {
        return $this->bookingType === InsuranceBookingService::FAMILY;
    }

    /** The booking form as the website's is keyed, so the policy is issued the same way. */
    private function bookingForm(): array
    {
        $home = ['surname' => $this->home['surname'], 'state' => $this->home['state'], 'address' => $this->home['address'], 'zipcode' => $this->home['zipcode']];
        $lead = $this->lead;

        if (! $this->isFamily()) {
            return $home + [
                'firstname' => $lead['firstname'], 'middlename' => $lead['middlename'], 'gender' => $lead['gender'], 'title' => $lead['title'],
                'dob' => $lead['dob'], 'email' => $lead['email'], 'phone_no' => $lead['phone'], 'passport_no' => $lead['passport_no'],
                'nationalty' => $lead['nationality'], 'nin' => $lead['nin'] ?: null, 'ocupation' => $lead['occupation'],
                'marital_status' => $lead['marital_status'], 'MedicalCondition' => $lead['medical'] ?: null,
            ];
        }

        $partner = $this->partner;
        $form = $home + [
            'firstname' => $lead['firstname'], 'middlename1' => $lead['middlename'], 'gender1' => $lead['gender'], 'title1' => $lead['title'],
            'dob1' => $lead['dob'], 'email' => $lead['email'], 'phone_no' => $lead['phone'], 'passport_no' => $lead['passport_no'],
            'nationalty1' => $lead['nationality'], 'nin' => $lead['nin'] ?: null, 'ocupation1' => $lead['occupation'],
            'marital_status1' => $lead['marital_status'], 'MedicalCondition1' => $lead['medical'] ?: null,
            'firstname2' => $partner['firstname'], 'middlename2' => $partner['middlename'], 'gender2' => $partner['gender'], 'title2' => $partner['title'],
            'dob2' => $partner['dob'], 'email2' => $partner['email'], 'phone_no2' => $partner['phone'], 'passport_no2' => $partner['passport_no'],
            'nationalty2' => $partner['nationality'], 'nin2' => $partner['nin'] ?: null, 'ocupation2' => $partner['occupation'],
            'MedicalCondition2' => $partner['medical'] ?: null,
            'noc' => count($this->kids),
        ];

        foreach (array_values($this->kids) as $i => $kid) {
            $n = $i + 1;
            $form += [
                'firstnameC'.$n => $kid['firstname'], 'middlenameC'.$n => $kid['middlename'], 'genderC'.$n => $kid['gender'],
                'titleC'.$n => $kid['title'], 'dobC'.$n => $kid['dob'], 'passport_noC'.$n => $kid['passport_no'],
                'nationaltyC'.$n => $kid['nationality'], 'ninC'.$n => $kid['nin'] ?: null,
            ];
        }

        return $form;
    }

    private function travellerRules(): array
    {
        $countries = Rule::in(array_keys(InsuranceOptions::COUNTRIES));
        $adult = fn (string $who) => [
            "$who.firstname" => ['required', 'string', 'max:120'],
            "$who.middlename" => ['nullable', 'string', 'max:120'],
            "$who.gender" => ['required', Rule::in(array_keys(InsuranceOptions::GENDERS))],
            "$who.title" => ['required', Rule::in(array_keys(InsuranceOptions::TITLES))],
            "$who.dob" => ['required', 'date', 'before:today'],
            "$who.email" => ['required', 'email', 'max:255'],
            "$who.phone" => ['required', 'string', 'max:20'],
            "$who.passport_no" => [$who === 'lead' ? 'required' : 'nullable', 'string', 'max:30'],
            "$who.nationality" => ['required', $countries],
            "$who.nin" => ['nullable', 'digits:11'],
            "$who.occupation" => ['required', 'string', 'max:120'],
            "$who.marital_status" => [$who === 'lead' ? 'required' : 'nullable', Rule::in(array_keys(InsuranceOptions::MARITAL_STATUSES))],
            "$who.medical" => ['nullable', 'string', 'max:100'],
        ];

        $rules = $adult('lead') + [
            'home.surname' => ['required', 'string', 'max:120'],
            'home.state' => ['required', Rule::in(array_keys(InsuranceOptions::STATES))],
            'home.address' => ['required', 'string', 'max:255'],
            'home.zipcode' => ['required', 'string', 'max:20'],
            'nok.fullname' => ['required', 'string', 'max:255'],
            'nok.address' => ['required', 'string', 'max:255'],
            'nok.phone' => ['required', 'string', 'max:20'],
            'nok.relationship' => ['required', 'string', 'max:100'],
        ];

        if ($this->isFamily()) {
            $rules += $adult('partner') + [
                'kids' => ['array', 'size:'.$this->children],
                'kids.*.firstname' => ['required', 'string', 'max:120'],
                'kids.*.middlename' => ['nullable', 'string', 'max:120'],
                'kids.*.gender' => ['required', Rule::in(array_keys(InsuranceOptions::GENDERS))],
                'kids.*.title' => ['required', Rule::in(array_keys(InsuranceOptions::TITLES))],
                'kids.*.dob' => ['required', 'date', 'before:today'],
                'kids.*.passport_no' => ['nullable', 'string', 'max:30'],
                'kids.*.nationality' => ['required', $countries],
                'kids.*.nin' => ['nullable', 'digits:11'],
            ];
        }

        return $rules;
    }

    private function travellerNames(): array
    {
        $names = ['home.surname' => 'surname', 'home.state' => 'state', 'home.address' => 'address', 'home.zipcode' => 'zip code',
            'nok.fullname' => 'next of kin name', 'nok.address' => 'next of kin address', 'nok.phone' => 'next of kin phone',
            'nok.relationship' => 'relationship'];

        foreach (['lead', 'partner', 'kids.*'] as $who) {
            foreach (['firstname' => 'first name', 'gender' => 'gender', 'title' => 'title', 'dob' => 'date of birth', 'email' => 'email',
                'phone' => 'phone', 'passport_no' => 'passport number', 'nationality' => 'nationality', 'nin' => 'NIN',
                'occupation' => 'occupation', 'marital_status' => 'marital status'] as $field => $label) {
                $names["$who.$field"] = $label;
            }
        }

        return $names;
    }

    private static function blankAdult(): array
    {
        return ['firstname' => '', 'middlename' => '', 'gender' => '', 'title' => '', 'dob' => '', 'email' => '', 'phone' => '',
            'passport_no' => '', 'nationality' => '', 'nin' => '', 'occupation' => '', 'marital_status' => '', 'medical' => ''];
    }

    private static function blankChild(): array
    {
        return ['firstname' => '', 'middlename' => '', 'gender' => '', 'title' => '', 'dob' => '', 'passport_no' => '', 'nationality' => '', 'nin' => ''];
    }
}
