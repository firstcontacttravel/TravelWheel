<?php

namespace App\Support;

/**
 * Where in Lagos a cargo shipment can be picked up, by nearest bus stop or
 * area, and what the pick-up costs there.
 */
class CargoPickupAreas
{
    public const ZONES = [
        'Mainland 1' => ['price' => 4500, 'areas' => ['Abule oja', 'Alagomeji', 'Ebute metta', 'Folagoro', 'Idiaraba', 'Iponri', 'Itire', 'Iwaya', 'Lawanson', 'Ojuelegba', 'Oyingbo', 'Oshodi']],
        'Mainland 2' => ['price' => 4000, 'areas' => ['Magodo', 'Alapere', 'Bariga', 'Ilupeju', 'Jibowu', 'Ikeja', 'Ogudu', 'Onipanu', 'Obanikoro', 'Somolu', 'Palmgroove', 'Yaba', 'Ajao', 'Maryland', 'Ketu', 'Mile12', 'Anthony', 'Shangisha', 'Ojota']],
        'Mainland 3' => ['price' => 4500, 'areas' => ['Abule-egba', 'Agege', 'Berger', 'Iju ishaga', 'Fagba', 'Akute', 'Magodo phase2', 'Omole']],
        'Mainland 4' => ['price' => 6000, 'areas' => ['Egbeda', 'Gowon', 'Ayobo', 'Ipaja', 'Igando', 'Ikotun', 'Iyana Ipaja', 'Okota', 'Ago palace', 'Ijaye', 'Dopemu', 'Meran']],
        'Mainland 5' => ['price' => 2500, 'areas' => ['Agric Ikorodu', 'Ajegunle ikorodu', 'Asolo', 'Owede oniri', 'Ishawo', 'Ogolonto']],
        'Mainland 6' => ['price' => 2500, 'areas' => ['Ikorodu garage', 'Sabo', 'Jubilee', 'laspotech', 'Ori okuta', 'ojokoro']],
        'Mainland 7' => ['price' => 4000, 'areas' => ['Lucky fiber', 'Imota', 'Odogunyan', 'Gberigbe', 'Ogijo', 'Caleb']],
        'Mainland 8' => ['price' => 6000, 'areas' => ['Festac', 'Apapa', 'Ajegunle', 'Satellite', 'Ijegun']],
        'Mainland 9' => ['price' => 6000, 'areas' => ['Lasu Gate', 'Iyana iba', 'Iyana Ishashi', 'Okoko', 'Ojo']],
        'Island 1' => ['price' => 6000, 'areas' => ['Lagos island', 'Cms', 'Marina', 'Idumota']],
        'Island 2' => ['price' => 6000, 'areas' => ['Eko Hotel', 'VI', 'Bonny camp', '1004', 'Oniru']],
        'Island 3' => ['price' => 7000, 'areas' => ['Lekki phase 1', 'Igbo efun', 'Ikate', 'Maruwa']],
        'Island 4' => ['price' => 7000, 'areas' => ['Ajah', 'Badore', 'Ogombo', 'Abraham adesanya', 'Ado', 'Langbasa']],
        'Island 5' => ['price' => 8000, 'areas' => ['Awoyaya', 'Sangotedo', 'Ocean bay']],
    ];

    /** The pick-up price for an area, matched as the website does: the whole name, any case. */
    public static function price(?string $area): ?int
    {
        foreach (self::ZONES as $zone) {
            foreach ($zone['areas'] as $name) {
                if (strcasecmp(trim((string) $area), $name) === 0) {
                    return $zone['price'];
                }
            }
        }

        return null;
    }

    /** @return list<string> */
    public static function areas(): array
    {
        return array_merge(...array_column(self::ZONES, 'areas'));
    }
}
