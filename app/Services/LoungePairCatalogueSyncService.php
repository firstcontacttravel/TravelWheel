<?php

namespace App\Services;

use App\Models\Lounge;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LoungePairCatalogueSyncService
{
    public function __construct(private readonly LoungePairService $loungePair)
    {
    }

    /** @return array{created: int, updated: int, skipped: int} */
    public function sync(string $iata): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($this->loungePair->loungesForAirport($iata) as $remoteLounge) {
            $providerId = $this->stringValue($remoteLounge, ['id', 'slug', 'lounge_id', 'loungeId', 'uuid', 'code']);

            if ($providerId === '') {
                $skipped++;
                continue;
            }

            $lounge = Lounge::query()
                ->where('provider', 'loungepair')
                ->where('provider_lounge_id', $providerId)
                ->first();

            $attributes = $this->attributes($remoteLounge, $providerId, $lounge);

            if ($lounge) {
                $lounge->fill($attributes)->save();
                $updated++;
            } else {
                Lounge::create($attributes);
                $created++;
            }
        }

        return compact('created', 'updated', 'skipped');
    }

    /** @param array<string, mixed> $record
     *  @return array<string, mixed>
     */
    private function attributes(array $record, string $providerId, ?Lounge $existing): array
    {
        $airport = $this->arrayValue($record, ['airport']);
        $city = $this->value($record, ['city', 'location.city', 'airport.city', 'airport.location.city'])
            ?: $this->value($airport, ['city', 'location.city'])
            ?: $this->value($record, ['country', 'airport.country'])
            ?: 'Unknown';
        $airportCode = $this->value($record, ['airport_iata', 'airportCode', 'airport.code', 'airport.iata'])
            ?: $this->value($airport, ['iata', 'code', 'id'])
            ?: 'Unknown';
        $facilities = $this->strings($this->value($record, ['facilities', 'amenities', 'features', 'services']));
        $images = $this->imageUrls($record);
        $prices = $this->arrayValue($record, ['prices', 'pricing', 'price']);
        $pageUrl = $this->value($record, ['url', 'deepLink', 'deep_link', 'link']);

        // The airport-list payload LoungePair grants us access to doesn't
        // include amenities (the lounge-detail endpoint that would requires
        // a 'lounges:read' scope our credentials don't have). Fall back to
        // the public lounge page's embedded Schema.org data, but only for
        // lounges that don't already have real facilities on file, so a
        // routine re-sync doesn't re-fetch every page every time.
        $alreadyHasFacilities = $existing && $existing->facilities1 !== null && $existing->facilities1 !== 'Not specified';
        if ($facilities === [] && ! $alreadyHasFacilities && is_string($pageUrl) && $pageUrl !== '') {
            $facilities = $this->amenitiesFromPublicPage($pageUrl);
        }

        return [
            // Preserve a compact local ID for legacy screens; the full provider
            // identifier is stored separately and used as the sync key.
            'lounge_id' => Str::limit('LP-'.$providerId, 50, ''),
            'provider' => 'loungepair',
            'provider_lounge_id' => Str::limit($providerId, 100, ''),
            'provider_airport_iata' => Str::upper($this->value($record, ['airport_iata', 'airport.iata', 'airport.code']) ?: ''),
            'brand_name' => Str::limit($this->value($record, ['name', 'brand_name', 'title']) ?: 'LoungePair lounge', 50, ''),
            'email' => Str::limit($this->value($record, ['email', 'contact.email']) ?: '', 100, ''),
            'phone_no' => Str::limit($this->value($record, ['phone', 'phone_no', 'contact.phone']) ?: '', 50, ''),
            'location' => Str::limit($city, 50, ''),
            // Existing screens use this as a Nigerian terminal type. Keep an
            // external airport identifier in provider_payload instead.
            'airport' => $existing?->airport ?? 0,
            'service' => Str::limit($this->value($record, ['service', 'access_type']) ?: '', 50, ''),
            'terminal' => Str::limit($this->value($record, ['terminal', 'terminal_name']) ?: $airportCode, 50, ''),
            'description' => $this->value($record, ['description', 'summary', 'content']) ?: 'Details supplied by LoungePair.',
            'facilities1' => $facilities[0] ?? 'Not specified',
            'facilities2' => $facilities[1] ?? 'Not specified',
            'facilities3' => $facilities[2] ?? 'Not specified',
            'facilities4' => $facilities[3] ?? 'Not specified',
            'facilities5' => $facilities[4] ?? 'Not specified',
            'given_PriceA' => $this->headlinePrice($record)
                ?? $this->price($prices, ['adult', 'adult_price', 'per_person', 'amount', 'value'])
                ?? $this->numericValue($record, ['adult_price', 'price', 'amount']),
            'given_PriceB' => $this->price($prices, ['child', 'child_price'])
                ?? $this->numericValue($record, ['child_price']),
            'given_PriceC' => $this->price($prices, ['infant', 'infant_price'])
                ?? $this->numericValue($record, ['infant_price']),
            // Pricing and settlement are provider-controlled; never overwrite
            // a locally configured markup during a catalogue refresh.
            'markup_price' => $existing?->markup_price ?? 0,
            'provider_currency' => Str::upper($this->value($record, ['currency', 'prices.currency', 'pricing.currency']) ?: $this->headlineCurrency($record) ?: (string) config('services.loungepair.currency')) ?: null,
            'provider_url' => $pageUrl,
            'provider_images' => $images ?: null,
            'provider_payload' => $record,
            'provider_synced_at' => now(),
            // Legacy columns are required. Remote images are rendered through
            // imageUrl(); these values are harmless fallbacks for older views.
            'pics1' => $existing?->pics1 ?? '',
            'pics2' => $existing?->pics2 ?? '',
            'pics3' => $existing?->pics3 ?? '',
            'pics4' => $existing?->pics4 ?? '',
            'pics5' => $existing?->pics5 ?? '',
        ];
    }

    /**
     * Scrape amenities from a lounge's public LoungePair page. That page
     * embeds a Schema.org JSON-LD block (a 'Place' node with an
     * amenityFeature list) meant for search engines — it's structured data,
     * not screen-scraped text, but it's still an unofficial fallback: cached
     * for a week per URL, and any failure just yields an empty list rather
     * than breaking the sync.
     *
     * @return array<int, string>
     */
    private function amenitiesFromPublicPage(string $url): array
    {
        return Cache::remember('loungepair:amenities:'.md5($url), now()->addWeek(), function () use ($url): array {
            try {
                $response = Http::timeout(10)->connectTimeout(5)->get($url);
            } catch (\Throwable $exception) {
                Log::warning('LoungePair amenities scrape failed', ['url' => $url, 'error' => $exception->getMessage()]);

                return [];
            }

            if ($response->failed()) {
                return [];
            }

            if (! preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $response->body(), $matches)) {
                return [];
            }

            foreach ($matches[1] as $json) {
                $data = json_decode($json, true);
                $nodes = is_array($data['@graph'] ?? null) ? $data['@graph'] : [$data];

                foreach ($nodes as $node) {
                    $features = $node['amenityFeature'] ?? null;

                    if (! is_array($features)) {
                        continue;
                    }

                    $names = collect($features)
                        ->filter(fn ($feature) => is_array($feature) && ($feature['value'] ?? false))
                        ->map(fn ($feature) => is_string($feature['name'] ?? null) ? $feature['name'] : null)
                        ->filter()
                        ->take(5)
                        ->values()
                        ->all();

                    if ($names !== []) {
                        return $names;
                    }
                }
            }

            return [];
        });
    }

    /** @param array<string, mixed> $record */
    private function imageUrls(array $record): array
    {
        // LoungePair's airport payload returns a single 'image' string per
        // lounge (confirmed against their live API); the plural keys are
        // kept as fallbacks in case a gallery shape shows up elsewhere.
        $images = $this->value($record, ['image', 'images', 'image_urls', 'photos', 'media']);

        return collect(is_array($images) ? $images : [$images])
            ->map(fn ($image) => is_array($image) ? Arr::first($image, fn ($value, $key) => in_array($key, ['url', 'src', 'original'], true)) : $image)
            ->filter(fn ($image) => is_string($image) && filter_var($image, FILTER_VALIDATE_URL))
            ->take(5)
            ->values()
            ->all();
    }

    /** @param array<string, mixed> $record */
    private function stringValue(array $record, array $paths): string
    {
        return (string) ($this->value($record, $paths) ?? '');
    }

    /** @param array<string, mixed> $record */
    private function arrayValue(array $record, array $paths): array
    {
        $value = $this->value($record, $paths);

        return is_array($value) ? $value : [];
    }

    /** @param array<string, mixed> $record */
    private function value(array $record, array $paths): mixed
    {
        foreach ($paths as $path) {
            $value = data_get($record, $path);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function price(array $prices, array $keys): ?float
    {
        $price = $this->value($prices, $keys);

        return is_numeric($price) ? (float) $price : null;
    }

    /** @param array<string, mixed> $record */
    private function headlinePrice(array $record): ?float
    {
        $fromPrice = data_get($record, 'fromPrice');

        if (is_numeric($fromPrice)) {
            return (float) $fromPrice;
        }

        if (! is_array($fromPrice)) {
            return null;
        }

        // LoungePair returns fromPrice as a list of tiers (one per audience/
        // duration), e.g. [{"amount":24,"currency":"USD",...}] — use the
        // first tier when the array is a list rather than a flat object.
        $tier = array_is_list($fromPrice) ? ($fromPrice[0] ?? null) : $fromPrice;

        return is_array($tier) ? $this->numericValue($tier, ['amount', 'value', 'price']) : null;
    }

    /** @param array<string, mixed> $record */
    private function headlineCurrency(array $record): ?string
    {
        $fromPrice = data_get($record, 'fromPrice');

        if (! is_array($fromPrice)) {
            return null;
        }

        $tier = array_is_list($fromPrice) ? ($fromPrice[0] ?? null) : $fromPrice;

        return is_array($tier) ? $this->value($tier, ['currency']) : null;
    }

    /** @param array<string, mixed> $record */
    private function numericValue(array $record, array $paths): ?float
    {
        $value = $this->value($record, $paths);

        return is_numeric($value) ? (float) $value : null;
    }

    private function strings(mixed $value): array
    {
        if (is_string($value)) {
            return [$value];
        }

        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->map(fn ($item) => is_string($item) ? $item : (is_array($item) ? (string) ($item['name'] ?? $item['label'] ?? '') : ''))
            ->filter()
            ->values()
            ->all();
    }
}
