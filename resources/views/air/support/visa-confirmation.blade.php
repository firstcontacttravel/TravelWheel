@component('layouts.app', ['title' => 'Visa Confirmation - TravelWheel'])

<div class="container mt-3">
    <a href="{{ route('air.support') }}" class="text-decoration-none" style="color: rgba(13, 24, 131, 1); font-weight: 600;">&larr; Back to Support Products</a>
</div>

@include('livewire.pages.support.partials.visa-confirmation1')

@endcomponent
