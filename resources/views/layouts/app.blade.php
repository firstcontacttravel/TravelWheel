<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'TravelWheel - Air Transport' }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    
    <!-- Phosphor Icons -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="{{ asset('bootstrap-5.0.2/dist/css/bootstrap.min.css') }}">
    <script src="{{ asset('bootstrap-5.0.2/dist/js/bootstrap.bundle.min.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('fontawesome-6/dist-font/css/font-awesome.min.css') }}">

    <!-- Custom Styles -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/styleslide.css') }}">
    
    <style>
        @media (max-width: 650px) {
            .navbarmain {
                padding-top: var(--space-16);
            }
        }
        .img-W1 {
            width: 25px;
            height: auto;
        }
        .nav-content {
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }
        .nav-text {
            display: flex;
            flex-direction: column;
            line-height: var(--leading-snug);
        }
        .product-nav {
            color: inherit;
            text-decoration: none;
        }
        body, .navbarmain, .topnav, .product-nav, .nav-content, .nav-text, .navbarmain * {
            font-family: var(--font-primary) !important;
        }
        .mobile-menu {
            display: block;
            max-height: 0;
            overflow: hidden;
            transition: max-height var(--transition-base) ease-out;
        }
        .mobile-menu.show {
            max-height: 500px;
        }
        @media (max-width: 768px) {
            .mobile-menu {
                width: 100%;
                background-color: white;
                border-top: 1px solid var(--color-neutral-200);
            }
        }
    </style>
    
    @livewireStyles
</head>
<body>
    <!-- Top Navigation Bar -->
    <livewire:layout.top-nav />
    
    <!-- Main Navigation Bar -->
    <livewire:layout.main-nav />
    
    <!-- Main Content -->
    <main class="navbarmain">
        {{ $slot }}
    </main>
    
    <!-- Footer -->
    <livewire:layout.footer />
    
    @livewireScripts
    
    <!-- Mobile Menu Toggle Script -->
    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');
            
            if (mobileMenuButton && mobileMenu) {
                mobileMenuButton.addEventListener('click', () => {
                    requestAnimationFrame(() => {
                        mobileMenu.classList.toggle('show');
                    });
                });
            }
        });
    </script>
</body>
</html>