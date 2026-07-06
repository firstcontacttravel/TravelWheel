<div>
    <link rel="stylesheet" href="{{ asset('css/visa-discovery.css') }}">

    <x-ui.page-shell eyebrow="Visa assistance" title="Find the right visa for your trip" copy="Check available TravelWheel visa products, requirements, processing estimates, and fees before you apply.">
        <x-ui.card title="Tell us about your trip" description="Eligibility is based on passport nationality, destination, residence rules, and travel dates.">
            <form wire:submit="search" class="vd-search" novalidate>
                @if($errors->any())
                    <x-ui.alert variant="error" role="alert">
                        <div><strong>Check the highlighted information.</strong><ul class="vd-error-list">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                    </x-ui.alert>
                @endif

                <div class="vd-search__grid">
                    <x-ui.field label="Passport nationality" for="visa-nationality" required :error="$errors->first('nationalityId')">
                        <x-ui.select id="visa-nationality" wire:model="nationalityId" :invalid="$errors->has('nationalityId')">
                            <option value="">Choose nationality</option>
                            @foreach($countries as $country)<option value="{{ $country->id }}">{{ $country->name }} ({{ $country->alpha2 }})</option>@endforeach
                        </x-ui.select>
                    </x-ui.field>

                    <x-ui.field label="Country of residence" for="visa-residence" hint="Optional unless a product has a residence rule." :error="$errors->first('residenceCountryId')">
                        <x-ui.select id="visa-residence" wire:model="residenceCountryId" :invalid="$errors->has('residenceCountryId')">
                            <option value="">Not specified</option>
                            @foreach($countries as $country)<option value="{{ $country->id }}">{{ $country->name }}</option>@endforeach
                        </x-ui.select>
                    </x-ui.field>

                    <x-ui.field label="Destination" for="visa-destination" required :error="$errors->first('destinationId')">
                        <x-ui.select id="visa-destination" wire:model="destinationId" :invalid="$errors->has('destinationId')">
                            <option value="">Where are you going?</option>
                            @foreach($countries as $country)<option value="{{ $country->id }}">{{ $country->name }}</option>@endforeach
                        </x-ui.select>
                    </x-ui.field>

                    <x-ui.field label="Arrival date" for="visa-arrival" required :error="$errors->first('arrivalDate')">
                        <x-ui.date id="visa-arrival" wire:model="arrivalDate" min="{{ now()->toDateString() }}" :invalid="$errors->has('arrivalDate')" />
                    </x-ui.field>

                    <x-ui.field label="Departure date" for="visa-departure" required :error="$errors->first('departureDate')">
                        <x-ui.date id="visa-departure" wire:model="departureDate" min="{{ $arrivalDate ?: now()->toDateString() }}" :invalid="$errors->has('departureDate')" />
                    </x-ui.field>
                </div>

                <fieldset class="vd-travelers">
                    <legend>Travelers</legend>
                    <div class="vd-travelers__grid">
                        @foreach([['adults','Adults','12+ years',1],['children','Children','2–11 years',0],['infants','Infants','Under 2 years',0]] as [$field,$label,$hint,$min])
                            <label class="vd-counter" for="visa-{{ $field }}">
                                <span><strong>{{ $label }}</strong><small>{{ $hint }}</small></span>
                                <input id="visa-{{ $field }}" type="number" min="{{ $min }}" max="9" wire:model="{{ $field }}" class="tw-ui-control">
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div class="vd-search__action">
                    <x-ui.button type="submit" size="lg" wire:loading.attr="disabled" wire:target="search">
                        <span wire:loading.remove wire:target="search">Check visa options</span>
                        <span wire:loading wire:target="search">Checking eligibility…</span>
                    </x-ui.button>
                    <p>Final visa issuance is decided by the relevant immigration authority.</p>
                </div>
            </form>
        </x-ui.card>

        @if($hasSearched)
            <section class="vd-results" aria-live="polite">
                <div class="vd-results__heading">
                    <div><p class="tw-ui-page__eyebrow">Search results</p><h2>{{ count($results) }} visa {{ count($results) === 1 ? 'option' : 'options' }}</h2></div>
                    <x-ui.badge variant="info">Estimates only</x-ui.badge>
                </div>

                @forelse($results as $result)
                    @php
                        $status = $result['eligibility']['status'];
                        $badgeVariant = $status === 'eligible' ? 'success' : ($status === 'conditionally_eligible' ? 'warning' : 'error');
                        $processing = $result['estimate']['processing_option'];
                    @endphp
                    <article class="vd-product {{ $status === 'ineligible' ? 'vd-product--muted' : '' }}" wire:key="visa-product-{{ $result['id'] }}">
                        <div class="vd-product__main">
                            <div class="vd-product__topline">
                                <div><span class="vd-product__family">{{ $result['family'] === 'voa' ? 'Nigerian Business Visa' : 'Standard visa' }}</span><h3>{{ $result['name'] }}</h3></div>
                                <x-ui.badge :variant="$badgeVariant">{{ str($status)->replace('_', ' ')->headline() }}</x-ui.badge>
                            </div>
                            @if($result['summary'])<p class="vd-product__summary">{{ $result['summary'] }}</p>@endif

                            <div class="vd-product__facts">
                                <div><span>Entry</span><strong>{{ str($result['entry_type'])->headline() }}</strong></div>
                                <div><span>Processing</span><strong>{{ $processing ? $processing['minimum_business_days'].'–'.$processing['maximum_business_days'].' business days' : 'Ask TravelWheel' }}</strong></div>
                                <div><span>Validity</span><strong>{{ $result['validity_days'] ? $result['validity_days'].' days' : 'Product-specific' }}</strong></div>
                                <div><span>Maximum stay</span><strong>{{ $result['maximum_stay_days'] ? $result['maximum_stay_days'].' days' : 'Confirm before applying' }}</strong></div>
                            </div>

                            @foreach($result['eligibility']['messages'] as $message)
                                <x-ui.alert :variant="$badgeVariant === 'error' ? 'error' : 'warning'">{{ $message }}</x-ui.alert>
                            @endforeach

                            <details class="vd-requirements">
                                <summary>Preview requirements ({{ count($result['requirements']) }})</summary>
                                <ul>@forelse($result['requirements'] as $requirement)<li><span>{{ $requirement['name'] }}</span><x-ui.badge :variant="$requirement['state'] === 'required' ? 'neutral' : 'info'">{{ str($requirement['state'])->headline() }}</x-ui.badge></li>@empty<li>No uploaded documents are configured.</li>@endforelse</ul>
                            </details>

                            @if($result['processing_disclaimer'])<p class="vd-disclaimer">{{ $result['processing_disclaimer'] }}</p>@endif
                        </div>

                        <aside class="vd-product__price">
                            <p class="vd-product__price-title">Estimated fees</p>
                            @foreach($result['estimate']['lines'] as $line)
                                <div class="vd-fee-line"><span>{{ $line['name'] }}@if($line['quantity'] > 1)<small> × {{ $line['quantity'] }}</small>@endif</span><strong>{{ $line['currency'] }} {{ number_format($line['amount'], 2) }}</strong></div>
                            @endforeach
                            @if(empty($result['estimate']['lines']))<p class="vd-disclaimer">Pricing will be confirmed by TravelWheel.</p>@endif

                            @if(!empty($result['estimate']['pay_now_totals']))
                                <div class="vd-total"><span>Estimated pay now</span>@foreach($result['estimate']['pay_now_totals'] as $currency => $amount)<strong>{{ $currency }} {{ number_format($amount, 2) }}</strong>@endforeach</div>
                            @endif
                            @if(!empty($result['estimate']['pay_separately_totals']))
                                <div class="vd-direct"><span>Pay separately to authority</span>@foreach($result['estimate']['pay_separately_totals'] as $currency => $amount)<strong>{{ $currency }} {{ number_format($amount, 2) }}</strong>@endforeach</div>
                            @endif
                            <p class="vd-disclaimer">The final NGN checkout quote is calculated before payment.</p>
                        </aside>
                    </article>
                @empty
                    <x-ui.state title="No visa products are available" copy="TravelWheel has no published product matching this destination yet. Try another destination or contact support." icon="?" />
                @endforelse
            </section>
        @endif

        <x-slot:summary>
            <x-ui.card title="What happens next?">
                <x-ui.timeline :items="[
                    ['title' => 'Check eligibility', 'copy' => 'Compare published products and indicative fees.', 'state' => 'current'],
                    ['title' => 'Complete application', 'copy' => 'Add traveler details and required documents.', 'state' => 'upcoming'],
                    ['title' => 'Review and pay', 'copy' => 'Accept a server-generated NGN quote.', 'state' => 'upcoming'],
                    ['title' => 'Track progress', 'copy' => 'Receive updates and respond to requests.', 'state' => 'upcoming'],
                ]" />
            </x-ui.card>
        </x-slot:summary>
    </x-ui.page-shell>
</div>
