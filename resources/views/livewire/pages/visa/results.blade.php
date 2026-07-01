<div class="vr-page">
    <link rel="stylesheet" href="{{ asset('css/visa-flow.css') }}">
    @php
        $travelerTotal = array_sum($searchParams['travelers'] ?? []);
        $eligibleCount = collect($results)->whereIn('eligibility.status', ['eligible', 'conditionally_eligible'])->count();
    @endphp

    <header class="vr-topbar">
        <div class="vr-topbar__inner">
            <div class="vr-route"><span>{{ $searchParams['nationality_name'] ?? 'Nationality' }}</span><b>→</b><span>{{ $searchParams['destination_name'] ?? 'Destination' }}</span></div>
            <div class="vr-meta"><span>{{ isset($searchParams['arrival_date']) ? \Carbon\Carbon::parse($searchParams['arrival_date'])->format('d M Y') : 'Travel date' }} – {{ isset($searchParams['departure_date']) ? \Carbon\Carbon::parse($searchParams['departure_date'])->format('d M Y') : '' }}</span><span>{{ $travelerTotal }} traveler{{ $travelerTotal === 1 ? '' : 's' }}</span></div>
            <a class="vr-modify" href="{{ route('air.visa') }}">Modify search</a>
        </div>
    </header>

    <div class="vr-layout">
        <aside class="vr-sidebar">
            <section class="vr-panel"><div class="vr-panel__head"><strong>Eligibility</strong></div><div class="vr-panel__body"><span>{{ $eligibleCount }} available option{{ $eligibleCount === 1 ? '' : 's' }}</span><small>Based on the nationality and residence supplied.</small></div></section>
            <section class="vr-panel"><div class="vr-panel__head"><strong>Your trip</strong></div><dl class="vr-details"><div><dt>Nationality</dt><dd>{{ $searchParams['nationality_name'] ?? '—' }}</dd></div><div><dt>Residence</dt><dd>{{ $searchParams['residence_name'] ?? 'Not specified' }}</dd></div><div><dt>Destination</dt><dd>{{ $searchParams['destination_name'] ?? '—' }}</dd></div><div><dt>Travelers</dt><dd>{{ $travelerTotal }}</dd></div></dl></section>
            <section class="vr-help"><strong>Need help choosing?</strong><p>Our visa team can explain requirements before you start an application.</p></section>
        </aside>

        <main class="vr-main">
            <div class="vr-heading"><div><h1>{{ count($results) }} visa {{ count($results) === 1 ? 'option' : 'options' }} found</h1><p>Compare eligibility, processing estimates, requirements, and fees.</p></div><span>Indicative prices</span></div>

            @forelse($results as $result)
                @php
                    $status = $result['eligibility']['status'];
                    $processing = $result['estimate']['processing_option'];
                    $canApply = in_array($status, ['eligible', 'conditionally_eligible'], true);
                @endphp
                <article class="vr-card {{ $canApply ? '' : 'vr-card--disabled' }}">
                    <div class="vr-card__head">
                        <div class="vr-stamp"><img src="{{ asset('assets/Visa 70.png') }}" alt=""></div>
                        <div><span class="vr-family">{{ $result['family'] === 'voa' ? 'Visa on arrival' : 'Standard visa' }}</span><h2>{{ $result['name'] }}</h2><p>{{ str($result['category'])->headline() }} · {{ str($result['entry_type'])->headline() }} entry</p></div>
                        <span class="vr-status vr-status--{{ $status }}">{{ str($status)->replace('_',' ')->headline() }}</span>
                    </div>
                    <div class="vr-card__body">
                        <div class="vr-facts"><div><span>Processing</span><strong>{{ $processing ? $processing['minimum_business_days'].'–'.$processing['maximum_business_days'].' business days' : 'Confirm with TravelWheel' }}</strong></div><div><span>Validity</span><strong>{{ $result['validity_days'] ? $result['validity_days'].' days' : 'Product-specific' }}</strong></div><div><span>Maximum stay</span><strong>{{ $result['maximum_stay_days'] ? $result['maximum_stay_days'].' days' : 'Confirm before applying' }}</strong></div></div>
                        @if($result['summary'])<p class="vr-summary">{{ $result['summary'] }}</p>@endif
                        @foreach($result['eligibility']['messages'] as $message)<div class="vr-message">{{ $message }}</div>@endforeach
                        <details class="vr-requirements"><summary>View {{ count($result['requirements']) }} requirements</summary><ul>@foreach($result['requirements'] as $requirement)<li><span>{{ $requirement['name'] }}</span><small>{{ str($requirement['state'])->headline() }}</small></li>@endforeach</ul></details>
                    </div>
                    <footer class="vr-card__footer">
                        <div class="vr-prices">
                            @forelse($result['estimate']['pay_now_totals'] as $currency => $amount)<span>Estimated pay now <strong>{{ $currency }} {{ number_format($amount,2) }}</strong></span>@empty<span>Price confirmed before payment</span>@endforelse
                            @foreach($result['estimate']['pay_separately_totals'] as $currency => $amount)<small>Plus {{ $currency }} {{ number_format($amount,2) }} paid directly to authority</small>@endforeach
                        </div>
                        @if($canApply)<form method="POST" action="{{ route('visa.applications.start') }}">@csrf<input type="hidden" name="visa_product_id" value="{{ $result['id'] }}"><button class="vr-apply" type="submit">Start application</button></form>@else<span class="vr-unavailable">Not available for this search</span>@endif
                    </footer>
                </article>
            @empty
                <section class="vr-empty"><img src="{{ asset('assets/img/Visa stamp passport.svg') }}" alt=""><h2>No visa products found</h2><p>There is no published TravelWheel product for this destination yet.</p><a href="{{ route('air.visa') }}">Try another search</a></section>
            @endforelse
        </main>

        <aside class="vr-rail"><section><strong>How it works</strong><ol><li><b>1</b><span>Choose a visa product</span></li><li><b>2</b><span>Complete traveler details</span></li><li><b>3</b><span>Upload requirements</span></li><li><b>4</b><span>Review and pay</span></li></ol></section><p>Final issuance is controlled by the relevant immigration authority.</p></aside>
    </div>
</div>
