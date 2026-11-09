<?php

namespace App\Filament\Pages;

use App\Filament\Booking\StaffBookingActions;
use App\Models\Country;
use App\Models\VisaDestination;
use App\Models\VisaProduct;
use App\Services\VisaApplicationDraftService;
use App\Services\VisaDiscoveryService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Staff starting a visa application for a customer: the website's search, then
 * the chosen visa's application. Each visa asks for its own travellers'
 * details, questions and documents, so those are filled in the website's
 * application, by staff now or by the customer from an emailed link.
 */
class BookVisa extends Page
{
    /** Changing any of these needs a new search. */
    private const SEARCH_FIELDS = ['nationality', 'residence', 'destination', 'arrivalDate', 'departureDate', 'adults', 'children', 'infants'];

    protected string $view = 'filament.pages.book-visa';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'new-booking/visa';

    protected static ?string $title = 'Book Visa';

    public string $nationality = '';

    public string $residence = '';

    /** 'country:ID' or 'region:ID', as the website's search sends it. */
    public string $destination = '';

    public string $arrivalDate = '';

    public string $departureDate = '';

    public int $adults = 1;

    public int $children = 0;

    public int $infants = 0;

    public string $email = '';

    public bool $searched = false;

    /** Email the customer a link to finish the application, or open it here for staff to fill in. */
    public bool $sendLink = false;

    public static function canAccess(): bool
    {
        return config('visa.enabled') && (auth()->user()?->canBookForCustomers() ?? false);
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

    public function updated(string $property): void
    {
        if (in_array($property, self::SEARCH_FIELDS, true)) {
            $this->searched = false;
        }

        if ($property === 'nationality') {
            $this->destination = '';
        }
    }

    public function changeCount(string $who, int $by): void
    {
        if (! in_array($who, ['adults', 'children', 'infants'], true)) {
            return;
        }

        $this->{$who} = max($who === 'adults' ? 1 : 0, min(9, $this->{$who} + $by));
        $this->infants = min($this->infants, $this->adults);
        $this->searched = false;
    }

    public function search(): void
    {
        $this->validateSearch();
        $this->searched = true;
    }

    /** The results are worked out again here, so only a visa the search offers can be started. */
    public function start(int $productId, VisaApplicationDraftService $drafts): void
    {
        $this->validateSearch();
        $this->validate(['email' => ['required', 'email:rfc', 'max:255']], [], ['email' => "customer's email"]);

        $search = $this->searchParams();
        $eligibleIds = collect($this->results())->pluck('id')->all();

        try {
            $application = DB::transaction(function () use ($drafts, $productId, $search, $eligibleIds) {
                [$application] = $drafts->start(VisaProduct::query()->findOrFail($productId), $search, $eligibleIds);
                $application->update(['contact_email' => $this->email]);
                $application->statusHistory()->latest('id')->first()?->update(['actor_type' => 'user', 'actor_id' => auth()->id(), 'reason' => 'Application started by staff']);

                return $application;
            });
        } catch (ValidationException $e) {
            Notification::make()->title(collect($e->errors())->flatten()->first())->danger()->send();
            $this->searched = false;

            return;
        }

        StaffBookingActions::finish($application, ['send_link' => $this->sendLink]);

        if ($this->sendLink) {
            $this->redirect(NewBooking::getUrl());

            return;
        }

        // Staff fill in the rest in the website's application, which this session may now open
        session()->put("visa_application_access.{$application->reference}", true);
        $this->redirect(route('visa.application', $application));
    }

    protected function getViewData(): array
    {
        return [
            'countries' => Country::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'destinations' => $this->destinations(),
            'results' => $this->searched ? $this->results() : [],
        ];
    }

    private function validateSearch(): void
    {
        $this->validate([
            'nationality' => ['required', Rule::exists('countries', 'id')->where('is_active', true)],
            'residence' => ['nullable', Rule::exists('countries', 'id')->where('is_active', true)],
            'destination' => ['required', Rule::in($this->destinations()->pluck('ref')->all())],
            'arrivalDate' => ['required', 'date', 'after_or_equal:today'],
            'departureDate' => ['required', 'date', 'after:arrivalDate'],
            'adults' => ['required', 'integer', 'min:1', 'max:9'],
            'children' => ['required', 'integer', 'min:0', 'max:9'],
            'infants' => ['required', 'integer', 'min:0', 'max:9', 'lte:adults'],
        ], ['destination.in' => 'Choose a destination offered for this passport.'], [
            'nationality' => 'passport nationality', 'residence' => 'country of residence',
            'arrivalDate' => 'arrival date', 'departureDate' => 'departure date',
        ]);
    }

    /** Where this passport can apply for a visa through us, countries and regions as one list. */
    private function destinations(): \Illuminate\Support\Collection
    {
        $nationality = filled($this->nationality) ? Country::query()->where('is_active', true)->find($this->nationality) : null;

        if (! $nationality) {
            return collect();
        }

        $available = app(VisaDiscoveryService::class)->availableDestinationsForNationality($nationality);

        return $available['countries']->map(fn (array $country): array => ['ref' => $country['ref'], 'name' => $country['name']])
            ->concat($available['regions']->map(fn (array $region): array => ['ref' => $region['ref'], 'name' => $region['name'].' (region)']))
            ->values();
    }

    /** @return list<array<string, mixed>> */
    private function results(): array
    {
        $search = $this->searchParams();
        $travelers = $search['travelers'];

        return app(VisaDiscoveryService::class)->search(
            Country::query()->findOrFail($search['nationality_id']),
            $this->destinationModel(),
            filled($search['residence_country_id']) ? Country::query()->find($search['residence_country_id']) : null,
            $travelers,
            ['arrival_date' => $search['arrival_date'], 'departure_date' => $search['departure_date'], 'travelers' => $travelers],
        )->all();
    }

    private function destinationModel(): Country|VisaDestination
    {
        [$type, $id] = explode(':', $this->destination, 2);

        return $type === 'region'
            ? VisaDestination::query()->where('is_active', true)->findOrFail($id)
            : Country::query()->where('is_active', true)->findOrFail($id);
    }

    /** The search as the website keeps it, so the application starts the same way. */
    private function searchParams(): array
    {
        $destination = $this->destinationModel();
        $isRegion = $destination instanceof VisaDestination;
        $residence = filled($this->residence) ? Country::query()->find($this->residence) : null;

        return [
            'nationality_id' => (int) $this->nationality,
            'residence_country_id' => $residence?->id,
            'destination_ref' => $this->destination,
            'destination_type' => $isRegion ? 'region' : 'country',
            'destination_id' => $isRegion ? null : $destination->id,
            'visa_destination_id' => $isRegion ? $destination->id : null,
            'arrival_date' => $this->arrivalDate,
            'departure_date' => $this->departureDate,
            'adults' => $this->adults,
            'children' => $this->children,
            'infants' => $this->infants,
            'nationality_name' => Country::query()->find($this->nationality)?->name,
            'destination_name' => $destination->name,
            'residence_name' => $residence?->name,
            'travelers' => ['adult' => $this->adults, 'child' => $this->children, 'infant' => $this->infants],
        ];
    }
}
