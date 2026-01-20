<?php

namespace App\Livewire\Layout;

use Livewire\Component;

class MainNav extends Component
{
    public $menuItems = [
        [
            'name' => 'Flight',
            'subtitle' => 'Request',
            'icon' => 'Flight 70.png',
            'route' => 'air.flight'
        ],
        [
            'name' => 'Hotel',
            'subtitle' => 'Booking',
            'icon' => 'Hotel 70.png',
            'route' => 'air.hotel'
        ],
        [
            'name' => 'Airport',
            'subtitle' => 'Protocol',
            'icon' => 'Protocol 70.png',
            'route' => 'air.protocol'
        ],
        [
            'name' => 'Airport',
            'subtitle' => 'Lounge',
            'icon' => 'Lounge 70.png',
            'route' => 'air.lounge'
        ],
        [
            'name' => 'Travel',
            'subtitle' => 'Insurance',
            'icon' => 'Insurance 70.png',
            'route' => 'air.insurance'
        ],
        [
            'name' => 'Visa',
            'subtitle' => 'Assistance',
            'icon' => 'Visa 70.png',
            'route' => 'air.visa'
        ],
        [
            'name' => 'Air',
            'subtitle' => 'Cargo',
            'icon' => 'Air Cargo 70.png',
            'route' => 'air.cargo'
        ],
        [
            'name' => 'Support',
            'subtitle' => 'Flex',
            'icon' => 'Support 70.png',
            'route' => 'air.support'
        ],
    ];

    public function render()
    {
        return view('livewire.layout.main-nav');
    }
}