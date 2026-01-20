<footer class="mt-32 footter">
    <section class="w-full bg-[var(--color-primary)]">
        <div class="flex flex-wrap p-3">
            <div class="w-full md:w-1/6 p-2 flex flex-col items-start">
                <a class="mb-2" href="{{ route('home') }}" id="a-nav">
                    <img src="https://travelwheel.ng/public/assets/img/TravelwheelAlt.png" class="w-36" alt="TravelWheel">
                </a>
                <small class="text-white mt-2">A Product of</small>
                <img src="https://travelwheel.ng/public/assets/img/FCTF.png" alt="FCTF">
                <small class="text-white mt-2">
                    <a href="https://www.google.com/maps/search/?api=1&query=74+Ayangburen+Road+Ikorodu+Lagos+Nigeria" target="_blank">
                        74, Ayangburen Road, Ikorodu, Lagos. Nigeria
                    </a>
                </small>
            </div>
            
            <div class="w-1/2 md:w-1/6 p-2">
                <h4 class="text-white font-bold mb-2">Products</h4>
                <ul>
                    <li>
                        <a href="{{ route('air') }}" class="text-white hover:text-[var(--color-secondary)]">Air Transport</a>
                    </li>
                </ul>
            </div>
            
            <div class="w-1/2 md:w-1/6 p-2">
                <h4 class="text-white font-bold mb-2">Features</h4>
                <ul>
                    <li><a href="{{ route('air.flight') }}" class="text-white hover:text-[var(--color-secondary)]">Flight Tickets</a></li>
                    <li><a href="{{ route('air.hotel') }}" class="text-white hover:text-[var(--color-secondary)]">Hotel Bookings</a></li>
                    <li><a href="{{ route('air.protocol') }}" class="text-white hover:text-[var(--color-secondary)]">Protocol Service</a></li>
                    <li><a href="{{ route('air.lounge') }}" class="text-white hover:text-[var(--color-secondary)]">Airport Lounge</a></li>
                    <li><a href="{{ route('air.insurance') }}" class="text-white hover:text-[var(--color-secondary)]">Travel Insurance</a></li>
                    <li><a href="{{ route('air.visa') }}" class="text-white hover:text-[var(--color-secondary)]">Visa Assistance</a></li>
                </ul>
            </div>
            
            <div class="w-1/2 md:w-1/6 p-2">
                <h4 class="text-white font-bold mb-2">Company</h4>
                <ul>
                    <li><a href="{{ route('aboutus') }}" class="text-white hover:text-[var(--color-secondary)]">About Us</a></li>
                    <li><a href="#" class="text-white hover:text-[var(--color-secondary)]">Media</a></li>
                    <li><a href="#" class="text-white hover:text-[var(--color-secondary)]">Terms & Condition</a></li>
                    <li><a href="/blog" target="_blank" class="text-white hover:text-[var(--color-secondary)]">Blog</a></li>
                </ul>
            </div>
            
            <div class="w-1/2 md:w-1/6 p-2">
                <h4 class="text-white font-bold mb-2">Help</h4>
                <ul>
                    <li><a href="{{ route('faq') }}" class="text-white hover:text-[var(--color-secondary)]">FAQ</a></li>
                    <li><a href="{{ route('help') }}" class="text-white hover:text-[var(--color-secondary)]">Contact</a></li>
                </ul>
            </div>
            
            <div class="w-full md:w-1/6 p-2 flex flex-col items-start">
                <div class="mb-3 w-full flex">
                    <div class="w-1/2">
                        <small class="text-white mt-2">A Member of</small>
                        <img src="https://travelwheel.ng/public/assets/img/nanta.jpg" class="w-16 mt-1" alt="NANTA">
                    </div>
                    <div class="w-1/2">
                        <small class="text-white mt-2">Certified By</small>
                        <img src="https://travelwheel.ng/public/assets/img/ncaa.jpg" class="w-16 mt-1" alt="NCAA">
                    </div>
                </div>
                <h4 class="text-white font-bold mb-2">Social Media</h4>
                <div class="flex space-x-2">
                    <a href="https://www.facebook.com/travelwheelng" class="facebook text-white hover:text-blue-500" target="_blank">
                        <i class="ph-fill ph-facebook-logo p-2"></i>
                    </a>
                    <a href="https://x.com/travelwheelng" class="twitter text-white hover:text-blue-400" target="_blank">
                        <i class="ph-fill ph-twitter-logo-fill p-2"></i>
                    </a>
                    <a href="https://www.instagram.com/travelwheelng/" class="instagram text-white hover:text-pink-500" target="_blank">
                        <i class="ph-fill ph-instagram-logo p-2"></i>
                    </a>
                    <a href="https://ng.linkedin.com/company/travelwheelng" class="linkedin text-white hover:text-blue-700" target="_blank">
                        <i class="ph-fill ph-linkedin-logo p-2"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>
</footer>