<x-filament-panels::page>
@include('filament.pages.partials.booking-styles')

@php($isFlightAssist = $tab === 'flight_assist')
<div class="bk">
    <div class="bk-top" style="justify-content:center;">
        <div class="bk-tabs">
            <button type="button" class="bk-tab {{ $isFlightAssist ? 'on' : '' }}" wire:click="switchTab('flight_assist')">Flight Assist</button>
            <button type="button" class="bk-tab {{ $isFlightAssist ? '' : 'on' }}" wire:click="switchTab('yellow_card')">Yellow Card</button>
        </div>
    </div>

    <div class="bk-one">
        @if ($isFlightAssist)
            <section class="bk-step tint">
                <div class="bk-label">1 · The request</div>
                <div class="bk-field"><label>Request type</label></div>
                <div class="bk-options" style="margin-bottom:14px;">
                    <button type="button" class="bk-pay {{ $requestType === 'date_change' ? 'on' : '' }}" wire:click="$set('requestType', 'date_change')">
                        <b>Date Change</b>
                        <small>Move the flight to another date</small>
                    </button>
                    <button type="button" class="bk-pay {{ $requestType === 'rerouting' ? 'on' : '' }}" wire:click="$set('requestType', 'rerouting')">
                        <b>Rerouting</b>
                        <small>Change the route of the flight</small>
                    </button>
                </div>
                @error('requestType') <div class="bk-err">{{ $message }}</div> @enderror

                <div class="bk-field"><label>Where was the ticket booked?</label></div>
                <div class="bk-options">
                    <button type="button" class="bk-pay {{ $bookingSource === 'airline' ? 'on' : '' }}" wire:click="$set('bookingSource', 'airline')">
                        <b>Airline website</b>
                        <small>We can make the change</small>
                    </button>
                    <button type="button" class="bk-pay {{ $bookingSource === 'agent' ? 'on' : '' }}" wire:click="$set('bookingSource', 'agent')">
                        <b>Travel agent</b>
                        <small>The agent makes the change</small>
                    </button>
                </div>
                @error('bookingSource') <div class="bk-err">{{ $message }}</div> @enderror

                @if ($bookingSource === 'agent')
                    <div class="bk-note warn" role="alert">Ask the customer to contact their travel agent for any changes or rerouting requests.</div>
                @endif
            </section>

            @if ($requestType && $bookingSource === 'airline')
                <section class="bk-step">
                    <div class="bk-label">2 · The ticket</div>
                    <div class="bk-grid">
                        <div class="bk-field">
                            <label for="bk-ticket-name">Name on ticket</label>
                            <input id="bk-ticket-name" type="text" wire:model="nameOnTicket">
                            @error('nameOnTicket') <div class="bk-err">{{ $message }}</div> @enderror
                        </div>
                        <div class="bk-field">
                            <label for="bk-airline-ref">Airline reference</label>
                            <input id="bk-airline-ref" type="text" wire:model="airlineReference">
                            @error('airlineReference') <div class="bk-err">{{ $message }}</div> @enderror
                        </div>
                        <div class="bk-field">
                            <label for="bk-airline-cat">Airline category</label>
                            <select id="bk-airline-cat" wire:model.live="airlineCategory">
                                <option value="">Select a category</option>
                                <option value="local">Local airline</option>
                                <option value="international">International airline</option>
                            </select>
                            @error('airlineCategory') <div class="bk-err">{{ $message }}</div> @enderror
                        </div>
                        <div class="bk-field">
                            <label for="bk-airline">Airline</label>
                            <select id="bk-airline" wire:model="airline" @disabled(! $airlines) wire:key="airlines-{{ $airlineCategory }}">
                                <option value="">{{ $airlines ? 'Select an airline' : 'Choose a category first' }}</option>
                                @foreach ($airlines as $name)
                                    <option value="{{ $name }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            @error('airline') <div class="bk-err">{{ $message }}</div> @enderror
                        </div>
                        <div class="bk-field full">
                            <label>Trip type</label>
                            <div class="bk-chips">
                                <button type="button" class="bk-chip {{ $tripType === 'one_way' ? 'on' : '' }}" wire:click="$set('tripType', 'one_way')">One way</button>
                                <button type="button" class="bk-chip {{ $tripType === 'round_trip' ? 'on' : '' }}" wire:click="$set('tripType', 'round_trip')">Round trip</button>
                            </div>
                            @error('tripType') <div class="bk-err">{{ $message }}</div> @enderror
                        </div>
                        @if ($tripType === 'one_way')
                            <div class="bk-field">
                                <label for="bk-travel-date">Travel date</label>
                                <input id="bk-travel-date" type="date" wire:model="travelDate">
                                @error('travelDate') <div class="bk-err">{{ $message }}</div> @enderror
                            </div>
                        @elseif ($tripType === 'round_trip')
                            <div class="bk-field">
                                <label for="bk-departure-date">Departure date</label>
                                <input id="bk-departure-date" type="date" wire:model="departureDate">
                                @error('departureDate') <div class="bk-err">{{ $message }}</div> @enderror
                            </div>
                            <div class="bk-field">
                                <label for="bk-return-date">Return date</label>
                                <input id="bk-return-date" type="date" wire:model="returnDate">
                                @error('returnDate') <div class="bk-err">{{ $message }}</div> @enderror
                            </div>
                        @endif
                        @foreach (['routeFrom' => 'Route from', 'routeTo' => 'Route to'] as $field => $label)
                            <div class="bk-field bk-suggest" x-data="bkAirport(@js($field))" @click.outside="matches = []" wire:key="airport-{{ $field }}">
                                <label for="bk-{{ $field }}">{{ $label }}</label>
                                <input id="bk-{{ $field }}" type="text" autocomplete="off" placeholder="Search airport or city" x-model="query" @input="search()" @focus="load()">
                                <ul x-show="matches.length" x-cloak>
                                    <template x-for="airport in matches" :key="airport.iata">
                                        <li><button type="button" @click="pick(airport)"><b x-text="airport.iata"></b><span x-text="`${airport.name}, ${airport.city}, ${airport.country}`"></span></button></li>
                                    </template>
                                </ul>
                            </div>
                        @endforeach
                        <div class="bk-field full">
                            <label for="bk-preferred">Preferred time (optional)</label>
                            <input id="bk-preferred" type="text" wire:model="preferredTime" placeholder="Morning / Evening">
                        </div>
                    </div>
                </section>

                <section class="bk-step">
                    <div class="bk-label">3 · Customer details</div>
                    <div class="bk-grid">
                        <div class="bk-field">
                            <label for="bk-phone">Phone</label>
                            <input id="bk-phone" type="tel" wire:model="phone">
                            @error('phone') <div class="bk-err">{{ $message }}</div> @enderror
                        </div>
                        <div class="bk-field">
                            <label for="bk-email">Email (as on ticket)</label>
                            <input id="bk-email" type="email" wire:model="email">
                            @error('email') <div class="bk-err">{{ $message }}</div> @enderror
                        </div>
                        <div class="bk-field full">
                            <label for="bk-info">Additional information (optional)</label>
                            <textarea id="bk-info" rows="2" wire:model="additionalInfo"></textarea>
                            @error('additionalInfo') <div class="bk-err">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="bk-note">
                        This service costs <strong>&#8358;{{ number_format($flightAssistPrice) }}</strong>. No payment is taken now: the fee is charged together with the customer's main flight booking fee. The customer is emailed as soon as you submit.
                    </div>
                </section>

                <button type="button" class="bk-submit" wire:click="createFlightAssist" wire:loading.attr="disabled" wire:target="createFlightAssist">
                    <span wire:loading.remove wire:target="createFlightAssist">Submit request</span>
                    <span wire:loading wire:target="createFlightAssist">Submitting…</span>
                </button>
            @endif
        @else
            <section class="bk-step tint">
                <div class="bk-label">1 · Service</div>
                <div class="bk-options">
                    @foreach ($yellowCardServices as $key => $service)
                        <button type="button" class="bk-pay {{ $serviceType === $key ? 'on' : '' }}" wire:click="$set('serviceType', '{{ $key }}')">
                            <b>{{ $service['label'] }}</b>
                            <small>&#8358;{{ number_format($service['price']) }}</small>
                        </button>
                    @endforeach
                </div>
                @error('serviceType') <div class="bk-err">{{ $message }}</div> @enderror
            </section>

            @if ($serviceType)
                <section class="bk-step">
                    <div class="bk-label">2 · Customer details</div>
                    <div class="bk-grid">
                        <div class="bk-field full">
                            <label for="bk-name">Full name</label>
                            <input id="bk-name" type="text" wire:model="fullName">
                            @error('fullName') <div class="bk-err">{{ $message }}</div> @enderror
                        </div>
                        <div class="bk-field">
                            <label for="bk-email">Email</label>
                            <input id="bk-email" type="email" wire:model="email">
                            @error('email') <div class="bk-err">{{ $message }}</div> @enderror
                        </div>
                        <div class="bk-field">
                            <label for="bk-phone">Phone</label>
                            <input id="bk-phone" type="tel" wire:model="phone">
                            @error('phone') <div class="bk-err">{{ $message }}</div> @enderror
                        </div>
                        <div class="bk-field">
                            <label for="bk-home">Home address</label>
                            <input id="bk-home" type="text" wire:model="homeAddress">
                            @error('homeAddress') <div class="bk-err">{{ $message }}</div> @enderror
                        </div>
                        <div class="bk-field">
                            <label for="bk-delivery">Delivery address</label>
                            <input id="bk-delivery" type="text" wire:model="deliveryAddress">
                            @error('deliveryAddress') <div class="bk-err">{{ $message }}</div> @enderror
                        </div>
                        <div class="bk-field full">
                            <label for="bk-data-page">Passport data page (PDF, JPG or PNG)</label>
                            <input id="bk-data-page" type="file" accept=".pdf,.jpg,.jpeg,.png" wire:model="dataPage">
                            <div wire:loading wire:target="dataPage" class="bk-note">Uploading…</div>
                            @error('dataPage') <div class="bk-err">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </section>

                <section class="bk-step">
                    <div class="bk-label">3 · Payment</div>
                    <div class="bk-pays">
                        <button type="button" class="bk-pay {{ $sendLink ? 'on' : '' }}" wire:click="$set('sendLink', true)">
                            <b>Email a payment link</b>
                            <small>The customer pays online by SeerBit</small>
                        </button>
                        <button type="button" class="bk-pay {{ $sendLink ? '' : 'on' }}" wire:click="$set('sendLink', false)">
                            <b>Don't send a link</b>
                            <small>Paying by transfer, POS or cash: Finance records it</small>
                        </button>
                    </div>
                    <button type="button" class="bk-submit" wire:click="createYellowCard" wire:loading.attr="disabled" wire:target="createYellowCard, dataPage">
                        <span wire:loading.remove wire:target="createYellowCard">Create booking · &#8358;{{ number_format($yellowCardServices[$serviceType]['price']) }}</span>
                        <span wire:loading wire:target="createYellowCard">Creating booking…</span>
                    </button>
                </section>
            @endif
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
            this.matches = list.filter((airport) => [airport.iata, airport.name, airport.city, airport.country]
                .some((value) => value && value.toLowerCase().includes(term))).slice(0, 10);
        },
        pick(airport) {
            this.query = `${airport.name} (${airport.iata}), ${airport.city}, ${airport.country}`;
            this.matches = [];
        },
    }));
</script>
@endscript
</x-filament-panels::page>
