<?php

namespace App\Filament\Pages;

use App\Filament\Booking\StaffBookingActions;
use App\Services\AirCargoBookingService;
use App\Support\CargoCountries;
use App\Support\CargoPickupAreas;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Staff sending an air cargo shipment for a customer, step by step like the
 * website. Prices come from the same service and price tables.
 */
class BookAirCargo extends Page
{
    use WithFileUploads;

    protected string $view = 'filament.pages.book-air-cargo';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'new-booking/air-cargo';

    protected static ?string $title = 'Book Air Cargo';

    public string $senderName = '';

    public string $senderPhone = '';

    public string $senderEmail = '';

    public string $senderAddress = '';

    public string $senderPostalCode = '';

    public string $senderCountry = 'NG';

    public string $receiverName = '';

    public string $receiverPhone = '';

    public string $receiverEmail = '';

    public string $receiverAddress = '';

    public string $receiverPostalCode = '';

    public string $receiverCountry = '';

    /** 'Document' or 'Package' */
    public string $shipmentType = '';

    public string $weight = '';

    public string $description = '';

    public string $length = '';

    public string $width = '';

    public string $height = '';

    public ?TemporaryUploadedFile $preview = null;

    public bool $pickup = false;

    public string $pickupArea = '';

    public string $pickupAddress = '';

    public string $pickupDate = '';

    public string $dropoffDate = '';

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

    public function setType(string $type): void
    {
        $this->shipmentType = in_array($type, AirCargoBookingService::TYPES, true) ? $type : '';
    }

    public function setWeight(string $weight): void
    {
        $this->weight = isset(AirCargoBookingService::WEIGHTS[$weight]) ? $weight : '';
    }

    public function setPickup(bool $pickup): void
    {
        $this->pickup = $pickup;
    }

    public function create(AirCargoBookingService $cargo): void
    {
        $countries = array_keys(CargoCountries::COUNTRIES);
        $isPackage = $this->shipmentType === 'Package';
        $dimension = ['nullable', 'numeric', 'min:0', 'max:1000'];

        $this->validate([
            'senderName' => ['required', 'string', 'max:255'],
            'senderPhone' => ['required', 'string', 'max:20'],
            'senderEmail' => ['required', 'email', 'max:255'],
            'senderAddress' => ['required', 'string', 'max:255'],
            'senderPostalCode' => ['required', 'string', 'max:20'],
            'senderCountry' => ['required', Rule::in($countries)],
            'receiverName' => ['required', 'string', 'max:255'],
            'receiverPhone' => ['required', 'string', 'max:30'],
            'receiverEmail' => ['required', 'email', 'max:255'],
            'receiverAddress' => ['required', 'string', 'max:255'],
            'receiverPostalCode' => ['required', 'string', 'max:20'],
            'receiverCountry' => ['required', Rule::in($countries)],
            'shipmentType' => ['required', Rule::in(AirCargoBookingService::TYPES)],
            'weight' => ['required', Rule::in(array_keys(AirCargoBookingService::WEIGHTS))],
            'description' => [$isPackage ? 'nullable' : 'required', 'string', 'max:255'],
            'length' => $dimension,
            'width' => $dimension,
            'height' => $dimension,
            'preview' => ['nullable', 'file', 'mimes:jpg,png,pdf', 'max:10240'],
            'pickupArea' => [Rule::requiredIf($this->pickup), 'nullable', Rule::in(CargoPickupAreas::areas())],
            'pickupAddress' => [Rule::requiredIf($this->pickup), 'nullable', 'string', 'max:255'],
            'pickupDate' => [Rule::requiredIf($this->pickup), 'nullable', 'date', 'after_or_equal:today'],
            'dropoffDate' => [Rule::requiredIf(! $this->pickup), 'nullable', 'date', 'after_or_equal:today'],
        ], [
            'pickupArea.in' => 'Choose an area from the list.',
        ], [
            'senderName' => 'sender name', 'senderPhone' => 'sender phone', 'senderEmail' => 'sender email',
            'senderAddress' => 'sender address', 'senderPostalCode' => 'sender postal code', 'senderCountry' => 'sender country',
            'receiverName' => 'receiver name', 'receiverPhone' => 'receiver phone', 'receiverEmail' => 'receiver email',
            'receiverAddress' => 'receiver address', 'receiverPostalCode' => 'receiver postal code', 'receiverCountry' => 'destination',
            'shipmentType' => 'shipment type', 'pickupArea' => 'nearest bus stop', 'pickupAddress' => 'pick-up address',
            'pickupDate' => 'pick-up date', 'dropoffDate' => 'drop-off date',
        ]);

        if (! $cargo->quote($this->shipmentType, $this->receiverCountry, $this->weight)) {
            Notification::make()->title('There is no price for this shipment. Check the price tables, or choose another weight.')->danger()->send();

            return;
        }

        $shippingId = $cargo->newShippingId();

        // Where the website keeps these, so the admin shows them the same way
        $previewImage = null;
        if ($this->preview) {
            $extension = strtolower($this->preview->getClientOriginalExtension());
            $previewImage = $shippingId.'.'.(in_array($extension, ['jpg', 'jpeg', 'png', 'pdf'], true) ? $extension : 'jpg');
            File::ensureDirectoryExists(public_path('assets/aircargo'));
            File::put(public_path('assets/aircargo/'.$previewImage), $this->preview->get());
        }

        $booking = $cargo->createPending([
            'shipping_id' => $shippingId,
            'sender_name' => $this->senderName,
            'sender_phone' => $this->senderPhone,
            'sender_email' => $this->senderEmail,
            'sender_address' => $this->senderAddress,
            'sender_postal_code' => $this->senderPostalCode,
            'sender_country' => $this->senderCountry,
            'receiver_name' => $this->receiverName,
            'receiver_phone' => $this->receiverPhone,
            'receiver_email' => $this->receiverEmail,
            'receiver_address' => $this->receiverAddress,
            'receiver_postal_code' => $this->receiverPostalCode,
            'receiver_country' => $this->receiverCountry,
            'shipment_type' => $this->shipmentType,
            'weight' => $this->weight,
            'description' => $this->description,
            'length' => $isPackage ? $this->length : null,
            'width' => $isPackage ? $this->width : null,
            'height' => $isPackage ? $this->height : null,
            'pickup_area' => $this->pickup ? $this->pickupArea : null,
            'pickup_address' => $this->pickup ? $this->pickupAddress : null,
            'pickup_date' => $this->pickup ? $this->pickupDate : null,
            'dropoff_date' => $this->pickup ? null : $this->dropoffDate,
        ], $previewImage);

        StaffBookingActions::finish($booking, ['send_link' => $this->sendLink]);

        $this->redirect(NewBooking::getUrl());
    }

    protected function getViewData(): array
    {
        return [
            'countries' => CargoCountries::COUNTRIES,
            'weights' => array_keys(AirCargoBookingService::WEIGHTS),
            'pickupZones' => CargoPickupAreas::ZONES,
            'quote' => app(AirCargoBookingService::class)->quote($this->shipmentType, $this->receiverCountry, $this->weight, $this->pickup ? $this->pickupArea : null),
            'dropOffAddress' => AirCargoBookingService::DROP_OFF_ADDRESS,
        ];
    }
}
