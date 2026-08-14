<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('protocols', function (Blueprint $table) {
            $table->double('markup_price', 10, 2)->default(0)->after('Given_Price2');
        });

        // Given_Price1/2 already exist but hold stale, unused data. Repurpose them
        // as the vendor price by backfilling from the current totals (100% vendor
        // cost, ₦0 markup) so public pricing doesn't change until an admin enters
        // the real vendor cost per plan.
        DB::table('protocols')->update([
            'Given_Price1' => DB::raw('CAST(price1 AS DECIMAL(10,2))'),
            'Given_Price2' => DB::raw('CAST(price2 AS DECIMAL(10,2))'),
            'markup_price' => 0,
        ]);

        // price1/price2 are no longer stored — the public total is now computed on
        // the fly by the Protocol model as Given_Price + markup_price.
        Schema::table('protocols', function (Blueprint $table) {
            $table->dropColumn(['price1', 'price2']);
        });
    }

    public function down(): void
    {
        Schema::table('protocols', function (Blueprint $table) {
            $table->string('price1', 50)->default('0')->after('Given_Price2');
            $table->string('price2', 50)->default('0')->after('price1');
        });

        DB::table('protocols')->update([
            'price1' => DB::raw('CAST(Given_Price1 + markup_price AS DECIMAL(10,2))'),
            'price2' => DB::raw('CAST(Given_Price2 + markup_price AS DECIMAL(10,2))'),
        ]);

        Schema::table('protocols', function (Blueprint $table) {
            $table->dropColumn('markup_price');
        });
    }
};
