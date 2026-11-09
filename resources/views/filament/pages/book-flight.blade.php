<x-filament-panels::page>
@include('filament.pages.partials.booking-styles')

@php
    $legLine = function (array $segments, ?string $duration, $stops): string {
        if ($segments === []) {
            return '';
        }
        $first = $segments[0];
        $last = $segments[array_key_last($segments)];
        $time = fn ($dt) => $dt ? \Carbon\Carbon::parse($dt)->format('D j M, H:i') : '';

        return ($first['from'] ?? '?').' '.$time($first['departDT'] ?? null).' → '.($last['to'] ?? '?').' '.$time($last['arriveDT'] ?? null)
            .($duration ? ' · '.$duration : '').' · '.((int) $stops === 0 ? 'Direct' : (int) $stops.' stop'.((int) $stops === 1 ? '' : 's'));
    };
@endphp

<div class="bk">
    <div class="bk-one">
        <section class="bk-step tint">
            <div class="bk-label">1 · Search</div>
            <div class="bk-options" style="margin-bottom:14px;">
                <button type="button" class="bk-pay {{ $trip === 'oneway' ? 'on' : '' }}" wire:click="setTrip('oneway')"><b>One way</b></button>
                <button type="button" class="bk-pay {{ $trip === 'return' ? 'on' : '' }}" wire:click="setTrip('return')"><b>Return</b></button>
            </div>
            <div class="bk-grid">
                @foreach (['from' => 'From', 'to' => 'To'] as $field => $label)
                    <div class="bk-field bk-suggest" x-data="bkAirport(@js($field))" @click.outside="matches = []" wire:key="airport-{{ $field }}">
                        <label for="bk-{{ $field }}">{{ $label }}</label>
                        <input id="bk-{{ $field }}" type="text" autocomplete="off" placeholder="Search airport or city" x-model="query" @input="search()" @focus="load()">
                        <ul x-show="matches.length" x-cloak>
                            <template x-for="airport in matches" :key="airport.iata">
                                <li><button type="button" @click="pick(airport)"><b x-text="airport.iata"></b><span x-text="`${airport.name}, ${airport.city}, ${airport.country}`"></span></button></li>
                            </template>
                        </ul>
                        @error($field) <div class="bk-err">{{ $message }}</div> @enderror
                    </div>
                @endforeach
                <div class="bk-field">
                    <label for="bk-depart">Departure</label>
                    <input id="bk-depart" type="date" min="{{ now()->toDateString() }}" wire:model.live="depart">
                    @error('depart') <div class="bk-err">{{ $message }}</div> @enderror
                </div>
                @if ($trip === 'return')
                    <div class="bk-field">
                        <label for="bk-returning">Return</label>
                        <input id="bk-returning" type="date" min="{{ $depart ?: now()->toDateString() }}" wire:model.live="returning">
                        @error('returning') <div class="bk-err">{{ $message }}</div> @enderror
                    </div>
                @endif
                <div class="bk-field">
                    <label for="bk-cabin">Cabin</label>
                    <select id="bk-cabin" wire:model.live="cabin">
                        @foreach ($cabins as $code => $name)
                            <option value="{{ $code }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="margin-top:12px;">
                @foreach (['adults' => ['Adults', '12 years and over'], 'children' => ['Children', '2 to 11 years'], 'infants' => ['Infants', 'Under 2 years, no more than adults']] as $type => [$label, $age])
                    <div class="bk-guest" wire:key="pax-count-{{ $type }}">
                        <div><b>{{ $label }}</b><small>{{ $age }}</small></div>
                        <div class="bk-count">
                            <button type="button" wire:click="changeCount('{{ $type }}', -1)" aria-label="One fewer {{ $label }}">&minus;</button>
                            <span id="bk-count-{{ $type }}">{{ $this->{$type} }}</span>
                            <button type="button" wire:click="changeCount('{{ $type }}', 1)" aria-label="One more {{ $label }}">+</button>
                        </div>
                    </div>
                @endforeach
                @error('adults') <div class="bk-err">{{ $message }}</div> @enderror
                @error('infants') <div class="bk-err">{{ $message }}</div> @enderror
            </div>

            <button type="button" class="bk-btn-line" wire:click="search" wire:loading.attr="disabled" wire:target="search">
                <span wire:loading.remove wire:target="search">{{ $searchId ? 'Search again' : 'Search flights' }}</span>
                <span wire:loading wire:target="search">Searching the airlines… this can take up to a minute</span>
            </button>
            @if ($searchError)
                <div class="bk-route bad" role="alert">{{ $searchError }}</div>
            @endif
            <div class="bk-note" style="margin-top:10px;">Only fares the airline can hold are shown. Fares paid by card at booking (SkyLink and WebFare) can't be held for a customer.</div>
        </section>

        @if ($searchId && ! $fare)
            <section class="bk-step">
                <div class="bk-label">2 · Choose a fare</div>
                @if ($fareError)
                    <div class="bk-route bad" role="alert" style="margin:0 0 12px;">{{ $fareError }}</div>
                @endif
                @forelse ($flights as $flight)
                    <div class="bk-price" wire:key="fare-{{ $flight['source'] }}-{{ md5($flight['fareSourceCode']) }}" style="margin-top:0; margin-bottom:12px;">
                        <h4>{{ $flight['airline'] ?: 'Airline' }} · {{ $flight['fareType'] ?? 'Public' }}</h4>
                        <div><span>Outbound</span><b style="font-weight:500;">{{ $legLine($flight['segments'] ?? [], $flight['durationLabel'] ?? null, $flight['stops'] ?? 0) }}</b></div>
                        @if (! empty($flight['returnSegments']))
                            <div><span>Return</span><b style="font-weight:500;">{{ $legLine($flight['returnSegments'], $flight['returnDurationLabel'] ?? null, $flight['returnStops'] ?? 0) }}</b></div>
                        @endif
                        <div class="total">
                            <span>{{ ($flight['isRefundable'] ?? false) ? 'Refundable' : 'Non-refundable' }}</span>
                            <b>{{ $flight['currency'] ?? 'NGN' }} {{ number_format((float) $flight['price'], 2) }}</b>
                        </div>
                        <button type="button" class="bk-btn-line" style="margin-top:10px;" wire:click="choose(@js($flight['source']), @js($flight['fareSourceCode']))" wire:loading.attr="disabled" wire:target="choose">
                            <span wire:loading.remove wire:target="choose">Choose this fare</span>
                            <span wire:loading wire:target="choose">Confirming with the airline…</span>
                        </button>
                    </div>
                @empty
                    <div class="bk-empty">No fares that can be held were found. Try other dates or airports.</div>
                @endforelse
            </section>
        @endif

        @if ($fare)
            <section class="bk-step">
                <div class="bk-label">2 · Confirmed fare</div>
                <div class="bk-price" style="margin-top:0;">
                    <h4>{{ $fare['airline'] ?: 'Airline' }} · {{ $fare['fareType'] ?? 'Public' }}</h4>
                    <div><span>Outbound</span><b style="font-weight:500;">{{ $legLine($fare['segments'] ?? [], $fare['durationLabel'] ?? null, $fare['stops'] ?? 0) }}</b></div>
                    @if (! empty($fare['returnSegments']))
                        <div><span>Return</span><b style="font-weight:500;">{{ $legLine($fare['returnSegments'], $fare['returnDurationLabel'] ?? null, $fare['returnStops'] ?? 0) }}</b></div>
                    @endif
                    <div class="total"><span>Total for all passengers</span><b>{{ $fare['currency'] ?? 'NGN' }} {{ number_format((float) $fare['price'], 2) }}</b></div>
                </div>
                <button type="button" class="bk-btn-line" style="margin-top:10px;" wire:click="changeFare" wire:loading.attr="disabled">Choose a different fare</button>
                @if ($fare['isPassportMandatory'] ?? false)
                    <div class="bk-note" style="margin-top:10px;">This airline needs every passenger's passport details.</div>
                @endif
            </section>

            @foreach ($passengers as $i => $pax)
                <section class="bk-step" wire:key="pax-{{ $i }}">
                    <div class="bk-label">{{ ['ADT' => 'Adult', 'CHD' => 'Child', 'INF' => 'Infant'][$pax['type']] }} {{ collect($passengers)->take($i + 1)->where('type', $pax['type'])->count() }}</div>
                    <div class="bk-grid">
                        <div class="bk-field">
                            <label for="bk-pax-{{ $i }}-title">Title</label>
                            <select id="bk-pax-{{ $i }}-title" wire:model="passengers.{{ $i }}.title">
                                <option value="">Choose</option>
                                @foreach ($titles[$pax['type']] as $title)
                                    <option value="{{ $title }}">{{ $title }}</option>
                                @endforeach
                            </select>
                            @error("passengers.$i.title") <div class="bk-err">{{ $message }}</div> @enderror
                        </div>
                        <div class="bk-field">
                            <label for="bk-pax-{{ $i }}-gender">Gender</label>
                            <select id="bk-pax-{{ $i }}-gender" wire:model="passengers.{{ $i }}.gender">
                                <option value="">Choose</option>
                                <option value="M">Male</option>
                                <option value="F">Female</option>
                            </select>
                            @error("passengers.$i.gender") <div class="bk-err">{{ $message }}</div> @enderror
                        </div>
                        @foreach (['first_name' => 'First name (as on passport)', 'last_name' => 'Last name (as on passport)'] as $field => $label)
                            <div class="bk-field">
                                <label for="bk-pax-{{ $i }}-{{ $field }}">{{ $label }}</label>
                                <input id="bk-pax-{{ $i }}-{{ $field }}" type="text" wire:model="passengers.{{ $i }}.{{ $field }}">
                                @error("passengers.$i.$field") <div class="bk-err">{{ $message }}</div> @enderror
                            </div>
                        @endforeach
                        <div class="bk-field">
                            <label for="bk-pax-{{ $i }}-dob">Date of birth</label>
                            <input id="bk-pax-{{ $i }}-dob" type="date" max="{{ now()->subDay()->toDateString() }}" wire:model="passengers.{{ $i }}.dob">
                            @error("passengers.$i.dob") <div class="bk-err">{{ $message }}</div> @enderror
                        </div>
                        <div class="bk-field">
                            <label for="bk-pax-{{ $i }}-nationality">Nationality</label>
                            <select id="bk-pax-{{ $i }}-nationality" wire:model="passengers.{{ $i }}.nationality">
                                @foreach ($countries as $code => $name)
                                    <option value="{{ $code }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            @error("passengers.$i.nationality") <div class="bk-err">{{ $message }}</div> @enderror
                        </div>
                        <div class="bk-field">
                            <label for="bk-pax-{{ $i }}-passport_no">Passport number{{ ($fare['isPassportMandatory'] ?? false) ? '' : ' (optional)' }}</label>
                            <input id="bk-pax-{{ $i }}-passport_no" type="text" wire:model="passengers.{{ $i }}.passport_no">
                            @error("passengers.$i.passport_no") <div class="bk-err">{{ $message }}</div> @enderror
                        </div>
                        <div class="bk-field">
                            <label for="bk-pax-{{ $i }}-passport_issue_country">Passport issued by</label>
                            <select id="bk-pax-{{ $i }}-passport_issue_country" wire:model="passengers.{{ $i }}.passport_issue_country">
                                <option value="">Choose</option>
                                @foreach ($countries as $code => $name)
                                    <option value="{{ $code }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            @error("passengers.$i.passport_issue_country") <div class="bk-err">{{ $message }}</div> @enderror
                        </div>
                        <div class="bk-field">
                            <label for="bk-pax-{{ $i }}-passport_issue_date">Passport issue date</label>
                            <input id="bk-pax-{{ $i }}-passport_issue_date" type="date" wire:model="passengers.{{ $i }}.passport_issue_date">
                            @error("passengers.$i.passport_issue_date") <div class="bk-err">{{ $message }}</div> @enderror
                        </div>
                        <div class="bk-field">
                            <label for="bk-pax-{{ $i }}-passport_exp">Passport expiry date</label>
                            <input id="bk-pax-{{ $i }}-passport_exp" type="date" wire:model="passengers.{{ $i }}.passport_exp">
                            @error("passengers.$i.passport_exp") <div class="bk-err">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </section>
            @endforeach

            <section class="bk-step">
                <div class="bk-label">Customer's contact</div>
                <div class="bk-grid">
                    <div class="bk-field">
                        <label for="bk-email">Email</label>
                        <input id="bk-email" type="email" wire:model="contact.email">
                        @error('contact.email') <div class="bk-err">{{ $message }}</div> @enderror
                    </div>
                    <div class="bk-field">
                        <label for="bk-phone">Phone</label>
                        <input id="bk-phone" type="tel" wire:model="contact.phone">
                        @error('contact.phone') <div class="bk-err">{{ $message }}</div> @enderror
                    </div>
                </div>
            </section>

            <section class="bk-step">
                <div class="bk-label">Payment</div>
                <div class="bk-pays">
                    <button type="button" class="bk-pay {{ $sendLink ? 'on' : '' }}" wire:click="$set('sendLink', true)">
                        <b>Email a payment link</b>
                        <small>The customer pays online before the hold runs out</small>
                    </button>
                    <button type="button" class="bk-pay {{ $sendLink ? '' : 'on' }}" wire:click="$set('sendLink', false)">
                        <b>Don't send a link</b>
                        <small>Paying by transfer, POS or cash: Finance records it</small>
                    </button>
                </div>
                <div class="bk-note" style="margin-bottom:12px;">The fare is held with the airline, not paid. Once it is paid, the flight team orders the ticket.</div>
                @if ($fareError)
                    <div class="bk-route bad" role="alert" style="margin:0 0 12px;">{{ $fareError }}</div>
                @endif
                @error('passengers') <div class="bk-err" style="margin-bottom:12px;">{{ $message }}</div> @enderror
                <button type="button" class="bk-submit" wire:click="create" wire:loading.attr="disabled" wire:target="create">
                    <span wire:loading.remove wire:target="create">Hold the fare · {{ $fare['currency'] ?? 'NGN' }} {{ number_format((float) $fare['price'], 2) }}</span>
                    <span wire:loading wire:target="create">Holding the fare with the airline…</span>
                </button>
            </section>
        @endif
    </div>
</div>

@script
<script>
    // The website's airport list, fetched once and only when someone starts searching
    let airports = null;

    Alpine.data('bkAirport', (field) => ({
        query: $wire.entangle(field),
        matches: [],
        load() {
            airports ??= fetch(@js(asset('assets/data/airports.json'))).then((response) => response.json());
            return airports;
        },
        async search() {
            const term = (this.query || '').toLowerCase();
            if (term.length < 2) {
                this.matches = [];
                return;
            }
            const list = await this.load();
            // An airport's own code first (LOS is Lagos, not Los Llanos), then its city, then anything else
            const rank = (airport) => (airport.iata || '').toLowerCase() === term ? 0
                : (airport.city || '').toLowerCase().startsWith(term) ? 1 : 2;
            this.matches = list.filter((airport) => [airport.iata, airport.name, airport.city, airport.country]
                .some((value) => value && value.toLowerCase().includes(term)))
                .sort((a, b) => rank(a) - rank(b)).slice(0, 10);
        },
        pick(airport) {
            // Ending in the code, as the flight search reads it
            this.query = `${airport.city} - ${airport.name} (${airport.iata})`;
            this.matches = [];
        },
    }));
</script>
@endscript
</x-filament-panels::page>
