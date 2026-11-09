<?php

namespace App\Services\Flights;

use App\Models\CompanyUser;
use App\Models\FlightBooking;
use App\Models\User;
use App\Support\FlightDisplay;
use Carbon\Carbon;

/**
 * Writes the flight_bookings row for a fare being booked, from the website's
 * checkout or a booking staff make in the admin, so both record the same
 * prices, snapshots and references.
 */
class FlightBookingRecorder
{
    /**
     * $context: session_id, trip_type, extras_total, company_user (who books
     * for a company), booked_by (the staff member), cost_code.
     */
    public function record(array $mappedFlight, array $contact, array $passengers, array $apiResponse, array $context = [], array $overrides = []): FlightBooking
    {
        $tktRaw = $overrides['tkt_time_limit'] ?? null;
        unset($overrides['tkt_time_limit']);

        /** @var CompanyUser|null $companyUser */
        $companyUser = $context['company_user'] ?? null;
        /** @var User|null $bookedBy */
        $bookedBy = $context['booked_by'] ?? null;

        return FlightBooking::create(array_merge([
            'company_id' => $companyUser?->company_id,
            'company_user_id' => $companyUser?->id,
            'booked_by_user_id' => $bookedBy?->id,
            'cost_code' => $companyUser ? ($context['cost_code'] ?? null) : null,
            // unique_id from the API is the hold/ticket reference; this is ours, shown to customers
            'booking_ref' => $this->newReference(),
            'fare_source_code' => $mappedFlight['fareSourceCode'] ?? '',
            'session_id' => (string) ($context['session_id'] ?? ''),
            'fare_type' => $mappedFlight['fareType'] ?? 'Public',
            'supplier' => $mappedFlight['source'] ?? 'travelnext',
            'trip_type' => (string) ($context['trip_type'] ?? ''),
            'route' => FlightDisplay::route($mappedFlight),
            'airline' => $mappedFlight['airline'] ?? '',
            'cabin' => FlightDisplay::cabin($mappedFlight),
            'currency' => $mappedFlight['currency'] ?? 'NGN',
            'supplier_price' => $mappedFlight['supplierPrice'] ?? ($mappedFlight['price'] ?? 0),
            'markup_amount' => $mappedFlight['markupAmount'] ?? 0,
            'markup_category' => $mappedFlight['markupCategory'] ?? null,
            'markup_details' => [
                'category' => $mappedFlight['markupCategory'] ?? null,
                'cabin' => $mappedFlight['markupCabin'] ?? null,
                'supplier_price' => $mappedFlight['supplierPrice'] ?? ($mappedFlight['price'] ?? 0),
                'markup_amount' => $mappedFlight['markupAmount'] ?? 0,
                'rate_per_passenger' => $mappedFlight['markupRatePerPassenger'] ?? 0,
                'passenger_count' => $mappedFlight['markupPassengerCount'] ?? 1,
                'customer_price' => $mappedFlight['price'] ?? 0,
                'currency' => $mappedFlight['currency'] ?? 'NGN',
            ],
            'total_price' => ((float) ($mappedFlight['price'] ?? 0)) + (float) ($context['extras_total'] ?? 0),
            'contact_email' => $contact['email'] ?? '',
            'contact_phone' => $contact['phone'] ?? '',
            // Needed later for SkyLink's primary_guest.country_code — persisted
            // rather than read from session at reserve() time, since that call
            // runs from the SeerBit payment callback (a separate request, by
            // then the original session may be long gone).
            'contact_area_code' => $contact['area_code'] ?? '',
            'contact_country_code' => $contact['country_code'] ?? '',
            'adult_count' => collect($passengers)->where('type', 'ADT')->count(),
            'child_count' => collect($passengers)->where('type', 'CHD')->count(),
            'infant_count' => collect($passengers)->where('type', 'INF')->count(),
            'tkt_time_limit' => $tktRaw ? Carbon::parse($tktRaw) : null,
            'booking_api_response' => $apiResponse,
            'passengers_snapshot' => $passengers,
            'flight_snapshot' => $mappedFlight,
        ], $overrides));
    }

    /**
     * A unique, human-readable booking reference: TW- and 8 uppercase
     * characters. Ours, separate from the airline's UniqueID or e-ticket.
     */
    public function newReference(): string
    {
        do {
            $ref = 'TW-'.strtoupper(substr(base_convert(bin2hex(random_bytes(5)), 16, 36), 0, 8));
        } while (FlightBooking::where('booking_ref', $ref)->exists());

        return $ref;
    }
}
