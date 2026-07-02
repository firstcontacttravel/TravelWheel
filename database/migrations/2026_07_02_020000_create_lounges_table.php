<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lounges', function (Blueprint $table) {
            $table->id();
            $table->string('lounge_id')->nullable();
            $table->string('brand_name');
            $table->string('email')->nullable();
            $table->string('phone_no')->nullable();
            $table->string('location'); // Lagos / Abuja / Kano
            $table->tinyInteger('airport'); // 1 = International, 2 = Local
            $table->string('terminal')->nullable(); // New Terminal / Old Terminal (Lagos only)
            $table->text('description')->nullable();
            $table->string('facilities1')->nullable();
            $table->string('facilities2')->nullable();
            $table->string('facilities3')->nullable();
            $table->string('facilities4')->nullable();
            $table->string('facilities5')->nullable();
            $table->decimal('given_PriceA', 12, 2)->nullable(); // given (cost) price for adults
            $table->decimal('given_PriceB', 12, 2)->nullable(); // given price for children
            $table->decimal('given_PriceC', 12, 2)->nullable(); // given price for infants
            $table->decimal('priceA', 12, 2)->nullable(); // selling price for adults
            $table->decimal('priceB', 12, 2)->nullable(); // selling price for children
            $table->decimal('priceC', 12, 2)->nullable(); // selling price for infants
            $table->string('pics1')->nullable();
            $table->string('pics2')->nullable();
            $table->string('pics3')->nullable();
            $table->string('pics4')->nullable();
            $table->string('pics5')->nullable();
            $table->timestamps();
        });

        Schema::create('lounge_service', function (Blueprint $table) {
            $table->id();
            $table->string('lounge_name');
            $table->string('payment_option');
            $table->string('fullname');
            $table->string('service')->default('Lounge Service');
            $table->string('email');
            $table->string('phone_no');
            $table->string('terminal')->nullable();
            $table->integer('nop'); // total number of persons
            $table->integer('noa')->default(0); // adults
            $table->integer('noc')->default(0); // children
            $table->integer('noi')->default(0); // infants
            $table->date('travel_date');
            $table->string('airline');
            $table->string('d_time');
            $table->decimal('amount', 12, 2);
            $table->decimal('amountA', 12, 2)->nullable();
            $table->decimal('amountC', 12, 2)->nullable();
            $table->decimal('vat', 10, 2)->default(0);
            $table->string('status')->default('Pending');
            $table->string('trans_id')->unique();
            $table->string('ref_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lounge_service');
        Schema::dropIfExists('lounges');
    }
};
