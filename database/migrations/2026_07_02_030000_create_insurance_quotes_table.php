<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_quotes', function (Blueprint $table) {
            $table->id();
            $table->string('quoteRequestId')->nullable();
            $table->string('productVariantId')->nullable();
            $table->string('dob')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_no')->nullable();
            $table->string('coverBegins')->nullable();
            $table->string('coverEnds')->nullable();
            $table->string('countryId')->nullable();
            $table->string('countryId2')->nullable();
            $table->string('purposeOfTravel')->nullable();
            $table->string('travelPlanId')->nullable();
            $table->string('bookingTypeId')->nullable();
            $table->string('noOfPeople')->nullable();
            $table->string('noOfChildren')->nullable();
            $table->boolean('multiTrip')->default(false);
            $table->decimal('amount', 15, 2)->nullable();
            $table->decimal('amountA', 15, 2)->nullable();
            $table->string('quoteId')->nullable();
            $table->string('requestdate')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_quotes');
    }
};
