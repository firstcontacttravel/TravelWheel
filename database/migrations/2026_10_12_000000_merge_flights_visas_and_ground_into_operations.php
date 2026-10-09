<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Flights, Visas and Ground & Airport Services become one department:
     * Operations. One team works every booking; Finance, Customer Support and
     * IT stay as they are.
     *
     * The Flights department becomes Operations in place (keeping its id), and
     * everything that pointed at Visas or Ground & Airport is moved onto it:
     * staff, work item queues, escalations, and the department recorded on
     * past activity. The two emptied departments are then removed. Nothing
     * that happened is lost; it just reads "Operations" from now on.
     */
    private const MERGED = ['visas', 'ground-airport'];

    public function up(): void
    {
        $operations = DB::table('departments')->where('slug', 'operations')->value('id')
            ?? DB::table('departments')->where('slug', 'flights')->value('id');

        if (! $operations) {
            $operations = DB::table('departments')->insertGetId([
                'name' => 'Operations', 'slug' => 'operations', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        DB::table('departments')->where('id', $operations)->update([
            'name' => 'Operations',
            'slug' => 'operations',
            'description' => 'Flights, visas, car hire, transfers, lounges, protocol and air cargo: every booking from payment to delivery.',
            'linear_team' => 'travelwheel',
            'linear_label' => 'Operations',
            'updated_at' => now(),
        ]);

        $merged = DB::table('departments')->whereIn('slug', self::MERGED)->pluck('id');

        if ($merged->isNotEmpty()) {
            DB::table('users')->whereIn('department_id', $merged)->update(['department_id' => $operations]);
            DB::table('work_items')->whereIn('department_id', $merged)->update(['department_id' => $operations]);
            DB::table('escalations')->whereIn('to_department_id', $merged)->update(['to_department_id' => $operations]);
            DB::table('activity_logs')->whereIn('department_id', $merged)->update(['department_id' => $operations]);
            DB::table('departments')->whereIn('id', $merged)->delete();
        }
    }

    /**
     * The departments come back, empty. Who belonged to which of the three
     * was not kept, so staff and queues stay with Operations (renamed back to
     * Flights) and have to be reassigned by hand.
     */
    public function down(): void
    {
        DB::table('departments')->where('slug', 'operations')->update([
            'name' => 'Flights', 'slug' => 'flights', 'linear_label' => 'Flights',
            'description' => 'Flight bookings, ticketing, reissues and the flight APIs.', 'updated_at' => now(),
        ]);

        foreach ([
            ['Visas', 'visas', 'Visas', 'Visa applications and visa confirmations.'],
            ['Ground & Airport Services', 'ground-airport', 'Ground & Airport', 'Car hire, transfers, lounges, protocol and air cargo.'],
        ] as [$name, $slug, $label, $description]) {
            DB::table('departments')->updateOrInsert(['slug' => $slug], [
                'name' => $name, 'linear_team' => 'travelwheel', 'linear_label' => $label,
                'description' => $description, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }
};
