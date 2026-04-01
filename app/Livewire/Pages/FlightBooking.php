<?php

namespace App\Livewire\Pages;

use Livewire\Component;

class FlightBooking extends Component
{
    // ── Flight data ───────────────────────────────────────────────────────────
    public string $sessionId    = '';
    public array  $searchParams = [];

    public array  $bookingFlight   = [];
    public string $searchSessionId = '';

    public string $bookingSessionId = '';
    public array  $bookingSearchParams = [];    
    public array  $extraServices = [];
    public array  $fareRules = [];
    public string $tripType = '';

    // ── Contact ───────────────────────────────────────────────────────────────
    public string $contactEmail        = '';
    public string $contactEmailConfirm = '';
    public string $contactPhone        = '';
    public string $contactAreaCode     = '';   // e.g. 080 (required by booking API)
    public string $contactCountryCode  = '';   // e.g. 234 (required by booking API)

    // ── Passengers ────────────────────────────────────────────────────────────
    public array $passengers = [];

    // ── Passenger counts ──────────────────────────────────────────────────────
    public int $adultCount  = 1;
    public int $childCount  = 0;
    public int $infantCount = 0;

    // ── UI state ──────────────────────────────────────────────────────────────
    public int  $step       = 1;
    public bool $submitting = false;

    // ── Nationalities (country code → name) ───────────────────────────────────
    public array $nationalities = [
        'NG' => 'Nigerian',      'GB' => 'British',       'US' => 'American',
        'GH' => 'Ghanaian',      'ZA' => 'South African', 'KE' => 'Kenyan',
        'CM' => 'Cameroonian',   'CI' => 'Ivorian',       'SN' => 'Senegalese',
        'ET' => 'Ethiopian',     'EG' => 'Egyptian',      'MA' => 'Moroccan',
        'FR' => 'French',        'DE' => 'German',        'IT' => 'Italian',
        'ES' => 'Spanish',       'PT' => 'Portuguese',    'NL' => 'Dutch',
        'AE' => 'Emirati',       'SA' => 'Saudi Arabian', 'QA' => 'Qatari',
        'IN' => 'Indian',        'CN' => 'Chinese',       'JP' => 'Japanese',
        'CA' => 'Canadian',      'AU' => 'Australian',    'BR' => 'Brazilian',
        'MX' => 'Mexican',       'RU' => 'Russian',       'TR' => 'Turkish',
        'ZW' => 'Zimbabwean',    'TZ' => 'Tanzanian',     'UG' => 'Ugandan',
    ];

    // ── Tax code labels ───────────────────────────────────────────────────────
    public array $taxLabels = [
        'QT'         => 'Airport Tax',
        'TE5'        => 'Ticket Levy',
        'W3'         => 'Security Surcharge',
        'W32'        => 'Security Surcharge',
        'MA'         => 'Miscellaneous Fee',
        'MAC'        => 'Miscellaneous Fee',
        'NG3'        => 'Nigeria Passenger Levy',
        'YQF'        => 'Fuel Surcharge',
        'YQI'        => 'Fuel Surcharge',
        'YRI'        => 'Carrier Surcharge',
        'YRF'        => 'Carrier Surcharge',
        'GB'         => 'Air Passenger Duty',
        'UB'         => 'Passenger Service Charge',
        'DE'         => 'Departure Tax',
        'BE'         => 'Booking Fee',
        'RA2'        => 'Regulatory Fee',
        'W42'        => 'Passenger Facility Charge',
        'CH'         => 'Customs & Immigration',
        'CJ'         => 'Customs Fee',
        'EQ'         => 'Equipment Fee',
        'F62'        => 'Facility Charge',
        'F7'         => 'Facility Charge',
        'FR7'        => 'Facility Charge',
        'G4'         => 'Government Tax',
        'L3'         => 'Landing Fee',
        'M6'         => 'Miscellaneous Tax',
        'O2'         => 'Airport Operations Fee',
        'O9'         => 'Other Charge',
        'PZ2'        => 'Passenger Zone Charge',
        'QA'         => 'Passenger Service Fee',
        'QX'         => 'Airport Development Fee',
        'R9'         => 'Revenue Tax',
        'RN'         => 'Regional Tax',
        'S2'         => 'Safety Charge',
        'S4'         => 'Security Fee',
        'S42'        => 'Security Fee',
        'TR'         => 'Ticket Tax',
        'ZR2'        => 'Zone Charge',
        'OtherTaxes' => 'Taxes & Fees',
    ];

    // ─────────────────────────────────────────────────────────────────────────
   public function mount(): void
    {
        $this->sessionId    = session('bookingSessionId', '');
        $this->searchParams = session('bookingSearchParams', []);
 
        $this->adultCount  = (int) ($this->searchParams['adults'] ?? 1);
        $this->childCount  = (int) ($this->searchParams['childs'] ?? 0);
        $this->infantCount = (int) ($this->searchParams['kids']   ?? 0);
 
        $this->bookingFlight        = session('bookingFlight', []);
        $this->bookingSearchParams  = session('bookingSearchParams', []);
        $this->bookingSessionId     = session('bookingSessionId', '');
        $this->extraServices        = session('extraServices', []);
        $this->fareRules            = session('fareRules', []);
        $this->tripType             = session('tripType', '');
 
        $this->contactAreaCode    = '080';
        $this->contactCountryCode = '234';
 
        $this->_rebuildPassengers();
    }
 
    private function getFlight(): array
    {
        return session('bookingFlight', []);
    }
 
    private function _rebuildPassengers(): void
    {
        $existing = collect($this->passengers)->groupBy('type');
        $this->passengers = [];
 
        for ($i = 0; $i < $this->adultCount; $i++) {
            $prev = $existing->get('ADT', collect([]))->values()->get($i);
            $this->passengers[] = $prev
                ? array_merge($this->_emptyPassenger('ADT', $i === 0), (array) $prev)
                : $this->_emptyPassenger('ADT', $i === 0);
        }
        for ($i = 0; $i < $this->childCount; $i++) {
            $prev = $existing->get('CHD', collect([]))->values()->get($i);
            $this->passengers[] = $prev
                ? array_merge($this->_emptyPassenger('CHD'), (array) $prev)
                : $this->_emptyPassenger('CHD');
        }
        for ($i = 0; $i < $this->infantCount; $i++) {
            $prev = $existing->get('INF', collect([]))->values()->get($i);
            $this->passengers[] = $prev
                ? array_merge($this->_emptyPassenger('INF'), (array) $prev)
                : $this->_emptyPassenger('INF');
        }
    }
 
    private function _emptyPassenger(string $type, bool $isPrimary = false): array
    {
        return [
            'type'                   => $type,
            'title'                  => '',
            'first_name'             => '',
            'middle_name'            => '',
            'last_name'              => '',
            'gender'                 => '',
            'dob'                    => '',
            'nationality'            => 'NG',
            // Passport
            'passport_no'            => '',
            'passport_issue_country' => 'NG',
            'passport_issue_date'    => '',
            'passport_exp'           => '',
            // ── NEW: Frequent Flyer ───────────────────────────────────────────
            'frequent_flyer_number'  => '',   // optional — airline loyalty number
            // UI helpers
            'is_primary'             => $isPrimary,
        ];
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // Passenger count controls
    // ─────────────────────────────────────────────────────────────────────────
    public function incrementPassenger(string $type): void
    {
        if ($this->getTotalPassengers() >= 9) return;
        match ($type) {
            'ADT'   => $this->adultCount++,
            'CHD'   => $this->childCount++,
            'INF'   => $this->infantCount++,
            default => null,
        };
        $this->_rebuildPassengers();
    }
 
    public function decrementPassenger(string $type): void
    {
        match ($type) {
            'ADT'   => $this->adultCount  = max(1, $this->adultCount - 1),
            'CHD'   => $this->childCount  = max(0, $this->childCount  - 1),
            'INF'   => $this->infantCount = max(0, $this->infantCount - 1),
            default => null,
        };
        $this->_rebuildPassengers();
    }
 
    public function getTotalPassengers(): int
    {
        return $this->adultCount + $this->childCount + $this->infantCount;
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // Computed fare
    // ─────────────────────────────────────────────────────────────────────────
    public function getComputedFare(): array
    {
        $flight    = $this->getFlight();
        $breakdown = $flight['fareBreakdown'] ?? [];
        $currency  = $flight['currency'] ?? 'NGN';
 
        $rates = [];
        foreach ($breakdown as $fb) {
            $type = $fb['passengerType'] ?? 'ADT';
            $rates[$type] = [
                'baseFare'      => (float) ($fb['baseFare']      ?? 0),
                'totalFare'     => (float) ($fb['totalFare']     ?? 0),
                'taxes'         => $fb['taxes']         ?? [],
                'baggage'       => $fb['baggage']       ?? [],
                'cabinBaggage'  => $fb['cabinBaggage']  ?? [],
                'changeAllowed' => $fb['changeAllowed'] ?? false,
                'changePenalty' => $fb['changePenalty'] ?? '0.00',
                'refundAllowed' => $fb['refundAllowed'] ?? false,
                'refundPenalty' => $fb['refundPenalty'] ?? '0.00',
            ];
        }
 
        $adultRate = $rates['ADT'] ?? [
            'baseFare' => 0, 'totalFare' => 0, 'taxes' => [],
            'baggage' => [], 'cabinBaggage' => [], 'changeAllowed' => false,
            'changePenalty' => '0.00', 'refundAllowed' => false, 'refundPenalty' => '0.00',
        ];
 
        $rows = []; $grand = 0.0;
 
        foreach (['ADT' => $this->adultCount, 'CHD' => $this->childCount, 'INF' => $this->infantCount] as $type => $qty) {
            if ($qty <= 0) continue;
            $rate    = $rates[$type] ?? $adultRate;
            $perPax  = (float) $rate['totalFare'];
            $perBase = (float) $rate['baseFare'];
            $perTax  = $perPax - $perBase;
            $sub     = $perPax * $qty;
            $grand  += $sub;
 
            $rows[] = [
                'passengerType' => $type,
                'label'         => match($type) { 'ADT' => 'Adult', 'CHD' => 'Child', 'INF' => 'Infant', default => 'Passenger' },
                'qty'           => $qty,
                'perBase'       => $perBase,
                'perTax'        => $perTax,
                'perTotal'      => $perPax,
                'subtotalBase'  => $perBase * $qty,
                'subtotalTax'   => $perTax  * $qty,
                'subtotal'      => $sub,
                'taxes'         => $rate['taxes'],
                'baggage'       => $rate['baggage'],
                'cabinBaggage'  => $rate['cabinBaggage'],
                'changeAllowed' => $rate['changeAllowed'],
                'changePenalty' => $rate['changePenalty'],
                'refundAllowed' => $rate['refundAllowed'],
                'refundPenalty' => $rate['refundPenalty'],
            ];
        }
 
        return ['rows' => $rows, 'total' => $grand, 'currency' => $currency];
    }
 
    public function getTotalPrice(): float
    {
        return $this->getComputedFare()['total'];
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // Validation
    // ─────────────────────────────────────────────────────────────────────────
    protected function rules(): array
    {
        $rules = [
            'contactEmail'        => 'required|email',
            'contactEmailConfirm' => 'required|same:contactEmail',
            'contactPhone'        => 'required|string|min:7|max:20',
            'contactAreaCode'     => 'required|string|max:10',
            'contactCountryCode'  => 'required|string|max:10',
        ];
 
        foreach ($this->passengers as $i => $_) {
            $rules["passengers.{$i}.title"]                  = 'required|in:Mr,Mrs,Ms,Miss,Dr,Master';
            $rules["passengers.{$i}.first_name"]             = 'required|string|min:2|max:100';
            $rules["passengers.{$i}.last_name"]              = 'required|string|min:2|max:100';
            $rules["passengers.{$i}.gender"]                 = 'required|in:M,F';
            $rules["passengers.{$i}.dob"]                    = 'required|date|before:today';
            $rules["passengers.{$i}.nationality"]            = 'required|string|size:2';
            $rules["passengers.{$i}.passport_no"]            = 'nullable|string|max:20';
            $rules["passengers.{$i}.passport_issue_country"] = 'nullable|string|size:2';
            $rules["passengers.{$i}.passport_issue_date"]    = 'nullable|date|before:today';
            $rules["passengers.{$i}.passport_exp"]           = 'nullable|date|after:today';
            $rules["passengers.{$i}.frequent_flyer_number"]  = 'nullable|string|max:30';
        }
 
        return $rules;
    }
 
    protected function messages(): array
    {
        return [
            'contactEmailConfirm.same'                 => 'Email addresses do not match.',
            'passengers.*.title.required'              => 'Please select a title.',
            'passengers.*.first_name.required'         => 'First name is required.',
            'passengers.*.last_name.required'          => 'Last name is required.',
            'passengers.*.gender.required'             => 'Please select a gender.',
            'passengers.*.dob.required'                => 'Date of birth is required.',
            'passengers.*.dob.before'                  => 'Date of birth must be in the past.',
            'passengers.*.passport_exp.after'          => 'Passport must not be expired.',
            'passengers.*.passport_issue_date.before'  => 'Passport issue date must be in the past.',
        ];
    }
 
    public function proceed(): void
    {
        $this->validate();
        $this->step = 2;
        $this->dispatch('scrollTop');
    }
 
    public function back(): void
    {
        $this->step = 1;
        $this->dispatch('scrollTop');
    }
 
    public function render()
    {
        return view('livewire.pages.flight.flight-booking', [
            'bookingFlight'       => $this->bookingFlight,
            'bookingSessionId'    => $this->bookingSessionId,
            'bookingSearchParams' => $this->bookingSearchParams,
            'extraServices'       => $this->extraServices,
            'fareRules'           => $this->fareRules,
            'tripType'            => $this->tripType,
        ])->layout('layouts.app', ['title' => 'Complete Your Booking']);
    }
}