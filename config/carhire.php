<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Fare Rules
    |--------------------------------------------------------------------------
    |
    | Shown in the "Fare Rules" popup on the Car Hire / Pick up 'n' Drop off
    | booking page and in the Car Hire confirmation email, so the customer is
    | sent exactly the rules they agreed to. Edit them here only.
    |
    */

    'fare_rules' => [
        [
            'title' => 'Cancellation',
            'items' => [
                'Cancellation is free of charge up to 5 hours prior to the trip. The money will be refunded in full to the card, bank account or credit limit according to the terms of the agreement.',
                'If you cancel a paid order less than 5 hours before the start of the trip, we will not be able to refund the money.',
                'If an order canceled less than 5 hours before the start of the trip has not been paid, you will have to pay a penalty of 100% of the order value.',
            ],
        ],
        [
            'title' => 'Changing the Order',
            'items' => [
                'We do not charge a fee for the very fact of making changes, but if you change your route, car class or make other significant changes, this may result in a change in price.',
            ],
        ],
        [
            'title' => 'What is Included in the Transfer Price',
            'items' => [
                'The price includes: a trip from point A to point B, transport fees, tips, meeting the passenger with the sign, escorting with baggage from the meeting point to the car.',
                'Possible car options: Toyota Hiace, Opel Vivaro, Hyundai H1 or similar.',
                'Free waiting time is 90 minutes. Additional waiting time is charged separately.',
            ],
        ],
        [
            'title' => 'Baggage Allowance',
            'items' => [
                'Baggage count is calculated based on the standard size of one piece of baggage: 55x45x25 cm (22x18x10 inches).',
                'Please contact Customer Support if the passenger is going to have oversize baggage. We will reach the service provider in order to pick the appropriate car.',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fare Rules per State
    |--------------------------------------------------------------------------
    |
    | Extra rules for one state, on top of the fare rules above. A section
    | with the same title as one above replaces it for that state (Abuja
    | uses its vendor's cancellation and rescheduling charges); any other
    | section is added after them. Shown in the popup and the confirmation
    | email for bookings in that state.
    |
    */

    'state_fare_rules' => [
        'abuja' => [
            [
                'title' => 'Cancellation',
                'items' => [
                    'Bookings cancelled 72 hours or more before the trip attract a 40% convenience fee before refund.',
                    'Bookings cancelled 24 to 48 hours before the trip attract a 50% convenience fee before refund.',
                    'Bookings made and cancelled on the same day attract a 50% convenience fee before refund, or 20% of the fare as a rescheduling charge.',
                    'Bookings made earlier and cancelled on the day of the trip are treated as a no-show and are non-refundable.',
                ],
            ],
            [
                'title' => 'Changing the Order',
                'items' => [
                    'Rescheduling 72 hours or more before the trip attracts 20% of the fare as a rescheduling charge.',
                    'Rescheduling 24 to 48 hours before the trip attracts 30% of the fare as a rescheduling charge.',
                    'Changes and upgrades must be finalised before the trip starts. Once the trip has started, a longer trip is billed as an extension at our standard hourly rates.',
                ],
            ],
            [
                'title' => 'Booking & What is Included (Abuja)',
                'items' => [
                    'A valid means of identification is needed to process a booking, and payment confirms it. Your details are handled in line with the Nigeria Data Protection Regulation (NDPR).',
                    'All rates include fuel and a chauffeur. A priority chauffeur costs ₦10,000 extra for sedans and ₦20,000 for SUVs.',
                    'Extra hours on a daily rental are charged extra. Unused hours are not rolled over.',
                    'Trips starting before 7am or running past midnight attract an extra ₦10,000.',
                ],
            ],
            [
                'title' => 'Locations (Abuja)',
                'items' => [
                    'Rates apply to points within the city. Drop-off charges depend on the location.',
                    'Pick-ups and drop-offs on the outskirts of town (e.g. Kugbo, Karsana, Brains and Hammers City, Life Camp, Lugbe, Kubwa, Dawaki, Dutse Alhaji, Bwari, Karu, Idu Train Station, Idu, Karmo, Kuje, Kurudu, Mararaba, Dei-Dei, Nyanya, Zuba, Gwagwalada) attract an extra ₦20,000 to ₦30,000 for sedans and ₦30,000 to ₦50,000 for SUVs, or more depending on the distance.',
                ],
            ],
            [
                'title' => 'Airport Trips (Abuja)',
                'items' => [
                    'A 10 or 12 hour daily rental includes one free airport pick-up or drop-off. A 24 hour rental includes two airport trips (pick-ups, drop-offs or one of each).',
                    'Rentals below 10 hours do not include an airport pick-up or drop-off.',
                    'More than one airport pick-up in a 12 hour rental is charged at 50% of the airport fare on top of the daily rental, or 4 hours are deducted from the rental.',
                    'Waiting longer than 30 minutes at a domestic airport, or 1 hour at an international airport, is charged by the hour and must be paid before the trip starts.',
                    'Airport pick-ups and drop-offs outside the main town attract extra charges. Ride-along airport trips are charged at 100% of the airport fee.',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | States
    |--------------------------------------------------------------------------
    |
    | Every state, by region, for the "Select your state" picker on the
    | booking page and the State field on each fleet car in admin. Service
    | opens in a state as soon as admin adds an active fleet car there, so
    | nothing here needs to change to launch a new state. "example" is the
    | address hint shown in that state's location fields, and "airport" the
    | hint in the Pick up 'n' Drop off pick-up field.
    |
    */

    'default_state' => 'lagos',

    'states' => [
        'South West' => [
            'lagos' => ['name' => 'Lagos', 'example' => 'e.g. Victoria Island, Lagos', 'airport' => 'e.g. Murtala Muhammed Airport'],
            'ogun' => ['name' => 'Ogun'],
            'oyo' => ['name' => 'Oyo'],
            'osun' => ['name' => 'Osun'],
            'ondo' => ['name' => 'Ondo'],
            'ekiti' => ['name' => 'Ekiti'],
        ],
        'South East' => [
            'anambra' => ['name' => 'Anambra'],
            'enugu' => ['name' => 'Enugu'],
            'imo' => ['name' => 'Imo'],
            'abia' => ['name' => 'Abia'],
            'ebonyi' => ['name' => 'Ebonyi'],
        ],
        'South South' => [
            'rivers' => ['name' => 'Rivers', 'example' => 'e.g. GRA Phase 2, Port Harcourt', 'airport' => 'e.g. Port Harcourt International Airport'],
            'delta' => ['name' => 'Delta'],
            'edo' => ['name' => 'Edo'],
            'cross_river' => ['name' => 'Cross River'],
            'akwa_ibom' => ['name' => 'Akwa Ibom'],
            'bayelsa' => ['name' => 'Bayelsa'],
        ],
        'North West' => [
            'kano' => ['name' => 'Kano'],
            'kaduna' => ['name' => 'Kaduna'],
            'sokoto' => ['name' => 'Sokoto'],
            'katsina' => ['name' => 'Katsina'],
            'zamfara' => ['name' => 'Zamfara'],
            'kebbi' => ['name' => 'Kebbi'],
            'jigawa' => ['name' => 'Jigawa'],
        ],
        'North East' => [
            'borno' => ['name' => 'Borno'],
            'yobe' => ['name' => 'Yobe'],
            'adamawa' => ['name' => 'Adamawa'],
            'taraba' => ['name' => 'Taraba'],
            'bauchi' => ['name' => 'Bauchi'],
            'gombe' => ['name' => 'Gombe'],
        ],
        'North Central' => [
            'abuja' => ['name' => 'FCT Abuja', 'example' => 'e.g. Wuse 2, Abuja', 'airport' => 'e.g. Nnamdi Azikiwe International Airport'],
            'kogi' => ['name' => 'Kogi'],
            'kwara' => ['name' => 'Kwara'],
            'benue' => ['name' => 'Benue'],
            'plateau' => ['name' => 'Plateau'],
            'nasarawa' => ['name' => 'Nasarawa'],
            'niger' => ['name' => 'Niger'],
        ],
    ],

];
