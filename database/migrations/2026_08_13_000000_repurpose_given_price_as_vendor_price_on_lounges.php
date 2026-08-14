<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lounges', function (Blueprint $table) {
            $table->double('markup_price', 10, 2)->default(0)->after('given_PriceC');
        });

        // given_PriceA/B/C already exist but hold stale, unused data. Repurpose them
        // as the vendor price by backfilling from the current totals (100% vendor
        // cost, ₦0 markup) so public pricing doesn't change until an admin enters
        // the real vendor cost per tier.
        DB::table('lounges')->update([
            'given_PriceA' => DB::raw('priceA'),
            'given_PriceB' => DB::raw('priceB'),
            'given_PriceC' => DB::raw('priceC'),
            'markup_price' => 0,
        ]);

        // priceA/B/C are no longer stored — the public total is now computed on the
        // fly by the Lounge model as given_Price + markup_price.
        Schema::table('lounges', function (Blueprint $table) {
            $table->dropColumn(['priceA', 'priceB', 'priceC']);
        });
    }

    public function down(): void
    {
        Schema::table('lounges', function (Blueprint $table) {
            $table->double('priceA', 10, 2)->default(0)->after('given_PriceC');
            $table->double('priceB', 10, 2)->default(0)->after('priceA');
            $table->double('priceC', 10, 2)->default(0)->after('priceB');
        });

        DB::table('lounges')->update([
            'priceA' => DB::raw('given_PriceA + markup_price'),
            'priceB' => DB::raw('given_PriceB + markup_price'),
            'priceC' => DB::raw('given_PriceC + markup_price'),
        ]);

        Schema::table('lounges', function (Blueprint $table) {
            $table->dropColumn('markup_price');
        });
    }
};
