{{-- A search from the loading page fetches the APIs it didn't reach via
     wire:init; a parallel search fetches every API from the browser instead
     (see the flightResults() Alpine component). --}}
<div class="tw-flight-results-page" @if ($parallel === null) wire:init="loadSupplementalResults" @endif>
    @include('livewire.pages.flight.partials.flight-result')
</div>
