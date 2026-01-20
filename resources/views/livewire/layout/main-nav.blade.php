<section class="navbarmain">
    <nav class="w-full bg-white shadow-base fixed left-0 z-[var(--z-fixed)] py-2 top-12">
        <div class="mx-auto pt-2 pb-2 flex items-center justify-between px-4">
            <a class="flex items-center" href="{{ route('home') }}">
                <img src="{{ asset('assets/travelwheel.png') }}" class="h-5 w-auto" alt="TravelWheel">
                <sup class="ml-3">
                    <a href="{{ route('air') }}" class="text-gray-700 hover:text-[var(--color-secondary)]">Air</a>
                </sup>
            </a>
            
            <button id="mobile-menu-button" class="block md:hidden text-neutral-700 focus:outline-none" type="button" aria-label="Toggle navigation">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            
            <!-- Desktop menu -->
            <div class="hidden md:flex flex-1 items-center justify-end" id="desktop-menu">
                <ul class="flex space-x-4 items-center">
                    @foreach($menuItems as $item)
                    <li>
                        <a class="product-nav" href="{{ route($item['route']) }}">
                            <div class="nav-content">
                                <img src="{{ asset('assets/' . $item['icon']) }}" class="img-W1" alt="{{ $item['name'] }}">
                                <div class="nav-text">
                                    <span class="main-color text-xs font-semibold">{{ $item['name'] }}</span>
                                    <span class="main-color text-xs">{{ $item['subtitle'] }}</span>
                                </div>
                            </div>
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
            
            <!-- Mobile menu -->
            <div class="mobile-menu md:hidden w-full absolute left-0 top-full bg-white" id="mobile-menu">
                <ul class="flex flex-col space-y-4 p-4">
                    @foreach($menuItems as $item)
                    <li>
                        <a class="product-nav" href="{{ route($item['route']) }}">
                            <div class="nav-content">
                                <img src="{{ asset('assets/' . $item['icon']) }}" class="img-W1" alt="{{ $item['name'] }}">
                                <div class="nav-text">
                                    <span class="main-color text-xs font-semibold">{{ $item['name'] }}</span>
                                    <span class="main-color text-xs">{{ $item['subtitle'] }}</span>
                                </div>
                            </div>
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </nav>
</section>