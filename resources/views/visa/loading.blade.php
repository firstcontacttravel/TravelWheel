@component('layouts.app', ['title' => 'Checking Visa Options'])
@php
    $travelers = (int) $search['adults'] + (int) $search['children'] + (int) $search['infants'];
@endphp
<link rel="stylesheet" href="{{ asset('css/visa-flow.css') }}">
<div class="vl-page" x-data="visaLoader()" x-init="start()">
    <div class="vl-shell">
        <section class="vl-summary" aria-label="Visa search summary">
            <div><span>Nationality</span><strong>{{ $nationality?->name ?? 'Not selected' }}</strong></div>
            <div><span>Destination</span><strong>{{ $destination?->name ?? 'Not selected' }}</strong></div>
            <div><span>Travel dates</span><strong>{{ \Carbon\Carbon::parse($search['arrival_date'])->format('d M Y') }} – {{ \Carbon\Carbon::parse($search['departure_date'])->format('d M Y') }}</strong></div>
            <div><span>Travelers</span><strong>{{ $travelers }} traveler{{ $travelers === 1 ? '' : 's' }}</strong></div>
            <div><span>Residence</span><strong>{{ $residence?->name ?? 'Not specified' }}</strong></div>
        </section>

        <section class="vl-card">
            <img class="vl-media" src="{{ asset('assets/img/Visa stamp passport.svg') }}" alt="Checking visa options">
            <h1>Finding the right visa options for you</h1>
            <p>We are checking published products, nationality rules, requirements, processing times, and indicative fees.</p>
            <div class="vl-progress">
                <div class="vl-progress__meta"><span><i></i><span x-text="statusText"></span></span><strong x-text="progress + '%'">0%</strong></div>
                <div class="vl-progress__track"><span :style="'width:' + progress + '%'" class="vl-progress__bar"></span></div>
            </div>
            <div class="vl-checks"><span>Eligibility rules</span><span>Document requirements</span><span>Fee breakdown</span></div>
            <div class="vl-error" x-ref="errorBox">This is taking longer than expected. <a href="{{ route('air.visa') }}">Start a new search</a>.</div>
        </section>
    </div>
    <div x-ref="runnerWrap"></div>
</div>
<script>
function visaLoader(){return{progress:8,statusText:'Preparing your search',statuses:['Preparing your search','Checking nationality rules','Reviewing available visa products','Comparing processing options','Calculating indicative fees','Opening your results'],start(){const timer=setInterval(()=>{this.progress=Math.min(97,this.progress+(this.progress<75?8:2));this.statusText=this.statuses[Math.min(this.statuses.length-1,Math.floor(this.progress/20))]},550);setTimeout(()=>{const frame=document.createElement('iframe');frame.className='vl-runner';frame.src='{{ route('visa.search.run') }}';frame.onload=()=>{clearInterval(timer);this.progress=100;this.statusText='Opening your results';setTimeout(()=>window.location.href='{{ route('visa.results') }}',300)};this.$refs.runnerWrap.appendChild(frame)},850);setTimeout(()=>this.$refs.errorBox.style.display='block',30000)}}}
</script>
@endcomponent
