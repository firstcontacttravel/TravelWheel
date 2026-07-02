<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ShippingZone;
use App\Models\CargoPackagePrice;

class CargoPackagePriceSeeder extends Seeder
{
    public function run(): void
    {
        $prices = [
            'Zone 1' => [71000, 72000, 72000, 73000, 93000, 110000, 127000, 144000, 161000, 179000],
            'Zone 2' => [78000, 78000, 79000, 80000, 100000, 117000, 134000, 151000, 168000, 185000],
            'Zone 3' => [85000, 85000, 86000, 87000, 113000, 138000, 162000, 187000, 210000, 236000],
            'Zone 4' => [97000, 98000, 98000, 99000, 126000, 150000, 174000, 195000, 223000, 248000],
            'Zone 5' => [99000, 100000, 101000, 101000, 128000, 153000, 177000, 201000, 226000, 250000],
            'Zone 6' => [106000, 107000, 108000, 108000, 137000, 165000, 193000, 221000, 250000, 278000],
            'Zone 7' => [116000, 117000, 117000, 118000, 146000, 174000, 203000, 231000, 259000, 288000],
            'Zone 8' => [120000, 121000, 122000, 122000, 159000, 194000, 229000, 265000, 300000, 335000],
        ];

        $keys = ['weight_0_5', 'weight_1_0', 'weight_1_5', 'weight_2_0', 'weight_2_5', 'weight_3_0', 'weight_3_5', 'weight_4_0', 'weight_4_5', 'weight_5_0'];

        foreach ($prices as $zoneName => $vals) {
            $zone = ShippingZone::where('zone_name', $zoneName)->first();
            if ($zone) {
                CargoPackagePrice::firstOrCreate(
                    ['zone_id' => $zone->id],
                    array_combine($keys, $vals)
                );
            }
        }
    }
}
