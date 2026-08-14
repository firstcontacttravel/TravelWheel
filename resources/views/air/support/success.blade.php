@component('layouts.app', ['title' => 'Request Submitted - TravelWheel'])

<style>
    main.navbarmain.upper-space { padding-top: 90px; }
    @media (max-width: 650px) {
        main.navbarmain.upper-space { padding-top: 50px; }
    }
    .support-success-section { background: linear-gradient(180deg, #f4f6ff 0%, #f8f9fa 220px); padding: 40px 16px 70px; }
    .support-success-card { border-radius: 20px; overflow: hidden; border: none; }
    .support-success-icon {
        width: 96px; height: 96px; margin: 0 auto 22px;
        border-radius: 50%;
        background: #e9f9ee;
        display: flex; align-items: center; justify-content: center;
    }
    .support-success-main { color: rgba(13, 24, 131, 1); font-size: 1.6rem; }
    .support-success-message { color: #667085; font-size: 1rem; line-height: 1.6; }
    .support-success-btn { background-color: rgba(13, 24, 131, 1); color: #fff; border: none; transition: 0.3s ease; }
    .support-success-btn:hover { background-color: rgba(9, 18, 100, 1); color: #fff; }
</style>

<section class="support-success-section d-flex justify-content-center align-items-center">
    <div class="card support-success-card shadow-lg text-center p-5" style="max-width: 500px;">
        <div class="support-success-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="52" height="52" fill="#12b76a" class="bi bi-check-circle-fill" viewBox="0 0 16 16">
                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM6.97 10.03a.75.75 0 0 0 1.08-.02l3.992-4.99a.75.75 0 1 0-1.14-.976L7.477 8.417 5.383 6.323a.75.75 0 0 0-1.06 1.06l2.647 2.647z"/>
            </svg>
        </div>

        <h3 class="support-success-main fw-bold">Request Submitted!</h3>
        @if (session('success'))
            <p class="support-success-main fw-semibold mt-2 mb-0">{{ session('success') }}</p>
        @endif
        <p class="support-success-message mt-3">Your request has been received successfully. Our support team will be in touch shortly.</p>

        <div class="mt-4">
            <a href="{{ route('air.support') }}" class="btn support-success-btn px-4 py-2 rounded-pill">Return to Support</a>
        </div>
    </div>
</section>
@endcomponent
