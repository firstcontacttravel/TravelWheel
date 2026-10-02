<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lets admin take a lounge off the public site without deleting it.
     * Every existing lounge starts active.
     */
    public function up(): void
    {
        Schema::table('lounges', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('markup_price');
        });
    }

    public function down(): void
    {
        Schema::table('lounges', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
