<x-filament-panels::page>
@include('filament.pages.partials.booking-styles')

<div class="bk">
    <div class="bk-one">
        <section class="bk-step tint">
            <div class="bk-label">1 · Trip</div>
            <div class="bk-grid">
                <div class="bk-field">
                    <label for="bk-nationality">Passport nationality</label>
                    <select id="bk-nationality" wire:model.live="nationality">
                        <option value="">Choose a country</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}">{{ $country->name }}</option>
                        @endforeach
                    </select>
                    @error('nationality') <div class="bk-err">{{ $message }}</div> @enderror
                </div>
                <div class="bk-field">
                    <label for="bk-destination">Destination</label>
                    <select id="bk-destination" wire:model.live="destination" @disabled($destinations->isEmpty())>
                        <option value="">{{ filled($nationality) ? ($destinations->isEmpty() ? 'No visas for this passport' : 'Choose a destination') : 'Choose the nationality first' }}</option>
                        @foreach ($destinations as $option)
                            <option value="{{ $option['ref'] }}">{{ $option['name'] }}</option>
                        @endforeach
                    </select>
                    @error('destination') <div class="bk-err">{{ $message }}</div> @enderror
                </div>
                <div class="bk-field">
                    <label for="bk-residence">Country of residence (optional)</label>
                    <select id="bk-residence" wire:model.live="residence">
                        <option value="">Same as nationality</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}">{{ $country->name }}</option>
                        @endforeach
                    </select>
                    @error('residence') <div class="bk-err">{{ $message }}</div> @enderror
                </div>
                <div class="bk-field">
                    <label for="bk-email">Customer's email</label>
                    <input id="bk-email" type="email" wire:model.blur="email">
                    @error('email') <div class="bk-err">{{ $message }}</div> @enderror
                </div>
                <div class="bk-field">
                    <label for="bk-arrival">Arrival</label>
                    <input id="bk-arrival" type="date" min="{{ now()->toDateString() }}" wire:model.live="arrivalDate">
                    @error('arrivalDate') <div class="bk-err">{{ $message }}</div> @enderror
                </div>
                <div class="bk-field">
                    <label for="bk-departure">Departure</label>
                    <input id="bk-departure" type="date" min="{{ now()->toDateString() }}" wire:model.live="departureDate">
                    @error('departureDate') <div class="bk-err">{{ $message }}</div> @enderror
                </div>
            </div>

            <div style="margin-top:12px;">
                @foreach (['adults' => ['Adults', '12 years and over'], 'children' => ['Children', '2 to 11 years'], 'infants' => ['Infants', 'Under 2 years, no more than adults']] as $type => [$label, $age])
                    <div class="bk-guest" wire:key="traveller-{{ $type }}">
                        <div><b>{{ $label }}</b><small>{{ $age }}</small></div>
                        <div class="bk-count">
                            <button type="button" wire:click="changeCount('{{ $type }}', -1)" aria-label="One fewer {{ $label }}">&minus;</button>
                            <span id="bk-count-{{ $type }}">{{ $this->{$type} }}</span>
                            <button type="button" wire:click="changeCount('{{ $type }}', 1)" aria-label="One more {{ $label }}">+</button>
                        </div>
                    </div>
                @endforeach
                @error('infants') <div class="bk-err">{{ $message }}</div> @enderror
            </div>

            <button type="button" class="bk-btn-line" wire:click="search" wire:loading.attr="disabled" wire:target="search">
                <span wire:loading.remove wire:target="search">{{ $searched ? 'Search again' : 'Find visas' }}</span>
                <span wire:loading wire:target="search">Searching…</span>
            </button>
        </section>

        @if ($searched)
            <section class="bk-step">
                <div class="bk-label">2 · How the application is finished</div>
                <div class="bk-pays">
                    <button type="button" class="bk-pay {{ $sendLink ? '' : 'on' }}" wire:click="$set('sendLink', false)">
                        <b>Fill it in now</b>
                        <small>Opens the application for you to enter the travellers, answers and documents</small>
                    </button>
                    <button type="button" class="bk-pay {{ $sendLink ? 'on' : '' }}" wire:click="$set('sendLink', true)">
                        <b>Email the customer a link</b>
                        <small>They finish the application and pay online</small>
                    </button>
                </div>
                <div class="bk-note">
                    Either way the application is listed under New Booking. The customer pays online at its last step,
                    or Finance records a transfer, POS or cash payment there once it is finished.
                </div>
            </section>

            <section class="bk-step">
                <div class="bk-label">3 · Choose a visa</div>
                @forelse ($results as $result)
                    <div class="bk-price" wire:key="visa-{{ $result['id'] }}" style="margin-top:0; margin-bottom:14px;">
                        <h4>{{ $result['name'] }}</h4>
                        <div class="bk-spec" style="margin-top:0; margin-bottom:10px;">
                            @if ($result['entry_type'])
                                <div><span>Entry:</span> {{ ucfirst(str_replace('_', ' ', $result['entry_type'])) }}</div>
                            @endif
                            @if ($result['validity_days'])
                                <div><span>Valid for:</span> {{ $result['validity_days'] }} days</div>
                            @endif
                            @if ($result['maximum_stay_days'])
                                <div><span>Maximum stay:</span> {{ $result['maximum_stay_days'] }} days</div>
                            @endif
                            @if ($option = $result['estimate']['processing_option'])
                                <div><span>Processing:</span> {{ $option['name'] }}{{ $option['minimum_business_days'] ? ', '.$option['minimum_business_days'].'–'.$option['maximum_business_days'].' business days' : '' }}</div>
                            @endif
                            @if ($result['eligibility']['status'] === 'conditionally_eligible')
                                @foreach ($result['eligibility']['messages'] as $note)
                                    <div><span>Note:</span> {{ $note }}</div>
                                @endforeach
                            @endif
                        </div>
                        @foreach ($result['estimate']['lines'] as $line)
                            <div>
                                <span>{{ $line['type_label'] }}{{ $line['pay_online'] ? '' : ' (paid separately)' }}</span>
                                <b>{{ $line['currency'] }} {{ number_format($line['amount'], 2) }}</b>
                            </div>
                        @endforeach
                        <div class="total">
                            <span>Pay online</span>
                            <b>{{ $result['estimate']['checkout']['total'] !== null ? '₦'.number_format($result['estimate']['checkout']['total'], 2) : 'Worked out at payment' }}</b>
                        </div>
                        <button type="button" class="bk-submit" style="margin-top:12px;" wire:click="start({{ $result['id'] }})" wire:loading.attr="disabled" wire:target="start">
                            <span wire:loading.remove wire:target="start">{{ $sendLink ? 'Start and email the customer' : 'Start application' }}</span>
                            <span wire:loading wire:target="start">Starting…</span>
                        </button>
                    </div>
                @empty
                    <div class="bk-empty">No visa is available for this passport and trip.</div>
                @endforelse
            </section>
        @endif
    </div>
</div>
</x-filament-panels::page>
