<?php

namespace App\Services;

use App\Models\FlightBooking;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AdminReplacementFlightSearchService
{
    public function options(FlightBooking $booking, array $criteria): array
    {
        $from = strtoupper(trim((string) ($criteria['from'] ?? '')));
        $to = strtoupper(trim((string) ($criteria['to'] ?? '')));
        $date = (string) ($criteria['departure_date'] ?? '');
        $cabin = strtoupper(trim((string) ($criteria['cabin'] ?? 'Y')));
        $airlineCode = strtoupper(trim((string) ($criteria['airline_code'] ?? '')));
        $airlineCode = preg_match('/^[A-Z0-9]{2}$/', $airlineCode) === 1 ? $airlineCode : '';

        if (! preg_match('/^[A-Z]{3}$/', $from) || ! preg_match('/^[A-Z]{3}$/', $to) || blank($date)) {
            return [];
        }

        $cacheKey = 'admin-reissue-flight-search:'.md5(json_encode([
            $from,
            $to,
            $date,
            $cabin,
            $booking->adult_count,
            $booking->child_count,
            $booking->infant_count,
            $booking->currency,
            $airlineCode,
        ]));

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($booking, $from, $to, $date, $cabin, $airlineCode): array {
            $payload = [
                'user_id' => config('services.travelnext.user_id'),
                'user_password' => config('services.travelnext.password'),
                'access' => config('services.travelnext.access'),
                'ip_address' => config('services.travelnext.ip'),
                'requiredCurrency' => $booking->currency ?: 'NGN',
                'journeyType' => 'OneWay',
                'OriginDestinationInfo' => [[
                    'departureDate' => Carbon::parse($date)->toDateString(),
                    'airportOriginCode' => $from,
                    'airportDestinationCode' => $to,
                ]],
                'class' => $this->mapCabin($cabin),
                'adults' => max(1, (int) $booking->adult_count),
                'childs' => max(0, (int) $booking->child_count),
                'infants' => max(0, (int) $booking->infant_count),
            ];

            $response = Http::connectTimeout(10)->timeout(60)
                ->post(config('services.travelnext.base_url').'availability', $payload);

            if ($response->failed()) {
                return [];
            }

            return $this->mapOptions($response->json() ?: [], $cabin, $airlineCode);
        });
    }

    public function decodeOption(?string $value): array
    {
        if (blank($value)) {
            return [];
        }

        $decoded = json_decode(base64_decode((string) $value, true) ?: '', true);

        return is_array($decoded) ? $decoded : [];
    }

    public function airportSearchOptions(?string $search): array
    {
        $search = strtoupper(trim((string) $search));

        if (strlen($search) < 2) {
            return [];
        }

        return $this->airports()
            ->filter(function (array $airport) use ($search): bool {
                return str_contains(strtoupper((string) ($airport['AirportCode'] ?? '')), $search)
                    || str_contains(strtoupper((string) ($airport['AirportName'] ?? '')), $search)
                    || str_contains(strtoupper((string) ($airport['City'] ?? '')), $search)
                    || str_contains(strtoupper((string) ($airport['Country'] ?? '')), $search);
            })
            ->take(50)
            ->mapWithKeys(fn (array $airport): array => [
                (string) $airport['AirportCode'] => $this->airportLabel($airport['AirportCode']),
            ])
            ->all();
    }

    public function airportLabel(?string $code): ?string
    {
        $code = strtoupper(trim((string) $code));

        if ($code === '') {
            return null;
        }

        $airport = $this->airports()->firstWhere('AirportCode', $code);

        if (! is_array($airport)) {
            return $code;
        }

        return trim(sprintf(
            '%s - %s, %s (%s)',
            $airport['AirportCode'] ?? $code,
            $airport['City'] ?? $airport['AirportName'] ?? '',
            $airport['Country'] ?? '',
            $airport['AirportName'] ?? '',
        ));
    }

    private function mapOptions(array $response, string $fallbackCabin, string $airlineCode = ''): array
    {
        $itineraries = data_get($response, 'AirSearchResponse.AirSearchResult.FareItineraries', []);

        if (! is_array($itineraries)) {
            return [];
        }

        $options = [];

        foreach (array_slice(array_values($itineraries), 0, 40) as $index => $item) {
            $fareItinerary = data_get($item, 'FareItinerary', []);
            $odo = data_get($fareItinerary, 'OriginDestinationOptions.0.OriginDestinationOption', []);
            $segments = is_array($odo) ? $this->mapSegments($odo, $fallbackCabin) : [];

            if ($segments === []) {
                continue;
            }

            if ($airlineCode !== '' && ! $this->matchesAirline($segments, $airlineCode)) {
                continue;
            }

            $segments = $this->withSummary($fareItinerary, $segments, $index);
            $value = base64_encode(json_encode($segments, JSON_UNESCAPED_SLASHES));
            $options[$value] = $this->label($fareItinerary, $segments, $index);
        }

        return $options;
    }

    private function airports(): Collection
    {
        return Cache::remember('travelnext-airport-list', now()->addMonth(), function (): Collection {
            $payload = [
                'user_id' => config('services.travelnext.user_id'),
                'user_password' => config('services.travelnext.password'),
                'access' => config('services.travelnext.access'),
                'ip_address' => config('services.travelnext.ip'),
            ];

            $response = Http::connectTimeout(10)->timeout(60)
                ->post(config('services.travelnext.base_url').'airport_list', $payload);

            $airports = $response->failed() ? [] : $this->normalizeAirportResponse($response->json() ?: []);

            if ($airports === []) {
                $localPath = public_path('assets/data/airportsCode.json');
                $airports = is_file($localPath) ? (json_decode(file_get_contents($localPath), true) ?: []) : [];
            }

            return collect($airports)
                ->filter(fn ($airport): bool => is_array($airport) && filled($airport['AirportCode'] ?? null))
                ->map(fn (array $airport): array => [
                    'AirportCode' => strtoupper((string) ($airport['AirportCode'] ?? '')),
                    'AirportName' => (string) ($airport['AirportName'] ?? ''),
                    'City' => (string) ($airport['City'] ?? ''),
                    'Country' => (string) ($airport['Country'] ?? ''),
                ])
                ->values();
        });
    }

    private function normalizeAirportResponse(array $response): array
    {
        if (array_is_list($response)) {
            return $response;
        }

        foreach ([
            'AirportListResponse.AirportListResult.Airports',
            'AirportListResult.Airports',
            'Airports',
            'AirportList',
            'data',
        ] as $path) {
            $airports = data_get($response, $path);

            if (is_array($airports)) {
                return $airports;
            }
        }

        return [];
    }

    private function mapSegments(array $segments, string $fallbackCabin): array
    {
        return collect($segments)
            ->filter(fn ($segment): bool => is_array($segment))
            ->map(function (array $segment) use ($fallbackCabin): array {
                $flightSegment = data_get($segment, 'FlightSegment', []);
                $departure = data_get($flightSegment, 'DepartureDateTime');

                if (blank($departure)) {
                    return [];
                }

                return [
                    'airportOriginCode' => strtoupper((string) data_get($flightSegment, 'DepartureAirportLocationCode')),
                    'airportDestinationCode' => strtoupper((string) data_get($flightSegment, 'ArrivalAirportLocationCode')),
                    'cabinPreference' => strtoupper((string) (data_get($flightSegment, 'CabinClassCode') ?: $fallbackCabin ?: 'Y')),
                    'departureDate' => Carbon::parse($departure)->toDateString(),
                    'airlineCode' => strtoupper((string) data_get($flightSegment, 'MarketingAirlineCode')),
                    'flightNumber' => (string) data_get($flightSegment, 'FlightNumber'),
                    'departDT' => (string) $departure,
                    'arriveDT' => (string) data_get($flightSegment, 'ArrivalDateTime'),
                    'departTime' => Carbon::parse($departure)->format('H:i'),
                    'arriveTime' => filled(data_get($flightSegment, 'ArrivalDateTime')) ? Carbon::parse(data_get($flightSegment, 'ArrivalDateTime'))->format('H:i') : '',
                    'airline' => (string) data_get($flightSegment, 'MarketingAirlineName'),
                    'cabin' => (string) data_get($flightSegment, 'CabinClassText'),
                    'duration' => (int) data_get($flightSegment, 'JourneyDuration', 0),
                    'equipment' => (string) data_get($flightSegment, 'OperatingAirline.Equipment'),
                ];
            })
            ->filter(fn (array $segment): bool => filled($segment['airportOriginCode'] ?? null)
                && filled($segment['airportDestinationCode'] ?? null)
                && filled($segment['departureDate'] ?? null)
                && filled($segment['airlineCode'] ?? null)
                && filled($segment['flightNumber'] ?? null))
            ->values()
            ->all();
    }

    private function label(array $fareItinerary, array $segments, int $index): string
    {
        $first = $segments[0];
        $last = $segments[array_key_last($segments)];
        $flightNumbers = collect($segments)
            ->map(fn (array $segment): string => $segment['airlineCode'].$segment['flightNumber'])
            ->implode(' / ');

        $totalFare = data_get($fareItinerary, 'AirItineraryFareInfo.ItinTotalFares.TotalFare.Amount');
        $currency = data_get($fareItinerary, 'AirItineraryFareInfo.ItinTotalFares.TotalFare.CurrencyCode');
        $stops = max(0, count($segments) - 1);
        $duration = collect($segments)->sum(fn (array $segment): int => (int) ($segment['duration'] ?? 0));
        $airlines = collect($segments)
            ->map(fn (array $segment): string => trim((string) ($segment['airline'] ?? $segment['airlineCode'] ?? '')))
            ->filter()
            ->unique()
            ->implode(' / ');
        $cabins = collect($segments)
            ->map(fn (array $segment): string => trim((string) ($segment['cabin'] ?? $segment['cabinPreference'] ?? '')))
            ->filter()
            ->unique()
            ->implode(' / ');
        $fare = trim((string) $currency.' '.(filled($totalFare) ? number_format((float) $totalFare, 2) : '-'));

        return '<div class="space-y-1 py-1">'
            .'<div class="flex items-start justify-between gap-3">'
            .'<span class="font-semibold text-gray-950 dark:text-white">'.e($flightNumbers ?: 'Option '.($index + 1)).'</span>'
            .'<span class="text-xs font-semibold text-primary-600 dark:text-primary-400">'.e($fare).'</span>'
            .'</div>'
            .'<div class="text-xs text-gray-600 dark:text-gray-300">'.e(($first['airportOriginCode'] ?? '-').' -> '.($last['airportDestinationCode'] ?? '-').' | '.$this->formatDateTime($first['departDT'] ?? null).' - '.$this->formatDateTime($last['arriveDT'] ?? null)).'</div>'
            .'<div class="text-xs text-gray-500 dark:text-gray-400">'.e(($stops === 0 ? 'Nonstop' : $stops.' stop'.($stops === 1 ? '' : 's')).' | '.$this->durationLabel($duration).' | '.($airlines ?: '-').' | '.($cabins ?: '-')).'</div>'
            .'</div>';
    }

    private function withSummary(array $fareItinerary, array $segments, int $index): array
    {
        $first = $segments[0];
        $last = $segments[array_key_last($segments)];
        $totalFare = data_get($fareItinerary, 'AirItineraryFareInfo.ItinTotalFares.TotalFare.Amount');
        $currency = data_get($fareItinerary, 'AirItineraryFareInfo.ItinTotalFares.TotalFare.CurrencyCode');
        $stops = max(0, count($segments) - 1);
        $duration = collect($segments)->sum(fn (array $segment): int => (int) ($segment['duration'] ?? 0));
        $flightNumbers = collect($segments)
            ->map(fn (array $segment): string => trim(($segment['airlineCode'] ?? '').($segment['flightNumber'] ?? '')))
            ->filter()
            ->implode(' / ');

        $segments[0]['optionSummary'] = [
            'option' => $index + 1,
            'route' => trim(($first['airportOriginCode'] ?? '').' -> '.($last['airportDestinationCode'] ?? '')),
            'stops' => $stops,
            'duration' => $duration,
            'flightNumbers' => $flightNumbers,
            'totalFare' => filled($totalFare) ? (float) $totalFare : null,
            'currency' => $currency,
            'departure' => $first['departDT'] ?? null,
            'arrival' => $last['arriveDT'] ?? null,
        ];

        return $segments;
    }

    private function matchesAirline(array $segments, string $airlineCode): bool
    {
        return collect($segments)
            ->every(fn (array $segment): bool => strtoupper((string) ($segment['airlineCode'] ?? '')) === $airlineCode);
    }

    private function formatDateTime(?string $value): string
    {
        if (blank($value)) {
            return '-';
        }

        try {
            return Carbon::parse($value)->timezone('Africa/Lagos')->format('d M H:i');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private function durationLabel(int $minutes): string
    {
        if ($minutes <= 0) {
            return '-';
        }

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        return trim(($hours > 0 ? $hours.'h ' : '').($remainingMinutes > 0 ? $remainingMinutes.'m' : ''));
    }

    private function mapCabin(string $code): string
    {
        return match (strtoupper($code)) {
            'S' => 'PremiumEconomy',
            'C' => 'Business',
            'F' => 'First',
            default => 'Economy',
        };
    }
}
