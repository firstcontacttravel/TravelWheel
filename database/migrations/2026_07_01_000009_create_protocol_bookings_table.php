<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('protocol_bookings', function (Blueprint $table): void {
            $table->id();
            $table->string('paymentoption');
            $table->longText('fullname');
            $table->string('package');
            $table->string('service');
            $table->integer('passenger');
            $table->string('email');
            $table->string('phone');
            $table->date('travel_date');
            $table->string('state');
            $table->string('airline');
            $table->string('airport');
            $table->string('d_time');
            $table->string('service_type');
            $table->string('status')->default('Pending');
            $table->decimal('amount', 12, 2);
            $table->decimal('vat', 10, 2)->default(0);
            $table->text('optional_request')->nullable();
            $table->string('optionalRequestOption')->nullable();
            $table->text('optionalRequestAddress')->nullable();
            $table->longText('reservationCode')->nullable();
            $table->longText('eTicketNo')->nullable();
            $table->longText('noOfBags')->nullable();
            $table->string('nextOfKin_fullname')->nullable();
            $table->string('nextOfKin_phone')->nullable();
            $table->string('means_id')->nullable();
            $table->string('trans_id')->unique();
            $table->string('ref_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('protocol_bookings');
    }
};
