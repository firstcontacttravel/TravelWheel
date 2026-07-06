<div>
    <link rel="stylesheet" href="{{ asset('css/visa-flow.css') }}">
    <section class="vw-hero" x-data="visaWidget()" @click.outside="paxOpen = false">
        <div class="vw-hero__inner">
            <h1>All Your Travel Needs In One Place</h1>
            <p class="vw-hero__subtitle">Simplifying Access To Travel.</p>

            <nav class="vw-product-tabs" aria-label="TravelWheel services">
                <a href="{{ route('air.flight') }}"><img src="{{ asset('assets/Flight 70.png') }}" alt=""><span>Flights</span></a>
                <!-- <a href="{{ route('air.hotel') }}"><img src="{{ asset('assets/Hotel 70.png') }}" alt=""><span>Hotels</span></a> -->
                <a href="{{ route('air.lounge') }}"><img src="{{ asset('assets/Lounge 70.png') }}" alt=""><span>Lounge</span></a>
                <a href="{{ route('air.protocol') }}"><img src="{{ asset('assets/Protocol 70.png') }}" alt=""><span>Protocol</span></a>
                <a href="{{ route('air.insurance') }}"><img src="{{ asset('assets/Insurance 70.png') }}" alt=""><span>Insurance</span></a>
                <a class="active" href="{{ route('air.visa') }}" aria-current="page"><img src="{{ asset('assets/Visa 70.png') }}" alt=""><span>Visa</span></a>
                <a href="{{ route('air.cargo') }}"><img src="{{ asset('assets/Air Cargo 70.png') }}" alt=""><span>Cargo</span></a>
                <a href="{{ route('air.support') }}"><img src="{{ asset('assets/Support 70.png') }}" alt=""><span>Support</span></a>
            </nav>

            <form class="vw-card" action="{{ route('visa.search') }}" method="POST">
                @csrf
                <div class="vw-card__head">
                    <div><span class="vw-kicker">Visa search</span><h2>Where do you want to go?</h2></div>
                    <div class="vw-pax-wrap">
                        <button class="vw-pax-trigger" type="button" @click="paxOpen = !paxOpen" :aria-expanded="paxOpen.toString()">
                            <span x-text="travelerLabel()"></span>
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>
                        <div class="vw-pax-dropdown" x-show="paxOpen" x-transition x-cloak>
                            <template x-for="row in passengerRows" :key="row.key">
                                <div class="vw-pax-row">
                                    <div><strong x-text="row.label"></strong><small x-text="row.hint"></small></div>
                                    <div class="vw-pax-controls"><button type="button" @click="decrement(row.key)" :disabled="counts[row.key] <= row.min">−</button><span x-text="counts[row.key]"></span><button type="button" @click="increment(row.key)" :disabled="totalPassengers() >= 9 || (row.key === 'infants' && counts.infants >= counts.adults)">+</button></div>
                                </div>
                            </template>
                            <button class="vw-pax-done" type="button" @click="paxOpen = false">Done</button>
                        </div>
                    </div>
                </div>

                @if($errors->any())<div class="vw-errors" role="alert"><strong>Please check your search.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

                <div class="vw-fields">
                    @php $defaultNationalityId = $countries->firstWhere('alpha2', 'NG')?->id; @endphp
                    <label class="vw-field"><span>Passport nationality</span><select name="nationality_id" required><option value="">Choose nationality</option>@foreach($countries as $country)<option value="{{ $country->id }}" @selected((string) old('nationality_id', $defaultNationalityId) === (string) $country->id)>{{ $country->name }} ({{ $country->alpha2 }})</option>@endforeach</select></label>
                    <label class="vw-field"><span>Destination</span><select name="destination_ref" required><option value="">Where are you going?</option>@if($destinationCountries->isNotEmpty())<optgroup label="Countries">@foreach($destinationCountries as $country)<option value="country:{{ $country->id }}" @selected(old('destination_ref') === 'country:'.$country->id)>{{ $country->name }}</option>@endforeach</optgroup>@endif @if($regionalDestinations->isNotEmpty())<optgroup label="Regional visa destinations">@foreach($regionalDestinations as $destination)<option value="region:{{ $destination->id }}" @selected(old('destination_ref') === 'region:'.$destination->id)>{{ $destination->name }}</option>@endforeach</optgroup>@endif</select></label>
                    <label class="vw-field"><span>Arrival date</span><input name="arrival_date" type="date" min="{{ now()->toDateString() }}" value="{{ old('arrival_date', now()->addWeeks(4)->toDateString()) }}" required></label>
                    <label class="vw-field"><span>Departure date</span><input name="departure_date" type="date" min="{{ now()->toDateString() }}" value="{{ old('departure_date', now()->addWeeks(5)->toDateString()) }}" required></label>
                    <label class="vw-field vw-field--optional"><span>Country of residence <small>Optional</small></span><select name="residence_country_id"><option value="">Not specified</option>@foreach($countries as $country)<option value="{{ $country->id }}" @selected(old('residence_country_id') == $country->id)>{{ $country->name }}</option>@endforeach</select></label>
                </div>

                <input type="hidden" name="adults" :value="counts.adults"><input type="hidden" name="children" :value="counts.children"><input type="hidden" name="infants" :value="counts.infants">
                <div class="vw-card__footer"><p>Final eligibility and visa issuance are determined by the relevant authority.</p><button class="vw-search-btn" type="submit">Check visa options <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></button></div>
            </form>
        </div>
    </section>

    <section class="vw-benefits"><div><strong>Clear requirements</strong><span>See what you need before applying.</span></div><div><strong>Transparent fees</strong><span>TravelWheel and authority fees are separated.</span></div><div><strong>Application support</strong><span>Track every step after submission.</span></div></section>

    <script>
        function visaWidget(){return{paxOpen:false,counts:{adults:{{ old('adults',1) }},children:{{ old('children',0) }},infants:{{ old('infants',0) }}},passengerRows:[{key:'adults',label:'Adults',hint:'12+ years',min:1},{key:'children',label:'Children',hint:'2–11 years',min:0},{key:'infants',label:'Infants',hint:'Under 2 years',min:0}],totalPassengers(){return this.counts.adults+this.counts.children+this.counts.infants},travelerLabel(){const total=this.totalPassengers();return `${total} ${total===1?'traveler':'travelers'}`},increment(key){if(this.totalPassengers()<9&&(key!=='infants'||this.counts.infants<this.counts.adults))this.counts[key]++},decrement(key){const min=key==='adults'?1:0;if(this.counts[key]>min)this.counts[key]--;if(key==='adults'&&this.counts.infants>this.counts.adults)this.counts.infants=this.counts.adults}}}
    </script>
</div>
