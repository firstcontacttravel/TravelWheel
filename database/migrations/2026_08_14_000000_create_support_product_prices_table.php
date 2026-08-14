<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_product_prices', function (Blueprint $table) {
            $table->id();
            $table->string('product')->unique();
            $table->string('label');
            $table->unsignedInteger('amount');
            $table->timestamps();
        });

        DB::table('support_product_prices')->insert([
            'product' => 'flight_assist',
            'label' => 'Flight Assist',
            'amount' => 25000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('support_product_prices');
    }
};
