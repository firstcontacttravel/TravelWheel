<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lounge_service', function (Blueprint $table): void {
            $table->id();
            $table->string('lounge_name');
            $table->string('payment_option');
            $table->string('fullname');
            $table->string('service')->default('Lounge Service');
            $table->string('email');
            $table->string('phone_no');
            $table->string('terminal')->nullable();
            $table->integer('nop');
            $table->integer('noa')->default(0);
            $table->integer('noc')->default(0);
            $table->integer('noi')->default(0);
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
    }
};
