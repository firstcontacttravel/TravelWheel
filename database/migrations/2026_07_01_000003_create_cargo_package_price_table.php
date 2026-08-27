<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cargo_package_price', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('zone_id')->constrained('shipping_zones');
            $table->integer('weight_0_5');
            $table->integer('weight_1_0');
            $table->integer('weight_1_5');
            $table->integer('weight_2_0');
            $table->integer('weight_2_5');
            $table->integer('weight_3_0');
            $table->integer('weight_3_5');
            $table->integer('weight_4_0');
            $table->integer('weight_4_5');
            $table->integer('weight_5_0');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cargo_package_price');
    }
};
