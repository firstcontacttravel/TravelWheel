<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Legacy static lookup table: `id` is a primary key but not auto-incrementing
        // on the live table (rows are seeded with explicit IDs) — mirrored as-is.
        Schema::create('voa', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('country', 100);
            $table->decimal('single_entry_fee', 10, 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voa');
    }
};
