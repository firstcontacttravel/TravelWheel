@component('layouts.app', ['title' => 'Extra Luggage - TravelWheel'])

<div class="container mt-3">
    <a href="{{ route('air.support') }}" class="text-decoration-none" style="color: rgba(13, 24, 131, 1); font-weight: 600;">&larr; Back to Support Products</a>
</div>

@include('livewire.pages.support.partials.extra1')

@endcomponent
