{{-- Own rules rather than Tailwind utilities, which only work if the stylesheet is rebuilt --}}
<style>
    .tw-topbar-partner-mobile { display: none; }
    /* Below desktop width the full address doesn't fit beside everything else */
    @media (max-width: 1023.98px) {
        .tw-topbar-email-text { display: none; }
    }
    @media (max-width: 767.98px) {
        .tw-topbar-contact { width: 100%; }
        .tw-topbar-partner-mobile { display: flex; align-items: center; margin-left: auto; }
    }
</style>
<div class="tw-topbar w-full bg-[var(--color-primary)] fixed top-0 left-0 pt-1 pb-2 z-[var(--z-fixed)]" style="font-family: 'Opensans-Regular', 'Open Sans', Arial, sans-serif">
    <div class="tw-topbar-inner flex flex-col md:flex-row md:justify-between md:items-center px-4">
        <div class="tw-topbar-contact flex flex-wrap items-center gap-4 py-2">
            <span class="tw-topbar-contact-item flex items-center gap-2">
                <i class="ph-fill ph-phone text-white"></i>
                <b class="text-white">+2348056265618</b>
            </span>
            <span class="tw-topbar-contact-item hidden md:flex items-center gap-2 navtop">
                <i class="ph ph-whatsapp-logo text-white"></i>
                <b class="text-white">+2348032705319</b>
            </span>
            {{-- On phones the address shrinks to a tappable icon to make room for "Partner with us" --}}
            <a class="tw-topbar-contact-item flex items-center gap-2" href="mailto:support@travelwheel.ng" aria-label="Email support@travelwheel.ng">
                <i class="ph-fill ph-envelope-simple text-white"></i>
                <b class="text-white tw-topbar-email-text">support@travelwheel.ng</b>
            </a>
            {{-- Phones: the right-hand links are hidden, so the partner link sits in this row instead --}}
            <a class="tw-topbar-contact-item tw-topbar-partner-mobile" href="{{ route('partners.register') }}" title="Become a TravelWheel partner">
                <b class="text-white">Partner with us</b>
            </a>
        </div>
        <div class="flex flex-wrap items-center py-2 space-x-4 hidden md:flex navtop">
            {{-- Company (About Us), FAQ and Help live in the footer's Company column --}}
            <a class="topnav" href="{{ route('partners.register') }}" title="Become a TravelWheel partner">
                <b class="text-white">Partner with us</b>
            </a>
            <span class="text-white topnav">|</span>
            <a class="topnav" href="https://www.facebook.com/travelwheelng" target="_blank">
                <i class="ph ph-facebook-logo text-white"></i>
            </a>
            <a class="topnav" href="https://x.com/travelwheelng" target="_blank">
                <i class="ph ph-x-logo text-white"></i>
            </a>
            <a class="topnav" href="https://www.instagram.com/travelwheelng/" target="_blank">
                <i class="ph ph-instagram-logo text-white"></i>
            </a>
            <a class="topnav" href="https://ng.linkedin.com/company/travelwheelng" target="_blank">
                <i class="ph ph-linkedin-logo text-white"></i>
            </a>
        </div>
    </div>
</div>
