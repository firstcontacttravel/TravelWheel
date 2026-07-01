<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Intl\Countries;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        foreach (Countries::getNames('en') as $alpha2 => $name) {
            $values = [
                'alpha3' => Countries::getAlpha3Code($alpha2),
                'name' => $name,
                'is_active' => true,
            ];

            if (Schema::hasColumn('countries', 'code')) {
                $values['code'] = strtoupper($alpha2);
            }

            Country::query()->updateOrCreate(
                ['alpha2' => strtoupper($alpha2)],
                $values,
            );
        }
    }
}
