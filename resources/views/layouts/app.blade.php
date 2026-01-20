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
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    
    <style>
        @media (max-width: 650px) {
            .navbarmain {
                padding-top: 50px;
            }
        }
        .img-W1 {
            width: 25px;
            height: auto;
        }
        .nav-content {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .nav-text {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }
        .product-nav {
            color: inherit;
            text-decoration: none;
        }
        body, .navbarmain, .topnav, .product-nav, .nav-content, .nav-text, .navbarmain * {
            font-family: "Opensans-Regular", "Open Sans", Arial, sans-serif !important;
        }
        .mobile-menu {
            display: block;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        .mobile-menu.show {
            max-height: 500px;
        }
        @media (max-width: 768px) {
            .mobile-menu {
                width: 100%;
                background-color: white;
                border-top: 1px solid #eee;
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