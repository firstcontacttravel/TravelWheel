<x-filament-panels::page>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    .bk { font-family: 'DM Sans', sans-serif; color: #1a1a1a; max-width: 1160px; width: 100%; margin: 0 auto; }
    .bk *, .bk *::before, .bk *::after { box-sizing: border-box; }

    .bk-top { display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end; justify-content: space-between; margin-bottom: 18px; }
    .bk-state { background: #fff; border: 1px solid #e0ddd6; border-radius: 14px; padding: 14px 16px; box-shadow: 0 2px 10px rgba(0,0,0,.05); flex: 1 1 280px; max-width: 420px; }
    .bk-tabs { display: flex; background: #fff; border: 1px solid #e0ddd6; border-radius: 14px; padding: 5px; box-shadow: 0 2px 8px rgba(0,0,0,.05); }
    .bk-tab { padding: 10px 22px; border: 0; border-radius: 10px; background: none; color: #777; font: 600 13.5px 'DM Sans', sans-serif; cursor: pointer; transition: background .2s, color .2s; }
    .bk-tab:hover { background: #f0f3ff; color: #0d1883; }
    .bk-tab.on { background: #0d1883; color: #fff; box-shadow: 0 4px 14px rgba(13,24,131,.25); }

    .bk-card { background: #fff; border: 1px solid #e0ddd6; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,.06); display: grid; grid-template-columns: 220px 1fr; overflow: hidden; }
    .bk-side { background: #f7f8ff; border-right: 1px solid #e8eaf5; padding: 22px 16px; }
    .bk-main { padding: 22px 20px; display: flex; flex-direction: column; gap: 16px; min-width: 0; }
    .bk-step { background: #fff; border: 1px solid #e4e6f0; border-radius: 14px; padding: 18px; }
    .bk-step.tint { background: #fafbff; }
    .bk-label { display: inline-flex; background: #eef1ff; color: #0d1883; font-size: 10px; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; padding: 4px 10px; border-radius: 20px; margin-bottom: 14px; }
    .bk-empty { display: flex; align-items: center; justify-content: center; min-height: 110px; color: #aaa; font-size: 13px; text-align: center; }

    .bk-type { position: relative; display: flex; width: 100%; align-items: center; gap: 10px; padding: 10px 11px; margin-bottom: 8px; border: 1.5px solid #dde0f0; border-radius: 10px; background: #fff; text-align: left; cursor: pointer; overflow: hidden; transition: border-color .2s, background .2s; font-family: inherit; }
    .bk-type:hover { border-color: #0d1883; background: #f0f3ff; }
    .bk-type.on { border-color: #0d1883; background: #eef1ff; }
    .bk-type.on::before { content: ''; position: absolute; inset: 0 auto 0 0; width: 3px; background: #0d1883; }
    .bk-thumb { width: 54px; height: 35px; flex-shrink: 0; background: #e8ecff; border-radius: 6px; overflow: hidden; }
    .bk-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .bk-type b { display: block; font-size: 12.5px; font-weight: 600; color: #1a1a1a; }
    .bk-type small { font-size: 10.5px; color: #888; }

    .bk-cats { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; }
    .bk-cat { position: relative; padding: 12px; border: 2px solid #e0e4f0; border-radius: 14px; background: #fff; text-align: left; cursor: pointer; font-family: inherit; transition: border-color .2s, background .2s, transform .2s, box-shadow .2s; }
    .bk-cat:hover { border-color: #0d1883; background: #f5f7ff; transform: translateY(-2px); box-shadow: 0 6px 18px rgba(13,24,131,.1); }
    .bk-cat.on { border-color: #0d1883; background: #eef1ff; box-shadow: 0 6px 18px rgba(13,24,131,.15); }
    .bk-cat-img { height: 72px; background: #e8ecff; border-radius: 9px; margin-bottom: 9px; overflow: hidden; }
    .bk-cat-img img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .bk-cat b { display: block; font-size: 13.5px; font-weight: 600; color: #1a1a1a; margin-bottom: 2px; }
    .bk-cat em { display: block; font-style: normal; font-size: 12px; font-weight: 700; color: #0d1883; }
    .bk-cat small { display: block; font-size: 10.5px; color: #888; margin-top: 2px; }

    .bk-spec { display: flex; flex-wrap: wrap; gap: 8px 22px; margin-top: 14px; padding: 12px 14px; border-radius: 10px; background: #f7f8ff; border: 1px solid #e0e4f8; font-size: 12.5px; color: #444; }
    .bk-spec span { color: #888; }

    .bk-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .bk-grid .full { grid-column: 1 / -1; }
    .bk-field label { display: block; margin-bottom: 4px; font-size: 10.5px; font-weight: 600; color: #555; letter-spacing: .04em; text-transform: uppercase; }
    .bk-field input, .bk-field select { width: 100%; padding: 10px 12px; border: 1.5px solid #dde0ee; border-radius: 10px; background: #fafbff; color: #1a1a1a; font: 400 13.5px 'DM Sans', sans-serif; outline: none; transition: border-color .2s, box-shadow .2s; }
    .bk-field input:focus, .bk-field select:focus { border-color: #0d1883; box-shadow: 0 0 0 3px rgba(13,24,131,.07); }
    .bk-err { margin-top: 4px; font-size: 11.5px; color: #c0392b; }

    .bk-chips { display: flex; flex-wrap: wrap; gap: 7px; align-items: center; }
    .bk-chip { padding: 7px 15px; border: 1.5px solid #dde0ee; border-radius: 20px; background: #fff; color: #555; font: 600 12.5px 'DM Sans', sans-serif; cursor: pointer; transition: all .2s; }
    .bk-chip:hover { border-color: #0d1883; color: #0d1883; }
    .bk-chip.on { background: #0d1883; border-color: #0d1883; color: #fff; }
    .bk-chips input { width: 90px; padding: 7px 10px; border: 1.5px solid #dde0ee; border-radius: 10px; font: 400 13px 'DM Sans', sans-serif; }

    .bk-route { margin-top: 12px; padding: 9px 13px; border-radius: 9px; background: #eef1ff; border: 1px solid #c5cef8; font-size: 12.5px; font-weight: 600; color: #0d1883; }
    .bk-route.bad { background: #fff0f0; border-color: #ffc5c5; color: #c0392b; }
    .bk-btn-line { margin-top: 12px; padding: 9px 18px; border: 1.5px solid #0d1883; border-radius: 10px; background: #fff; color: #0d1883; font: 600 13px 'DM Sans', sans-serif; cursor: pointer; }
    .bk-btn-line:hover { background: #f0f3ff; }

    .bk-price { margin-top: 14px; padding: 13px 15px; background: #f7f8ff; border: 1.5px solid #c5cef8; border-radius: 10px; }
    .bk-price h4 { margin: 0 0 9px; font-size: 9.5px; font-weight: 700; color: #999; text-transform: uppercase; letter-spacing: .08em; }
    .bk-price div { display: flex; justify-content: space-between; padding: 4px 0; font-size: 12.5px; border-bottom: 1px solid #e8eaf5; color: #666; }
    .bk-price div b { color: #1a1a1a; font-weight: 600; }
    .bk-price .total { padding-top: 8px; margin-top: 2px; border-top: 1.5px solid #c5cef8; border-bottom: 0; font-size: 14.5px; font-weight: 700; color: #555; }
    .bk-price .total b { color: #0d1883; font-weight: 700; }

    .bk-pays { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px; }
    .bk-pay { padding: 12px 14px; border: 2px solid #e0e4f0; border-radius: 12px; background: #fff; text-align: left; cursor: pointer; font-family: inherit; transition: border-color .2s, background .2s; }
    .bk-pay:hover { border-color: #0d1883; }
    .bk-pay.on { border-color: #0d1883; background: #eef1ff; }
    .bk-pay b { display: block; font-size: 13px; font-weight: 600; color: #1a1a1a; }
    .bk-pay small { font-size: 11.5px; color: #777; }
    .bk-submit { width: 100%; padding: 13px; border: 0; border-radius: 11px; background: #0d1883; color: #fff; font: 700 14.5px 'DM Sans', sans-serif; cursor: pointer; box-shadow: 0 4px 14px rgba(13,24,131,.25); transition: background .2s; }
    .bk-submit:hover { background: #0a1369; }
    .bk-submit[disabled] { opacity: .6; cursor: wait; }

    @media (max-width: 768px) {
        .bk-card { grid-template-columns: 1fr; }
        .bk-side { border-right: 0; border-bottom: 1px solid #e8eaf5; }
        .bk-grid, .bk-pays { grid-template-columns: 1fr; }
        .bk-tabs { width: 100%; }
        .bk-tab { flex: 1; padding: 10px 8px; }
    }
</style>

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
