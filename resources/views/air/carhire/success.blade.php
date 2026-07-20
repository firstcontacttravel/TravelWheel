@component('layouts.app', ['title' => 'Booking Successful - TravelWheel'])

<section class="d-flex justify-content-center align-items-center" style="padding: 60px 16px;">
    <style>
        .carhire-success-card { border-radius: 15px; overflow: hidden; }
        .carhire-success-main { color: rgba(13, 24, 131, 1); }
        .carhire-success-btn { background-color: rgba(13, 24, 131, 1); color: #fff; border: none; transition: 0.3s ease; }
        .carhire-success-btn:hover { background-color: rgba(9, 18, 100, 1); color: #fff; }
    </style>

    <div class="card carhire-success-card shadow-lg text-center p-5" style="max-width: 500px;">
        <div class="mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="green" class="bi bi-check-circle-fill" viewBox="0 0 16 16">
                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM6.97 10.03a.75.75 0 0 0 1.08-.02l3.992-4.99a.75.75 0 1 0-1.14-.976L7.477 8.417 5.383 6.323a.75.75 0 0 0-1.06 1.06l2.647 2.647z"/>
            </svg>
        </div>

        <h3 class="carhire-success-main fw-bold">Successful!</h3>
        @if (session('success'))
            <div class="alert alert-success text-center mt-2">{{ session('success') }}</div>
        @endif
        <p class="mt-2">Thank you for your payment. Your booking has been received successfully.</p>

        <div class="mt-3">
            <a href="{{ route('air.carhire') }}" class="btn carhire-success-btn px-4 py-2 rounded-pill">Return to Car Hire &amp; Transfers</a>
        </div>
    </div>
</section>
@endcomponent
