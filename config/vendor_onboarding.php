<?php

/*
|--------------------------------------------------------------------------
| Vendor / partner onboarding form
|--------------------------------------------------------------------------
| Everything the digital vendor registration form asks, by section. The
| public form (App\Livewire\Pages\Partners\VendorRegistration) and the admin
| review screen both read this file, so a question added here appears in
| both. Keys are stored with each application; don't rename a key once
| applications have used it, or older answers stop showing.
|
| Field types: text, textarea, yesno, select (options), checkboxes (options).
*/

return [

    // Who is told about new applications. Vendor emails never go to the
    // reservations copy list; these are partnership matters, not bookings.
    'notify_email' => env('PARTNERS_EMAIL', env('RESERVATIONS_EMAIL', 'reservation@travelwheel.ng')),

    'business_types' => [
        'travel_agency' => 'Travel Agency',
        'tour_operator' => 'Tour Operator',
        'airline' => 'Airline / Flight Provider',
        'consolidator' => 'Ticketing Consolidator',
        'airport_services' => 'Airport Service Provider',
        'hotel' => 'Hotel / Accommodation Provider',
        'car_rental' => 'Car Rental Company',
        'transport' => 'Transport / Shuttle Operator',
        'visa' => 'Visa Assistance Provider',
        'insurance' => 'Insurance Provider / Broker',
        'cargo' => 'Cargo / Freight / Courier Company',
        'health' => 'Health / Vaccination Centre',
    ],

    'booking_channels' => [
        'email' => 'Email',
        'whatsapp' => 'WhatsApp',
        'phone' => 'Phone call',
        'api' => 'API / system integration',
        'portal' => 'Your own extranet / portal',
    ],

    'rate_models' => [
        'net' => 'Net rates (we add our margin)',
        'commission' => 'Commission on your public rate',
        'both' => 'Both, depending on the service',
    ],

    'settlement_currencies' => [
        'NGN' => 'Nigerian Naira (NGN)',
        'USD' => 'US Dollar (USD)',
        'GBP' => 'British Pound (GBP)',
        'EUR' => 'Euro (EUR)',
    ],

    /*
    | Every vendor uploads these, whatever they offer. `required` documents
    | must be attached; the rest are "where applicable". `expires` asks for
    | the document's expiry date so the team can re-check it in time.
    */
    'company_documents' => [
        'incorporation' => ['label' => 'Business registration / incorporation certificate (e.g. CAC)', 'required' => true],
        'company_profile' => ['label' => 'Company profile', 'required' => true],
        'proof_of_address' => ['label' => 'Proof of business address (utility bill, tenancy, etc.)', 'required' => true],
        'director_info' => ['label' => 'Director / owner information (e.g. CAC status report)', 'required' => true],
        'representative_id' => ['label' => 'ID of the authorised representative', 'required' => true, 'expires' => true],
        'rate_sheet' => ['label' => 'Current price / rate sheet', 'required' => true],
        'terms' => ['label' => 'Terms & conditions', 'required' => true],
        'refund_policy' => ['label' => 'Refund / cancellation policy', 'required' => true],
        'tax_id' => ['label' => 'Tax identification (TIN) certificate', 'required' => false],
        'business_licence' => ['label' => 'Business licence(s)', 'required' => false, 'expires' => true],
        'privacy_policy' => ['label' => 'Data protection / privacy policy', 'required' => false],
        'insurance' => ['label' => 'Business insurance documentation', 'required' => false, 'expires' => true],
    ],

    /*
    | One entry per service a vendor can offer. `fields` are the questions
    | for that service; `documents` are uploads on top of company_documents.
    */
    'services' => [

        'flights' => [
            'label' => 'Flights',
            'intro' => 'Airline tickets, consolidation or B2B fares.',
            'fields' => [
                'airlines' => ['label' => 'Airlines / consolidators you work with', 'type' => 'textarea', 'required' => true],
                'gds' => ['label' => 'GDS or booking platform (e.g. Amadeus, Sabre, Travelport, NDC)', 'type' => 'text'],
                'iata_accredited' => ['label' => 'Are you IATA accredited?', 'type' => 'yesno', 'required' => true],
                'iata_number' => ['label' => 'IATA number (if accredited)', 'type' => 'text'],
                'routes' => ['label' => 'Routes / countries covered', 'type' => 'textarea', 'required' => true],
                'cabins' => ['label' => 'Cabins offered', 'type' => 'checkboxes', 'options' => ['Economy', 'Premium Economy', 'Business', 'First']],
                'group_bookings' => ['label' => 'Do you handle group bookings?', 'type' => 'yesno'],
                'ticketing_time_limit' => ['label' => 'Ticketing time limits', 'type' => 'text'],
                'change_rules' => ['label' => 'Change / rebooking rules', 'type' => 'textarea'],
                'refund_policy' => ['label' => 'Cancellation / refund policy', 'type' => 'textarea', 'required' => true],
                'baggage' => ['label' => 'Baggage policy', 'type' => 'textarea'],
                'rates' => ['label' => 'Commission / B2B rates', 'type' => 'textarea', 'required' => true],
                'emergency_contact' => ['label' => 'Emergency / after-hours ticketing contact', 'type' => 'text', 'required' => true],
            ],
            'documents' => [
                'iata_certificate' => ['label' => 'IATA certificate / accreditation', 'required' => false, 'expires' => true],
                'consolidator_agreement' => ['label' => 'Airline / consolidator agreement', 'required' => false],
            ],
        ],

        'hotels' => [
            'label' => 'Hotels & Accommodation',
            'intro' => 'Hotels, serviced apartments and short-let accommodation.',
            'fields' => [
                'properties' => ['label' => 'Properties and their locations', 'type' => 'textarea', 'required' => true],
                'star_ratings' => ['label' => 'Star ratings / property types', 'type' => 'text'],
                'room_types' => ['label' => 'Room types available', 'type' => 'textarea'],
                'meal_plans' => ['label' => 'Meal plans', 'type' => 'checkboxes', 'options' => ['Room only', 'Bed & breakfast', 'Half board', 'Full board', 'All inclusive']],
                'check_in_out' => ['label' => 'Check-in / check-out times', 'type' => 'text'],
                'airport_shuttle' => ['label' => 'Do you offer an airport shuttle?', 'type' => 'yesno'],
                'cancellation' => ['label' => 'Cancellation policy', 'type' => 'textarea', 'required' => true],
                'rates' => ['label' => 'Net / commissionable rates and seasons', 'type' => 'textarea', 'required' => true],
            ],
            'documents' => [
                'property_photos' => ['label' => 'Property photos / brochure', 'required' => false],
                'hotel_licence' => ['label' => 'Hotel / tourism operating licence', 'required' => false, 'expires' => true],
            ],
        ],

        'lounge' => [
            'label' => 'Airport Lounge',
            'intro' => 'Lounge access at Nigerian or international airports.',
            'fields' => [
                'airports' => ['label' => 'Airport(s) and terminal(s)', 'type' => 'textarea', 'required' => true],
                'lounges' => ['label' => 'Lounge name(s) and location inside the terminal', 'type' => 'textarea', 'required' => true],
                'access_type' => ['label' => 'Lounge access', 'type' => 'checkboxes', 'options' => ['International', 'Local / domestic']],
                'opening_hours' => ['label' => 'Opening hours', 'type' => 'text', 'required' => true],
                'eligible_passengers' => ['label' => 'Eligible passengers (cabin, airline, departures/arrivals)', 'type' => 'textarea'],
                'max_stay' => ['label' => 'Maximum stay', 'type' => 'text'],
                'price_adult' => ['label' => 'Net price per adult', 'type' => 'text', 'required' => true],
                'price_child' => ['label' => 'Net price per child', 'type' => 'text'],
                'price_infant' => ['label' => 'Net price per infant', 'type' => 'text'],
                'facilities' => ['label' => 'Facilities (Wi-Fi, food, showers, etc.)', 'type' => 'textarea'],
                'accessibility' => ['label' => 'Accessibility', 'type' => 'text'],
                'cancellation' => ['label' => 'Booking / cancellation terms', 'type' => 'textarea', 'required' => true],
                'voucher_process' => ['label' => 'How guests are admitted (voucher, name list, QR code)', 'type' => 'textarea', 'required' => true],
            ],
            'documents' => [
                'lounge_agreement' => ['label' => 'Lounge authorisation / agreement with the airport', 'required' => true, 'expires' => true],
                'lounge_photos' => ['label' => 'Service description / photos', 'required' => false],
            ],
        ],

        'protocol' => [
            'label' => 'Airport Protocol / Meet & Assist',
            'intro' => 'Meet & greet, fast-track and VIP airport assistance.',
            'fields' => [
                'airports' => ['label' => 'Airports covered', 'type' => 'textarea', 'required' => true],
                'movements' => ['label' => 'Movements covered', 'type' => 'checkboxes', 'options' => ['Arrival', 'Departure', 'Transit'], 'required' => true],
                'services' => ['label' => 'Services offered', 'type' => 'checkboxes', 'options' => ['Meet & greet', 'Fast-track', 'Immigration assistance (where legally permitted)', 'Baggage assistance', 'Wheelchair coordination', 'VIP / VVIP', 'Lounge coordination', 'Escort']],
                'hours' => ['label' => 'Operating hours', 'type' => 'text', 'required' => true],
                'emergency_contact' => ['label' => 'Emergency / on-duty contact', 'type' => 'text', 'required' => true],
                'rates' => ['label' => 'Net rates per passenger / plan', 'type' => 'textarea', 'required' => true],
                'cancellation' => ['label' => 'Cancellation policy', 'type' => 'textarea', 'required' => true],
            ],
            'documents' => [
                'airport_authorisation' => ['label' => 'Airport / FAAN service-provider authorisation', 'required' => true, 'expires' => true],
                'service_agreement' => ['label' => 'Service agreement', 'required' => false],
                'staff_credentials' => ['label' => 'Staff airport passes / credentials', 'required' => false, 'expires' => true],
            ],
        ],

        'car_hire' => [
            'label' => 'Car Hire',
            'intro' => 'Chauffeur-driven or self-drive vehicle rental by the hour or day.',
            'fields' => [
                'locations' => ['label' => 'Cities / locations served', 'type' => 'textarea', 'required' => true],
                'vehicle_types' => ['label' => 'Vehicle types', 'type' => 'checkboxes', 'options' => ['Saloon', 'SUV', 'Luxury', 'Bus / Van'], 'required' => true],
                'categories' => ['label' => 'Vehicle categories by model year', 'type' => 'checkboxes', 'options' => ['Regular (2005–2015)', 'Standard (2016–2019)', 'Executive (2020 and newer)'], 'required' => true],
                'fleet_size' => ['label' => 'Number of vehicles in your fleet', 'type' => 'text', 'required' => true],
                'drive_options' => ['label' => 'Drive options', 'type' => 'checkboxes', 'options' => ['Chauffeur-driven', 'Self-drive']],
                'airport_collection' => ['label' => 'Airport collection / drop-off?', 'type' => 'yesno'],
                'one_way' => ['label' => 'One-way rental?', 'type' => 'yesno'],
                'driver_vetting' => ['label' => 'How your drivers are vetted (licence checks, background checks, training)', 'type' => 'textarea', 'required' => true],
                'deposit' => ['label' => 'Deposit requirements', 'type' => 'text'],
                'insurance_cover' => ['label' => 'Vehicle insurance cover', 'type' => 'textarea', 'required' => true],
                'fuel_mileage' => ['label' => 'Fuel / mileage policy', 'type' => 'textarea'],
                'child_seats' => ['label' => 'Child seats available?', 'type' => 'yesno'],
                'emergency_assistance' => ['label' => 'Breakdown / emergency assistance', 'type' => 'textarea', 'required' => true],
                'rates' => ['label' => 'Net rates (per hour / day)', 'type' => 'textarea', 'required' => true],
                'cancellation' => ['label' => 'Cancellation policy', 'type' => 'textarea', 'required' => true],
            ],
            'documents' => [
                'fleet_list' => ['label' => 'Fleet list (make, model, year, plate number)', 'required' => true],
                'vehicle_insurance' => ['label' => 'Vehicle insurance certificates', 'required' => true, 'expires' => true],
                'rental_licence' => ['label' => 'Vehicle / rental licence', 'required' => false, 'expires' => true],
                'driver_list' => ['label' => 'Driver list with licence numbers', 'required' => false],
            ],
        ],

        'transfers' => [
            'label' => "Pick up 'n' Drop off (Transfers)",
            'intro' => 'Airport transfers and point-to-point rides between cities, towns and rural areas.',
            'fields' => [
                'coverage' => ['label' => 'Airports, cities and routes covered', 'type' => 'textarea', 'required' => true],
                'vehicle_types' => ['label' => 'Vehicle types', 'type' => 'checkboxes', 'options' => ['Saloon', 'SUV', 'Luxury', 'Bus / Van'], 'required' => true],
                'max_passengers' => ['label' => 'Maximum passengers and luggage per vehicle type', 'type' => 'textarea'],
                'name_board' => ['label' => 'Meet & greet with name board at the airport?', 'type' => 'yesno'],
                'flight_monitoring' => ['label' => 'Do you track flights for delays?', 'type' => 'yesno'],
                'waiting_time' => ['label' => 'Free waiting time and waiting charges', 'type' => 'text', 'required' => true],
                'driver_vetting' => ['label' => 'How your drivers are vetted', 'type' => 'textarea', 'required' => true],
                'rates' => ['label' => 'Net rates per route or per km', 'type' => 'textarea', 'required' => true],
                'cancellation' => ['label' => 'Cancellation policy', 'type' => 'textarea', 'required' => true],
                'emergency_contact' => ['label' => 'Dispatch / emergency contact', 'type' => 'text', 'required' => true],
            ],
            'documents' => [
                'fleet_list' => ['label' => 'Fleet list (make, model, year, plate number)', 'required' => true],
                'vehicle_insurance' => ['label' => 'Vehicle insurance certificates', 'required' => true, 'expires' => true],
                'driver_list' => ['label' => 'Driver list with licence numbers', 'required' => false],
            ],
        ],

        'visa' => [
            'label' => 'Visa Assistance',
            'intro' => 'Visa processing, appointments and document support.',
            'fields' => [
                'countries' => ['label' => 'Countries you process visas for', 'type' => 'textarea', 'required' => true],
                'categories' => ['label' => 'Visa categories', 'type' => 'checkboxes', 'options' => ['Tourist / visit', 'Business', 'Student', 'Work', 'Transit', 'Family / settlement', 'eVisa / visa on arrival']],
                'processing_times' => ['label' => 'Typical processing times', 'type' => 'textarea', 'required' => true],
                'government_fees' => ['label' => 'Government / visa-centre fees', 'type' => 'textarea'],
                'service_charges' => ['label' => 'Your service charges', 'type' => 'textarea', 'required' => true],
                'appointments' => ['label' => 'Do you book appointments / biometrics?', 'type' => 'yesno'],
                'tracking' => ['label' => 'How applicants can track progress', 'type' => 'text'],
                'priority' => ['label' => 'Priority / express services', 'type' => 'textarea'],
                'refusal_policy' => ['label' => 'Refusal / refund policy', 'type' => 'textarea', 'required' => true],
            ],
            'documents' => [
                'visa_accreditation' => ['label' => 'Licence / accreditation (where required)', 'required' => false, 'expires' => true],
                'visa_authorisation' => ['label' => 'Authorisation / agreement (where applicable)', 'required' => false],
            ],
            // Shown as a required tick-box on the form
            'acknowledgement' => 'We understand that visa assistance must never be presented as a guarantee of visa approval.',
        ],

        'insurance' => [
            'label' => 'Travel Insurance',
            'intro' => 'Travel medical, cancellation and baggage cover.',
            'fields' => [
                'underwriter' => ['label' => 'Underwriter', 'type' => 'text', 'required' => true],
                'countries' => ['label' => 'Countries / regions covered', 'type' => 'textarea', 'required' => true],
                'policy_types' => ['label' => 'Policy types', 'type' => 'checkboxes', 'options' => ['Single trip', 'Annual multi-trip', 'Schengen visa', 'Student', 'Family', 'Senior citizen']],
                'cover' => ['label' => 'Cover: medical, cancellation / interruption, baggage, with limits', 'type' => 'textarea', 'required' => true],
                'exclusions' => ['label' => 'Main exclusions', 'type' => 'textarea'],
                'age_limits' => ['label' => 'Age restrictions', 'type' => 'text'],
                'premiums' => ['label' => 'Premiums and commission / net rates', 'type' => 'textarea', 'required' => true],
                'claims' => ['label' => 'Claims process', 'type' => 'textarea', 'required' => true],
                'assistance_line' => ['label' => '24-hour emergency assistance number', 'type' => 'text', 'required' => true],
            ],
            'documents' => [
                'naicom_licence' => ['label' => 'Insurance licence / authorisation (e.g. NAICOM)', 'required' => true, 'expires' => true],
                'underwriter_details' => ['label' => 'Underwriter details', 'required' => true],
                'policy_wording' => ['label' => 'Policy wording', 'required' => true],
                'sample_certificate' => ['label' => 'Sample policy / certificate', 'required' => false],
                'commission_structure' => ['label' => 'Commission structure', 'required' => false],
                'claims_procedure' => ['label' => 'Claims procedure', 'required' => false],
            ],
        ],

        'air_cargo' => [
            'label' => 'Air Cargo / Freight',
            'intro' => 'Documents, parcels and freight, local or international.',
            'fields' => [
                'coverage' => ['label' => 'Origins / destinations covered', 'type' => 'textarea', 'required' => true],
                'cargo_types' => ['label' => 'Cargo types handled', 'type' => 'checkboxes', 'options' => ['Documents', 'Parcels', 'General cargo', 'Perishables', 'Dangerous goods', 'Valuables']],
                'limits' => ['label' => 'Weight / volume limits', 'type' => 'text'],
                'partners' => ['label' => 'Airline / carrier partners', 'type' => 'textarea'],
                'customs' => ['label' => 'Do you handle customs clearance?', 'type' => 'yesno'],
                'transit_times' => ['label' => 'Typical transit times', 'type' => 'textarea', 'required' => true],
                'tracking' => ['label' => 'Shipment tracking', 'type' => 'text'],
                'pickup_delivery' => ['label' => 'Pickup and delivery service', 'type' => 'textarea'],
                'cargo_insurance' => ['label' => 'Cargo insurance offered', 'type' => 'text'],
                'rates' => ['label' => 'Rates and handling charges', 'type' => 'textarea', 'required' => true],
                'claims' => ['label' => 'Claims process for loss or damage', 'type' => 'textarea', 'required' => true],
            ],
            'documents' => [
                'freight_licence' => ['label' => 'Cargo / freight licence or authorisation', 'required' => true, 'expires' => true],
                'iata_cass' => ['label' => 'IATA / CASS accreditation', 'required' => false, 'expires' => true],
                'dangerous_goods' => ['label' => 'Dangerous Goods certification', 'required' => false, 'expires' => true],
                'customs_documents' => ['label' => 'Customs / freight documents', 'required' => false],
                'cargo_insurance_certificate' => ['label' => 'Insurance certificate', 'required' => false, 'expires' => true],
            ],
        ],

        'travel_assist' => [
            'label' => 'Travel Assist',
            'intro' => 'Yellow Card, Extra Luggage, Flight Assist and Visa Confirmation.',
            'fields' => [
                'products' => ['label' => 'Which Travel Assist services can you provide?', 'type' => 'checkboxes', 'options' => ['Yellow Card (vaccination certificate)', 'Extra Luggage', 'Flight Assist', 'Visa Confirmation'], 'required' => true],
                'locations' => ['label' => 'Locations served', 'type' => 'textarea', 'required' => true],
                'turnaround' => ['label' => 'Turnaround times (standard and fast-track)', 'type' => 'textarea', 'required' => true],
                'delivery' => ['label' => 'How results / documents reach the customer', 'type' => 'text'],
                'rates' => ['label' => 'Net rates per service', 'type' => 'textarea', 'required' => true],
                'cancellation' => ['label' => 'Cancellation / refund policy', 'type' => 'textarea', 'required' => true],
            ],
            'documents' => [
                'health_accreditation' => ['label' => 'Port Health / health authority accreditation (Yellow Card)', 'required' => false, 'expires' => true],
            ],
        ],

        'tours' => [
            'label' => 'Tours & Holiday Packages',
            'intro' => 'Tours, excursions and packaged holidays.',
            'fields' => [
                'destinations' => ['label' => 'Destinations', 'type' => 'textarea', 'required' => true],
                'package_types' => ['label' => 'Package types', 'type' => 'checkboxes', 'options' => ['Day tours', 'Multi-day tours', 'Holiday packages', 'Group / corporate trips', 'Pilgrimage', 'Honeymoon']],
                'inclusions' => ['label' => 'What packages typically include', 'type' => 'textarea'],
                'licensed_guides' => ['label' => 'Are your guides licensed?', 'type' => 'yesno'],
                'group_sizes' => ['label' => 'Group sizes', 'type' => 'text'],
                'rates' => ['label' => 'Net / commissionable rates', 'type' => 'textarea', 'required' => true],
                'cancellation' => ['label' => 'Cancellation policy', 'type' => 'textarea', 'required' => true],
                'emergency_contact' => ['label' => 'Emergency contact during tours', 'type' => 'text', 'required' => true],
            ],
            'documents' => [
                'tour_licence' => ['label' => 'Tour operator licence (e.g. NTDC)', 'required' => false, 'expires' => true],
                'sample_itinerary' => ['label' => 'Sample itinerary', 'required' => false],
            ],
        ],
    ],

    'declaration' => 'I/We confirm that the information and documents supplied are accurate, complete and current. I/We agree to comply with applicable laws, service standards, agreed rates, refund/cancellation policies, data-protection requirements and the platform\'s vendor terms. Approval is subject to verification and may be suspended or withdrawn if information becomes inaccurate or compliance requirements are not met.',

    'consent' => 'I/We consent to TravelWheel verifying the information and documents supplied, including checks with registries, references and issuing bodies, and processing the personal data in this form for vendor onboarding in line with the Nigeria Data Protection Act 2023.',
];
