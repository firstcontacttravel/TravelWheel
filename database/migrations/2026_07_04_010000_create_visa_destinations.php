<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visa_destinations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('country_visa_destination', function (Blueprint $table) {
            $table->foreignId('visa_destination_id')->constrained()->cascadeOnDelete();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->primary(['visa_destination_id', 'country_id']);
        });

        Schema::table('visa_products', function (Blueprint $table) {
            $table->foreignId('visa_destination_id')->nullable()->after('destination_country_id')->constrained()->nullOnDelete();
            $table->foreignId('destination_country_id')->nullable()->change();
        });

        Schema::table('visa_applications', function (Blueprint $table) {
            $table->foreignId('visa_destination_id')->nullable()->after('destination_country_id')->constrained()->nullOnDelete();
            $table->foreignId('destination_country_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('visa_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('visa_destination_id');
            $table->foreignId('destination_country_id')->nullable(false)->change();
        });

        Schema::table('visa_products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('visa_destination_id');
            $table->foreignId('destination_country_id')->nullable(false)->change();
        });

        Schema::dropIfExists('country_visa_destination');
        Schema::dropIfExists('visa_destinations');
    }
};
