<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_wear_items', function (Blueprint $table): void {
            $table->id();
            $table->string('vehicle_type');
            $table->string('name');
            $table->decimal('percentage', 5, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Starting Tear & Wear items per vehicle type, matching the
        // client-provided example (AC 1.5%, Year 2%, Neatness 1.5% = 5%
        // total). Each vehicle type's list is independently editable from
        // the admin panel afterwards.
        $vehicleTypes = ['saloon', 'suv', 'van', 'bus', 'luxury'];
        $items = [
            ['name' => 'AC', 'percentage' => 1.5, 'sort_order' => 1],
            ['name' => 'Year', 'percentage' => 2.0, 'sort_order' => 2],
            ['name' => 'Neatness', 'percentage' => 1.5, 'sort_order' => 3],
        ];

        $rows = [];
        foreach ($vehicleTypes as $vehicleType) {
            foreach ($items as $item) {
                $rows[] = array_merge($item, [
                    'vehicle_type' => $vehicleType,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        DB::table('transfer_wear_items')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_wear_items');
    }
};
