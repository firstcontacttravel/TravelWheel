<x-filament-panels::page>
@include('filament.pages.partials.booking-styles')

<div class="bk">
    <div class="bk-one">
        <section class="bk-step tint">
            <div class="bk-label">1 · Where</div>
            <div class="bk-grid">
                <div class="bk-field full">
                    <label for="bk-location">Location</label>
                    <select id="bk-location" wire:model.live="location">
                        <option value="">Choose a location</option>
                        @foreach ($locations as $name)
                            <option value="{{ $name }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="bk-field">
                    <label>Airport</label>
                    <div class="bk-chips">
                        <button type="button" class="bk-chip {{ $airport === 'International Airport' ? 'on' : '' }}" wire:click="setAirport('International Airport')">International</button>
                        <button type="button" class="bk-chip {{ $airport === 'Local Airport' ? 'on' : '' }}" wire:click="setAirport('Local Airport')">Local</button>
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
        </section>

        <section class="bk-step">
            <div class="bk-label">2 · Plan</div>
            @if ($plans)
                <div class="bk-options">
                    @foreach ($plans as $key => $option)
                        <button type="button" class="bk-pay {{ $plan === (string) $key ? 'on' : '' }}" wire:click="selectPlan('{{ $key }}')">
                            <b>{{ $option['label'] }}</b>
                            <small>&#8358;{{ number_format($option['price']) }} per passenger</small>
                        </button>
                    @endforeach
                </div>
            @elseif ($chosen)
                <div class="bk-empty">No protocol service is offered here. Choose another location, airport or service.</div>
            @else
                <div class="bk-empty">Choose a location, airport and service first</div>
            @endif
            @error('plan') <div class="bk-err">{{ $message }}</div> @enderror
        </section>

        @if ($quote)
            <section class="bk-step">
                <div class="bk-label">3 · Flight</div>
                <div class="bk-grid">
                    <div class="bk-field">
                        <label for="bk-date">Travel date</label>
                        <input id="bk-date" type="date" min="{{ now()->toDateString() }}" wire:model="travelDate">
                        @error('travelDate') <div class="bk-err">{{ $message }}</div> @enderror
                    </div>
                    <div class="bk-field">
                        <label for="bk-time">{{ $service }} time</label>
                        <input id="bk-time" type="time" wire:model="time">
                        @error('time') <div class="bk-err">{{ $message }}</div> @enderror
                    </div>
                    <div class="bk-field">
                        <label for="bk-airline">Airline</label>
                        <select id="bk-airline" wire:model.live="airline">
                            <option value="">Choose an airline</option>
                            @foreach ($airlines as $name)
                                <option value="{{ $name }}">{{ $name }}</option>
                            @endforeach
                            <option value="OTHERS">Other airline</option>
                        </select>
                        @error('airline') <div class="bk-err">{{ $message }}</div> @enderror
                    </div>
                    @if ($airline === 'OTHERS')
                        <div class="bk-field">
                            <label for="bk-other-airline">Airline name</label>
                            <input id="bk-other-airline" type="text" wire:model="otherAirline">
                            @error('otherAirline') <div class="bk-err">{{ $message }}</div> @enderror
                        </div>
                    @endif
                </div>
            </section>

            <section class="bk-step">
                <div class="bk-label">4 · Passengers</div>
                <div class="bk-guest">
                    <div><b>Passengers</b><small>&#8358;{{ number_format($quote['unit']) }} each</small></div>
                    <div class="bk-count">
                        <button type="button" wire:click="changePassengers(-1)" aria-label="One fewer passenger">&minus;</button>
                        <span id="bk-pax-count">{{ count($passengers) }}</span>
                        <button type="button" wire:click="changePassengers(1)" aria-label="One more passenger">+</button>
                    </div>
                </div>
                @foreach ($passengers as $i => $passenger)
                    <div class="bk-grid" style="margin-top:14px; padding-top:12px; border-top:1px dashed #e0e4f0;" wire:key="pax-{{ $i }}">
                        <div class="bk-field full" style="margin-bottom:-4px;"><label style="color:#0d1883;">Passenger {{ $i + 1 }}</label></div>
                        @foreach (['first' => 'First name', 'last' => 'Last name', 'pnr' => 'PNR / reservation code', 'ticket' => 'e-Ticket number'] as $field => $label)
                            <div class="bk-field">
                                <label for="bk-pax-{{ $i }}-{{ $field }}">{{ $label }}</label>
                                <input id="bk-pax-{{ $i }}-{{ $field }}" type="text" wire:model="passengers.{{ $i }}.{{ $field }}">
                                @error("passengers.$i.$field") <div class="bk-err">{{ $message }}</div> @enderror
                            </div>
                        @endforeach
                        <div class="bk-field">
                            <label for="bk-pax-{{ $i }}-bags">Number of bags</label>
                            <input id="bk-pax-{{ $i }}-bags" type="number" min="0" wire:model="passengers.{{ $i }}.bags">
                            @error("passengers.$i.bags") <div class="bk-err">{{ $message }}</div> @enderror
                        </div>
                    </div>
                @endforeach
            </section>

            <section class="bk-step">
                <div class="bk-label">5 · Optional request</div>
                <div class="bk-field">
                    <label for="bk-request">Request</label>
                    <select id="bk-request" wire:model.live="optionalRequest">
                        <option value="">No optional request</option>
                        @foreach ($requests as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                    @error('optionalRequest') <div class="bk-err">{{ $message }}</div> @enderror
                </div>
                @if ($vehicleDirection)
                    <div class="bk-options" style="margin-top:12px;">
                        @foreach ($vehicles as $key => $option)
                            <button type="button" class="bk-pay {{ $vehicle === $key ? 'on' : '' }}" wire:click="$set('vehicle', '{{ $key }}')">
                                <b>{{ $option['name'] }}</b>
                                <small>Up to {{ $option['seats'] }} seats · {{ \App\Support\ProtocolVehicles::priceLabel($option['price']) }}</small>
                            </button>
                        @endforeach
                    </div>
                    @error('vehicle') <div class="bk-err">{{ $message }}</div> @enderror
                    <div class="bk-field" style="margin-top:12px;">
                        <label for="bk-vehicle-address">{{ $vehicleDirection }} location</label>
                        <input id="bk-vehicle-address" type="text" wire:model="vehicleAddress">
                        @error('vehicleAddress') <div class="bk-err">{{ $message }}</div> @enderror
                    </div>
                    <div class="bk-note">The vehicle is settled separately and is not included in the protocol amount.</div>
                @endif
            </section>

            <section class="bk-step">
                <div class="bk-label">6 · Contact &amp; ID</div>
                <div class="bk-grid">
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
                        <label for="bk-kin-name">Next of kin name</label>
                        <input id="bk-kin-name" type="text" wire:model="nextOfKinName">
                        @error('nextOfKinName') <div class="bk-err">{{ $message }}</div> @enderror
                    </div>
                    <div class="bk-field">
                        <label for="bk-kin-phone">Next of kin phone</label>
                        <input id="bk-kin-phone" type="tel" wire:model="nextOfKinPhone">
                        @error('nextOfKinPhone') <div class="bk-err">{{ $message }}</div> @enderror
                    </div>
                    <div class="bk-field full">
                        <label for="bk-means-id">Lead passenger's means of ID (PDF, JPG or PNG)</label>
                        <input id="bk-means-id" type="file" accept=".pdf,.jpg,.jpeg,.png" wire:model="meansId">
                        <div wire:loading wire:target="meansId" class="bk-note">Uploading…</div>
                        @error('meansId') <div class="bk-err">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="bk-price">
                    <h4>Price breakdown</h4>
                    <div><span>{{ $plans[$plan]['label'] }} × {{ count($passengers) }}</span><b>&#8358;{{ number_format($quote['subtotal']) }}</b></div>
                    <div><span>VAT ({{ $vatPercent }}%)</span><b>&#8358;{{ number_format($quote['vat']) }}</b></div>
                    <div class="total"><span>Total</span><b>&#8358;{{ number_format($quote['total']) }}</b></div>
                </div>
            </section>

            <section class="bk-step">
                <div class="bk-label">7 · Payment</div>
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
                <button type="button" class="bk-submit" wire:click="create" wire:loading.attr="disabled" wire:target="create, meansId">
                    <span wire:loading.remove wire:target="create">Create booking · &#8358;{{ number_format($quote['total']) }}</span>
                    <span wire:loading wire:target="create">Creating booking…</span>
                </button>
            </section>
        @endif
    </div>
</div>
</x-filament-panels::page>
