<?php

namespace App\Support;

/** The airlines a Protocol customer can pick, by airport type and location. */
class ProtocolAirlines
{
    public const INTERNATIONAL = [
        'Lagos' => ['BRITISH AIRWAYS', 'QATAR AIRWAYS', 'EMIRATES', 'LUFTHANSA', 'TURKISH AIRLINES', 'DELTA AIRLINES', 'VIRGIN ATLANTIC', 'ETHIOPIAN AIRLINES', 'KENYA AIRWAYS', 'AIR PEACE', 'ASKY AIRLINES', 'AIR FRANCE', 'KLM', 'EGYPT AIR', 'ROYAL AIR MAROC', 'TAAG ANGOLA', 'SOUTH AFRICA AIRWAYS', 'UNITED AIRLINES', 'RWANDAIR'],
        'Abuja' => ['BRITISH AIRWAYS', 'LUFTHANSA', 'ETHIOPIAN AIRLINES', 'KLM', 'EMIRATES', 'QATAR AIRWAYS', 'TURKISH AIRLINES', 'AIR FRANCE', 'AIR PEACE', 'ASKY AIRLINES', 'ROYAL AIR MAROC', 'UNITED AIRLINES', 'VIRGIN ATLANTIC', 'DELTA AIRLINES'],
        'Kano' => ['ETHIOPIAN AIRLINES'],
        'Enugu' => ['ETHIOPIAN AIRLINES'],
        'Rivers' => ['QATAR AIRWAYS', 'TURKISH AIRLINES', 'LUFTHANSA'],
    ];

    public const LOCAL = [
        'Lagos' => ['Air Peace', 'Arik', 'Ibom Air', 'Value Jet', 'Overland Airways', 'United Nigeria Airlines', 'Max Air', 'XEjet', 'Aero Contractor', 'Green Africa', 'Rano Air', 'Umza Air'],
        'Abuja' => ['Air Peace', 'Arik', 'Ibom Air', 'Value Jet', 'Overland Airways', 'United Nigeria Airlines', 'Max Air', 'XEjet', 'Aero Contractor', 'Green Africa', 'Rano Air', 'Umza Air'],
        'Enugu' => ['Ibom Air', 'United Nigeria Airlines', 'XEjet'],
        'Rivers' => ['Air Peace', 'Arik', 'Aero Contractor', 'Ibom Air', 'United Nigeria Airlines', 'Value Jet', 'Umza Air'],
        'Kano' => ['Air Peace', 'Rano Air', 'Value Jet', 'Umza Air'],
        'Delta Asaba' => ['Air Peace', 'Arik', 'Aero Contractor', 'United Nigeria Airlines'],
        'Imo' => ['Air Peace', 'United Nigeria Airlines', 'XEjet'],
        'Oyo' => ['Overland Airways'],
        'Kwara' => ['Overland Airways'],
        'Anambra' => ['Air Peace', 'United Nigeria Airlines'],
        'Delta Warri' => ['Air Peace', 'Arik', 'Aero Contractor'],
        'Edo' => ['Air Peace', 'Arik', 'Aero Contractor', 'United Nigeria Airlines'],
        'Gombe' => ['Air Peace'],
        'Borno' => ['Air Peace', 'Umza Air', 'Rano Air'],
        'Adamawa' => ['Air Peace', 'Umza Air'],
        'Sokoto' => ['Umza Air', 'Rano Air'],
        'Kaduna' => ['Rano Air'],
        'Cross River' => ['Ibom Air', 'Value Jet'],
    ];

    /**
     * Local airlines are kept as the website saves them, in capitals.
     *
     * @return list<string>
     */
    public static function for(string $location, bool $international): array
    {
        $map = $international ? self::INTERNATIONAL : self::LOCAL;
        $key = collect(array_keys($map))->first(fn (string $name) => strcasecmp($name, trim($location)) === 0);
        $airlines = $key ? $map[$key] : [];

        return $international ? $airlines : array_map('strtoupper', $airlines);
    }
}
