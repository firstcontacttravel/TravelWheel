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
