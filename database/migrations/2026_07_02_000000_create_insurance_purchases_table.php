<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_purchases', function (Blueprint $table): void {
            $table->id();
            $table->string('trans_id')->nullable()->index();
            $table->string('ref_id')->nullable();
            $table->string('qoute_id')->nullable();
            $table->text('cover_id')->nullable();
            $table->string('bookingtype_id')->nullable();
            $table->decimal('c_amount', 15, 2)->nullable();
            $table->decimal('vat', 15, 2)->nullable();
            $table->decimal('t_amount', 15, 2)->nullable();
            $table->string('payment_option')->nullable();
            $table->string('surname')->nullable();
            $table->string('middlename')->nullable();
            $table->string('firstname')->nullable();
            $table->string('gender')->nullable();
            $table->string('title')->nullable();
            $table->string('dob')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_no')->nullable();
            $table->string('state')->nullable();
            $table->string('address')->nullable();
            $table->string('zipcode')->nullable();
            $table->string('passport_no')->nullable();
            $table->string('occupation')->nullable();
            $table->string('nationalty')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('noc')->nullable();
            $table->string('medicalCondition')->nullable();
            $table->string('nok_fullname')->nullable();
            $table->string('nok_address')->nullable();
            $table->string('nok_phone')->nullable();
            $table->string('nok_relationship')->nullable();
            $table->string('status')->default('Successful');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_purchases');
    }
};
