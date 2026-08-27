<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voa_requirements', function (Blueprint $table): void {
            $table->increments('id');
            $table->enum('requirement_type', ['company', 'minor_nigerian', 'minor_foreign', 'individual']);
            $table->string('requirement_name');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voa_requirements');
    }
};
