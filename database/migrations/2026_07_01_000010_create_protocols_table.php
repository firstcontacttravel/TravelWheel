<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Historical shape, before 2026_08_13 (adds `markup_price`, drops
        // price1/price2) alters this table. `id` has no primary key / auto-increment
        // on the live table — mirrored as-is rather than "fixed" here.
        Schema::create('protocols', function (Blueprint $table): void {
            $table->integer('id');
            $table->string('Company', 225)->nullable();
            $table->string('email', 225)->nullable();
            $table->string('phone_no', 225)->nullable();
            $table->string('location', 225);
            $table->string('airport', 225);
            $table->string('service', 50);
            $table->double('Given_Price1', 10, 2)->nullable();
            $table->double('Given_Price2', 10, 2)->nullable();
            $table->string('price1', 50)->default('0');
            $table->string('price2', 50)->default('0');
            $table->timestamp('created_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('protocols');
    }
};
