<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ShippingZone;
use App\Models\CargoDocumentPrice;

class CargoDocumentPriceSeeder extends Seeder
{
    public function run(): void
    {
        $prices = [
            'Zone 1' => [66000, 66000, 67000, 68000, 93000, 110000, 127000, 144000, 161000, 179000],
            'Zone 2' => [73000, 74000, 74000, 75000, 100000, 117000, 134000, 151000, 168000, 185000],
            'Zone 3' => [80000, 81000, 82000, 82000, 113000, 138000, 162000, 187000, 210000, 236000],
            'Zone 4' => [92000, 93000, 93000, 94000, 126000, 150000, 174000, 195000, 223000, 248000],
            'Zone 5' => [95000, 95000, 96000, 97000, 128000, 153000, 177000, 201000, 226000, 250000],
            'Zone 6' => [101000, 102000, 103000, 103000, 137000, 165000, 193000, 221000, 250000, 278000],
            'Zone 7' => [111000, 112000, 112000, 113000, 146000, 174000, 203000, 231000, 259000, 288000],
            'Zone 8' => [116000, 117000, 117000, 118000, 159000, 194000, 229000, 265000, 300000, 335000],
        ];

        $keys = ['weight_0_5', 'weight_1_0', 'weight_1_5', 'weight_2_0', 'weight_2_5', 'weight_3_0', 'weight_3_5', 'weight_4_0', 'weight_4_5', 'weight_5_0'];

        foreach ($prices as $zoneName => $vals) {
            $zone = ShippingZone::where('zone_name', $zoneName)->first();
            if ($zone) {
                CargoDocumentPrice::firstOrCreate(
                    ['zone_id' => $zone->id],
                    array_combine($keys, $vals)
                );
            }
        }
    }
}
