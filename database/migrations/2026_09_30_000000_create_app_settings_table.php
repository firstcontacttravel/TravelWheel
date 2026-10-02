<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Site-wide values admin can change without a deploy (see AppSetting).
     * Seeded with the LoungePair markups that used to be hardcoded in
     * Lounge, so prices don't change when this runs.
     */
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        DB::table('app_settings')->insert([
            ['key' => 'loungepair_markup_nigeria', 'value' => '10000', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'loungepair_markup_international', 'value' => '15000', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
