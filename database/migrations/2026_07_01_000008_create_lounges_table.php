<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Historical shape, before 2026_07_07 (adds `service`) and 2026_08_13
        // (adds `markup_price`, drops priceA/B/C) alter this table.
        Schema::create('lounges', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('lounge_id', 50);
            $table->string('brand_name', 50);
            $table->string('email', 100);
            $table->string('phone_no', 50);
            $table->string('location', 50);
            $table->string('airport', 50);
            $table->string('terminal', 50);
            $table->text('description');
            $table->text('facilities1');
            $table->text('facilities2');
            $table->text('facilities3');
            $table->text('facilities4');
            $table->text('facilities5');
            $table->double('given_PriceA', 10, 2)->nullable();
            $table->double('given_PriceB', 10, 2)->nullable();
            $table->double('given_PriceC', 10, 2)->nullable();
            $table->double('priceA', 10, 2)->default(0);
            $table->double('priceB', 10, 2)->default(0);
            $table->double('priceC', 10, 2)->default(0);
            $table->string('pics1', 250);
            $table->string('pics2', 250);
            $table->string('pics3', 250);
            $table->string('pics4', 250);
            $table->string('pics5', 250);
            $table->timestamp('created_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lounges');
    }
};
