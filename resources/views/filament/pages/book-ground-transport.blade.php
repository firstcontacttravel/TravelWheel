<x-filament-panels::page>
@include('filament.pages.partials.booking-styles')

@php($isCar = $tab === 'car')
<div class="bk">
    <div class="bk-top">
        <div class="bk-state bk-field">
            <label for="bk-state">State</label>
            <select id="bk-state" wire:model.live="state">
                <option value="">Choose a state</option>
                @foreach ($stateGroups as $region => $regionStates)
                    <optgroup label="{{ $region }}">
                        @foreach ($regionStates as $key => $name)
                            <option value="{{ $key }}">{{ $name }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            @error('state') <div class="bk-err">{{ $message }}</div> @enderror
        </div>
        <div class="bk-tabs">
            <button type="button" class="bk-tab {{ $isCar ? 'on' : '' }}" wire:click="switchTab('car')">Car Rental</button>
            <button type="button" class="bk-tab {{ $isCar ? '' : 'on' }}" wire:click="switchTab('transfer')">Pick up 'n' Drop off</button>
        </div>
    </div>

    <div class="bk-card">
        <aside class="bk-side">
            <div class="bk-label">1 · Vehicle type</div>
            @forelse ($types as $type)
                <button type="button" class="bk-type {{ $vehicleType === $type ? 'on' : '' }}" wire:click="selectType('{{ $type }}')">
                    <span class="bk-thumb"><img src="{{ $typeThumbs[$type] ?? '' }}" alt=""></span>
                    <span>
                        <b>{{ $type === 'van' ? 'Mini Van' : ($type === 'suv' ? 'SUV' : ucfirst($type)) }}</b>
                        <small>{{ $vehicles[$type]['items'][0]['passengers'] ?? '' }}</small>
                    </span>
                </button>
            @empty
                <div class="bk-empty">{{ $state ? 'No vehicles for this service in this state' : 'Choose a state first' }}</div>
            @endforelse
            @error('vehicleType') <div class="bk-err">{{ $message }}</div> @enderror
        </aside>

        <div class="bk-main">
            <section class="bk-step tint">
                <div class="bk-label">2 · Select category</div>
                @if ($categories)
                    <div class="bk-cats">
                        @foreach ($categories as $item)
                            <button type="button" class="bk-cat {{ $category === $item['name'] ? 'on' : '' }}" wire:click="selectCategory('{{ $item['name'] }}')">
                                <div class="bk-cat-img"><img src="{{ $item['images'][0] ?? '' }}" alt=""></div>
                                <b>{{ $item['name'] }}</b>
                                <em>From &#8358;{{ number_format($item['price']) }}</em>
                                <small>{{ $item['passengers'] }}</small>
                            </button>
                        @endforeach
                    </div>
                    @if ($selected)
                        <div class="bk-spec">
                            <div><span>Year:</span> {{ $selected['year_note'] ?? 'Any' }}</div>
                            <div><span>Makes:</span> {{ $selected['makes'] ? implode(', ', $selected['makes']).', or similar' : 'Any available model' }}</div>
                        </div>
                    @endif
                @else
                    <div class="bk-empty">Choose a vehicle type first</div>
                @endif
                @error('category') <div class="bk-err">{{ $message }}</div> @enderror
            </section>

            @if ($selected)
                <section class="bk-step">
                    <div class="bk-label">{{ $isCar ? '3 · Pick-up & duration' : '3 · Your route' }}</div>
                    <div class="bk-grid" x-data="bkPlaces(@js($mapsKey))" x-init="init()">
                        <div class="bk-field {{ $isCar ? 'full' : '' }}" wire:ignore>
                            <label for="bk-pickup">Pick-up location</label>
                            <input id="bk-pickup" type="text" data-places="pickup" wire:model.blur="pickup" placeholder="{{ $isCar ? $addressExample : $airportExample }}">
                        </div>
                        @if (! $isCar)
                            <div class="bk-field" wire:ignore>
                                <label for="bk-dropoff">Drop-off location</label>
                                <input id="bk-dropoff" type="text" data-places="dropoff" wire:model.blur="dropoff" placeholder="{{ $addressExample }}">
                            </div>
                        @endif
                    </div>
                    @error('pickup') <div class="bk-err">{{ $message }}</div> @enderror
                    @error('dropoff') <div class="bk-err">{{ $message }}</div> @enderror

                    @if ($isCar)
                        <div class="bk-field" style="margin-top:12px;">
                            <label>Rental duration</label>
                            <div class="bk-chips">
                                @foreach ([2, 4, 6, 8, 12] as $option)
                                    <button type="button" class="bk-chip {{ ! $customHours && (float) $hours === (float) $option ? 'on' : '' }}" wire:click="setHours({{ $option }})">{{ $option }}h</button>
                                @endforeach
                                <button type="button" class="bk-chip {{ $customHours ? 'on' : '' }}" wire:click="setHours(null)">Custom</button>
                                @if ($customHours)
                                    <input type="number" min="1" placeholder="Hours" wire:model.live.debounce.400ms="hours" aria-label="Hours">
                                @endif
                            </div>
                            @error('hours') <div class="bk-err">{{ $message }}</div> @enderror
                        </div>
                    @else
                        <button type="button" class="bk-btn-line" wire:click="getRoute" wire:loading.attr="disabled" wire:target="getRoute">
                            <span wire:loading.remove wire:target="getRoute">Get price for this route</span>
                            <span wire:loading wire:target="getRoute">Checking the route…</span>
                        </button>
                        @if ($routeError)
                            <div class="bk-route bad" role="alert">{{ $routeError }}</div>
                        @elseif ($routeKm)
                            <div class="bk-route">{{ $routeKm }} km · about {{ $routeDrive }} drive</div>
                        @endif
                        @error('routeMins') <div class="bk-err">{{ $message }}</div> @enderror
                    @endif

                    @if ($quote)
                        <div class="bk-price">
                            <h4>Price breakdown</h4>
                            <div><span>Base fare</span><b>&#8358;{{ number_format($quote['base_fare']) }}</b></div>
                            <div><span>Tear &amp; wear ({{ $quote['wear_percent'] }}%)</span><b>&#8358;{{ number_format($quote['tear_wear_amount']) }}</b></div>
                            <div><span>Fuel</span><b>&#8358;{{ number_format($quote['fuel_amount']) }}</b></div>
                            <div><span>Admin fee ({{ $quote['admin_fee_percent'] }}%)</span><b>&#8358;{{ number_format($quote['admin_fee_amount']) }}</b></div>
                            <div class="total"><span>Total</span><b>&#8358;{{ number_format($quote['total']) }}</b></div>
                        </div>
                    @endif
                </section>
            @endif

            @if ($quote)
                <section class="bk-step">
                    <div class="bk-label">4 · Customer details</div>
                    <div class="bk-grid">
                        <div class="bk-field full">
                            <label for="bk-name">Customer name</label>
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
                            <label for="bk-date">Pick-up date</label>
                            <input id="bk-date" type="date" min="{{ now()->toDateString() }}" wire:model="pickupDate">
                            @error('pickupDate') <div class="bk-err">{{ $message }}</div> @enderror
                        </div>
                        <div class="bk-field">
                            <label for="bk-time">Pick-up time</label>
                            <input id="bk-time" type="time" wire:model="pickupTime">
                            @error('pickupTime') <div class="bk-err">{{ $message }}</div> @enderror
                        </div>
                        <div class="bk-field">
                            <label for="bk-pax">Passengers{{ $passengerLimit ? " (up to {$passengerLimit})" : '' }}</label>
                            <input id="bk-pax" type="number" min="1" @if ($passengerLimit) max="{{ $passengerLimit }}" @endif wire:model="passengers">
                            @error('passengers') <div class="bk-err">{{ $message }}</div> @enderror
                        </div>
                        @unless ($isCar)
                            <div class="bk-field">
                                <label for="bk-flight">Flight / vessel no. (optional)</label>
                                <input id="bk-flight" type="text" wire:model="flightNumber">
                            </div>
                            <div class="bk-field full">
                                <label for="bk-notes">Special requests (optional)</label>
                                <input id="bk-notes" type="text" wire:model="specialRequests" placeholder="e.g. child seat, extra luggage">
                            </div>
                        @endunless
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
                    <button type="button" class="bk-submit" wire:click="create" wire:loading.attr="disabled" wire:target="create">
                        <span wire:loading.remove wire:target="create">Create booking · &#8358;{{ number_format($quote['total']) }}</span>
                        <span wire:loading wire:target="create">Creating booking…</span>
                    </button>
                </section>
            @endif
        </div>
    </div>
</div>

@if ($mapsKey)
    @script
    <script>
        // Same Google address suggestions as the website's booking page
        Alpine.data('bkPlaces', (key) => ({
            init() {
                this.$nextTick(() => this.whenReady(() => this.attach()));
            },
            whenReady(done) {
                if (window.google?.maps?.places) return done();
                window.bkPlacesQueue = window.bkPlacesQueue || [];
                window.bkPlacesQueue.push(done);
                if (document.getElementById('bk-maps')) return;
                window.bkPlacesReady = () => window.bkPlacesQueue.splice(0).forEach((run) => run());
                const script = document.createElement('script');
                script.id = 'bk-maps';
                script.async = true;
                script.src = `https://maps.googleapis.com/maps/api/js?key=${key}&libraries=places&callback=bkPlacesReady`;
                document.head.appendChild(script);
            },
            attach() {
                this.$el.querySelectorAll('[data-places]').forEach((input) => {
                    const places = new google.maps.places.Autocomplete(input, { componentRestrictions: { country: 'ng' } });
                    places.addListener('place_changed', () => {
                        const place = places.getPlace();
                        if (place.formatted_address) {
                            input.value = place.name && !place.formatted_address.startsWith(place.name)
                                ? `${place.name}, ${place.formatted_address}`
                                : place.formatted_address;
                        }
                        $wire.set(input.dataset.places, input.value);
                    });
                });
            },
        }));
    </script>
    @endscript
@else
    <script>document.addEventListener('alpine:init', () => Alpine.data('bkPlaces', () => ({ init() {} })));</script>
@endif
</x-filament-panels::page>
