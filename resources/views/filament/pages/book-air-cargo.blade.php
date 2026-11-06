<x-filament-panels::page>
@include('filament.pages.partials.booking-styles')

<div class="bk">
    <div class="bk-one">
        @foreach (['sender' => ['1 · Ship from', 'Sender'], 'receiver' => ['2 · Ship to', 'Receiver']] as $side => [$heading, $who])
            <section class="bk-step {{ $side === 'sender' ? 'tint' : '' }}">
                <div class="bk-label">{{ $heading }}</div>
                <div class="bk-grid">
                    <div class="bk-field">
                        <label for="bk-{{ $side }}-name">{{ $who }} name</label>
                        <input id="bk-{{ $side }}-name" type="text" wire:model="{{ $side }}Name">
                        @error($side.'Name') <div class="bk-err">{{ $message }}</div> @enderror
                    </div>
                    <div class="bk-field">
                        <label for="bk-{{ $side }}-phone">Phone</label>
                        <input id="bk-{{ $side }}-phone" type="tel" wire:model="{{ $side }}Phone">
                        @error($side.'Phone') <div class="bk-err">{{ $message }}</div> @enderror
                    </div>
                    <div class="bk-field">
                        <label for="bk-{{ $side }}-email">Email</label>
                        <input id="bk-{{ $side }}-email" type="email" wire:model="{{ $side }}Email">
                        @error($side.'Email') <div class="bk-err">{{ $message }}</div> @enderror
                    </div>
                    <div class="bk-field">
                        <label for="bk-{{ $side }}-country">{{ $side === 'receiver' ? 'Destination country' : 'Country' }}</label>
                        <select id="bk-{{ $side }}-country" wire:model.live="{{ $side }}Country">
                            <option value="">Choose a country</option>
                            @foreach ($countries as $code => $country)
                                <option value="{{ $code }}">{{ $country['name'] }}</option>
                            @endforeach
                        </select>
                        @error($side.'Country') <div class="bk-err">{{ $message }}</div> @enderror
                    </div>
                    <div class="bk-field">
                        <label for="bk-{{ $side }}-address">Address</label>
                        <input id="bk-{{ $side }}-address" type="text" wire:model="{{ $side }}Address">
                        @error($side.'Address') <div class="bk-err">{{ $message }}</div> @enderror
                    </div>
                    <div class="bk-field">
                        <label for="bk-{{ $side }}-postal">Postal code</label>
                        <input id="bk-{{ $side }}-postal" type="text" wire:model="{{ $side }}PostalCode">
                        @error($side.'PostalCode') <div class="bk-err">{{ $message }}</div> @enderror
                    </div>
                </div>
            </section>
        @endforeach

        <section class="bk-step">
            <div class="bk-label">3 · What's being sent</div>
            <div class="bk-options">
                <button type="button" class="bk-pay {{ $shipmentType === 'Document' ? 'on' : '' }}" wire:click="setType('Document')">
                    <b>Document</b>
                    <small>Passports, certificates, company papers</small>
                </button>
                <button type="button" class="bk-pay {{ $shipmentType === 'Package' ? 'on' : '' }}" wire:click="setType('Package')">
                    <b>Package</b>
                    <small>Parcels and boxes</small>
                </button>
            </div>
            @error('shipmentType') <div class="bk-err">{{ $message }}</div> @enderror

            @if ($shipmentType)
                <div class="bk-field" style="margin-top:14px;">
                    <label>Weight</label>
                    <div class="bk-chips">
                        @foreach ($weights as $option)
                            <button type="button" class="bk-chip {{ $weight === $option ? 'on' : '' }}" wire:click="setWeight('{{ $option }}')">{{ $option }}kg</button>
                        @endforeach
                    </div>
                    @error('weight') <div class="bk-err">{{ $message }}</div> @enderror
                </div>
                <div class="bk-grid" style="margin-top:12px;">
                    <div class="bk-field full">
                        <label for="bk-description">{{ $shipmentType === 'Document' ? 'Describe the document' : 'Package description (optional)' }}</label>
                        <input id="bk-description" type="text" wire:model="description" placeholder="{{ $shipmentType === 'Document' ? 'Passport, company document, certificate, etc.' : '' }}">
                        @error('description') <div class="bk-err">{{ $message }}</div> @enderror
                    </div>
                    @if ($shipmentType === 'Package')
                        @foreach (['length' => 'Length', 'width' => 'Width', 'height' => 'Height'] as $field => $label)
                            <div class="bk-field">
                                <label for="bk-{{ $field }}">{{ $label }} (in, optional)</label>
                                <input id="bk-{{ $field }}" type="number" min="0" wire:model="{{ $field }}">
                                @error($field) <div class="bk-err">{{ $message }}</div> @enderror
                            </div>
                        @endforeach
                    @endif
                    <div class="bk-field full">
                        <label for="bk-preview">Picture of the {{ strtolower($shipmentType) }} (optional)</label>
                        <input id="bk-preview" type="file" accept=".jpg,.jpeg,.png,.pdf" wire:model="preview">
                        <div wire:loading wire:target="preview" class="bk-note">Uploading…</div>
                        @error('preview') <div class="bk-err">{{ $message }}</div> @enderror
                    </div>
                </div>
            @endif
        </section>

        <section class="bk-step">
            <div class="bk-label">4 · Pick-up or drop-off</div>
            <div class="bk-options">
                <button type="button" class="bk-pay {{ $pickup ? '' : 'on' }}" wire:click="setPickup(false)">
                    <b>Customer drops it off</b>
                    <small>{{ $dropOffAddress }}, 9:00AM to 5:00PM</small>
                </button>
                <button type="button" class="bk-pay {{ $pickup ? 'on' : '' }}" wire:click="setPickup(true)">
                    <b>We pick it up</b>
                    <small>Lagos only, 9:00AM to 4:00PM, priced by area</small>
                </button>
            </div>
            <div class="bk-grid" style="margin-top:12px;">
                @if ($pickup)
                    <div class="bk-field">
                        <label for="bk-pickup-area">Nearest bus stop or area</label>
                        <select id="bk-pickup-area" wire:model.live="pickupArea">
                            <option value="">Choose an area</option>
                            @foreach ($pickupZones as $zone => $info)
                                <optgroup label="{{ $zone }} · &#8358;{{ number_format($info['price']) }}">
                                    @foreach ($info['areas'] as $area)
                                        <option value="{{ $area }}">{{ $area }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        @error('pickupArea') <div class="bk-err">{{ $message }}</div> @enderror
                    </div>
                    <div class="bk-field">
                        <label for="bk-pickup-date">Pick-up date</label>
                        <input id="bk-pickup-date" type="date" min="{{ now()->toDateString() }}" wire:model="pickupDate">
                        @error('pickupDate') <div class="bk-err">{{ $message }}</div> @enderror
                    </div>
                    <div class="bk-field full">
                        <label for="bk-pickup-address">Pick-up address</label>
                        <input id="bk-pickup-address" type="text" wire:model="pickupAddress">
                        @error('pickupAddress') <div class="bk-err">{{ $message }}</div> @enderror
                    </div>
                @else
                    <div class="bk-field">
                        <label for="bk-dropoff-date">Drop-off date</label>
                        <input id="bk-dropoff-date" type="date" min="{{ now()->toDateString() }}" wire:model="dropoffDate">
                        @error('dropoffDate') <div class="bk-err">{{ $message }}</div> @enderror
                    </div>
                @endif
            </div>

            @if ($quote)
                <div class="bk-price">
                    <h4>Price breakdown</h4>
                    <div><span>Shipping ({{ $shipmentType }}, {{ $weight }}kg to {{ $countries[$receiverCountry]['name'] }})</span><b>&#8358;{{ number_format($quote['shipping']) }}</b></div>
                    @if ($pickup)
                        <div><span>Pick-up</span><b>&#8358;{{ number_format($quote['pickup']) }}</b></div>
                    @endif
                    <div class="total"><span>Total</span><b>&#8358;{{ number_format($quote['total']) }}</b></div>
                </div>
                <div class="bk-note">The price is an estimate until TravelWheel re-weighs the shipment.</div>
            @elseif ($shipmentType && $weight && $receiverCountry)
                <div class="bk-route bad" role="alert">There is no price for this destination and weight.</div>
            @endif
        </section>

        @if ($quote)
            <section class="bk-step">
                <div class="bk-label">5 · Payment</div>
                <div class="bk-pays">
                    <button type="button" class="bk-pay {{ $sendLink ? 'on' : '' }}" wire:click="$set('sendLink', true)">
                        <b>Email a payment link</b>
                        <small>The sender pays online by SeerBit</small>
                    </button>
                    <button type="button" class="bk-pay {{ $sendLink ? '' : 'on' }}" wire:click="$set('sendLink', false)">
                        <b>Don't send a link</b>
                        <small>Paying by transfer, POS or cash: Finance records it</small>
                    </button>
                </div>
                <button type="button" class="bk-submit" wire:click="create" wire:loading.attr="disabled" wire:target="create, preview">
                    <span wire:loading.remove wire:target="create">Create shipment · &#8358;{{ number_format($quote['total']) }}</span>
                    <span wire:loading wire:target="create">Creating shipment…</span>
                </button>
            </section>
        @endif
    </div>
</div>
</x-filament-panels::page>
