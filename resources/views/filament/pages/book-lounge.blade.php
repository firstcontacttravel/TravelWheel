<x-filament-panels::page>
@include('filament.pages.partials.booking-styles')

@php($isNigeria = $tab === 'nigeria')
<div class="bk">
    <div class="bk-top" style="justify-content:center;">
        <div class="bk-tabs">
            <button type="button" class="bk-tab {{ $isNigeria ? 'on' : '' }}" wire:click="switchTab('nigeria')">Nigerian airports</button>
            <button type="button" class="bk-tab {{ $isNigeria ? '' : 'on' }}" wire:click="switchTab('worldwide')">Worldwide</button>
        </div>
    </div>

    <div class="bk-one">
        <section class="bk-step tint">
            <div class="bk-label">1 · Where</div>
            @if ($isNigeria)
                <div class="bk-grid">
                    <div class="bk-field full">
                        <label for="bk-lounge-state">State</label>
                        <select id="bk-lounge-state" wire:model.live="state">
                            <option value="">Choose a state</option>
                            @foreach ($states as $name)
                                <option value="{{ $name }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="bk-field">
                        <label>Airport</label>
                        <div class="bk-chips">
                            <button type="button" class="bk-chip {{ $access === 1 ? 'on' : '' }}" wire:click="setAccess(1)">International</button>
                            <button type="button" class="bk-chip {{ $access === 2 ? 'on' : '' }}" wire:click="setAccess(2)">Local</button>
                        </div>
                    </div>
                    <div class="bk-field">
                        <label>Service</label>
                        <div class="bk-chips">
                            <button type="button" class="bk-chip {{ $service === 'Departure' ? 'on' : '' }}" wire:click="setService('Departure')">Departure</button>
                            <button type="button" class="bk-chip {{ $service === 'Arrival' ? 'on' : '' }}" wire:click="setService('Arrival')">Arrival</button>
                        </div>
                    </div>
                </div>
            @else
                <div class="bk-field">
                    <label for="bk-iata">Airport code</label>
                    <input id="bk-iata" type="text" maxlength="3" placeholder="e.g. LHR, DXB, LOS" wire:model="iata" wire:keydown.enter="searchAirport" style="text-transform:uppercase;">
                </div>
                <button type="button" class="bk-btn-line" wire:click="searchAirport" wire:loading.attr="disabled" wire:target="searchAirport">
                    <span wire:loading.remove wire:target="searchAirport">Find lounges</span>
                    <span wire:loading wire:target="searchAirport">Searching…</span>
                </button>
                @if ($searchError)
                    <div class="bk-route bad" role="alert">{{ $searchError }}</div>
                @endif
            @endif
        </section>

        <section class="bk-step">
            <div class="bk-label">2 · Choose a lounge</div>
            @if ($lounges->isNotEmpty())
                <div class="bk-cats" style="grid-template-columns:repeat(auto-fill, minmax(230px, 1fr));">
                    @foreach ($lounges as $lounge)
                        <button type="button" class="bk-cat {{ $loungeId === $lounge->id ? 'on' : '' }}" wire:click="selectLounge({{ $lounge->id }})" wire:key="lounge-{{ $lounge->id }}">
                            <div class="bk-cat-img" style="height:110px;"><img src="{{ $lounge->imageUrl(0) }}" alt=""></div>
                            <b>{{ $lounge->brand_name }}</b>
                            <em>&#8358;{{ number_format($lounge->bookingPrice('A')) }} per adult</em>
                            <small>{{ $lounge->provider === 'loungepair' ? 'LoungePair: staff place this booking by hand' : $lounge->terminal }}</small>
                        </button>
                    @endforeach
                </div>
                @if ($selected)
                    <div class="bk-spec">
                        <div><span>Adult:</span> &#8358;{{ number_format($selected->bookingPrice('A')) }}</div>
                        <div><span>Child:</span> &#8358;{{ number_format($selected->bookingPrice('B')) }}</div>
                        <div><span>Infant:</span> &#8358;{{ number_format($selected->bookingPrice('C')) }}</div>
                        @if ($facilities = collect(range(1, 5))->map(fn ($i) => $selected->{'facilities'.$i})->filter()->implode(', '))
                            <div><span>Facilities:</span> {{ $facilities }}</div>
                        @endif
                    </div>
                @endif
            @elseif ($searchDone)
                <div class="bk-empty">
                    @if ($isNigeria)
                        No lounge here for this service. Try the Worldwide tab with the airport's code.
                    @else
                        No lounges found at {{ $searchedIata }}.
                    @endif
                </div>
            @else
                <div class="bk-empty">{{ $isNigeria ? 'Choose a state, airport and service first' : 'Search for an airport first' }}</div>
            @endif
            @error('loungeId') <div class="bk-err">{{ $message }}</div> @enderror
        </section>

        @if ($selected)
            <section class="bk-step">
                <div class="bk-label">3 · Guests</div>
                @foreach (['adults' => ['Adults', '12 years and over'], 'children' => ['Children', '2 to 11 years'], 'infants' => ['Infants', 'Under 2 years']] as $type => [$label, $age])
                    <div class="bk-guest" wire:key="guest-{{ $type }}">
                        <div><b>{{ $label }}</b><small>{{ $age }}</small></div>
                        <div class="bk-count">
                            <button type="button" wire:click="changeGuests('{{ $type }}', -1)" aria-label="One fewer {{ $label }}">&minus;</button>
                            <span id="bk-count-{{ $type }}">{{ $this->{$type} }}</span>
                            <button type="button" wire:click="changeGuests('{{ $type }}', 1)" aria-label="One more {{ $label }}">+</button>
                        </div>
                    </div>
                @endforeach

                <div class="bk-price">
                    <h4>Price breakdown</h4>
                    <div><span>Adults ({{ $adults }})</span><b>&#8358;{{ number_format($quote['adult_amount']) }}</b></div>
                    <div><span>Children ({{ $children }})</span><b>&#8358;{{ number_format($quote['child_amount']) }}</b></div>
                    <div><span>Infants ({{ $infants }})</span><b>&#8358;{{ number_format($quote['infant_amount']) }}</b></div>
                    <div><span>VAT ({{ $vatPercent }}%)</span><b>&#8358;{{ number_format($quote['vat']) }}</b></div>
                    <div class="total"><span>Total</span><b>&#8358;{{ number_format($quote['total']) }}</b></div>
                </div>
            </section>

            <section class="bk-step">
                <div class="bk-label">4 · Customer details</div>
                <div class="bk-grid">
                    <div class="bk-field">
                        <label for="bk-first">First name</label>
                        <input id="bk-first" type="text" wire:model="firstName">
                        @error('firstName') <div class="bk-err">{{ $message }}</div> @enderror
                    </div>
                    <div class="bk-field">
                        <label for="bk-last">Last name</label>
                        <input id="bk-last" type="text" wire:model="lastName">
                        @error('lastName') <div class="bk-err">{{ $message }}</div> @enderror
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
                        <label for="bk-date">Service date</label>
                        <input id="bk-date" type="date" min="{{ now()->toDateString() }}" wire:model="serviceDate">
                        @error('serviceDate') <div class="bk-err">{{ $message }}</div> @enderror
                    </div>
                    <div class="bk-field">
                        <label for="bk-time">Departure time</label>
                        <input id="bk-time" type="time" wire:model="departureTime">
                        @error('departureTime') <div class="bk-err">{{ $message }}</div> @enderror
                    </div>
                    <div class="bk-field full">
                        <label for="bk-ticket">Flight ticket number (optional)</label>
                        <input id="bk-ticket" type="text" wire:model="ticketNo">
                        @error('ticketNo') <div class="bk-err">{{ $message }}</div> @enderror
                    </div>
                </div>
            </section>

            <section class="bk-step">
                <div class="bk-label">5 · Payment</div>
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
                <button type="button" class="bk-submit" wire:click="create" wire:loading.attr="disabled" wire:target="create" @disabled($quote['total'] <= 0)>
                    <span wire:loading.remove wire:target="create">Create booking · &#8358;{{ number_format($quote['total']) }}</span>
                    <span wire:loading wire:target="create">Creating booking…</span>
                </button>
            </section>
        @endif
    </div>
</div>
</x-filament-panels::page>
