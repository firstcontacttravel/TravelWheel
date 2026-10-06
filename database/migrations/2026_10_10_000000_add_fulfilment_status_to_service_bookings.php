<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Workflow phase 4: what staff have done, separate from what the customer
     * paid.
     *
     * Every service below kept one status column that checkout wrote the
     * payment result into, and that the admin's "Change status" dropdown then
     * overwrote with fulfilment words ("confirmed", "completed",
     * "cancelled"). A paid car hire marked "completed" no longer said it was
     * paid. Each table now gets fulfilment_status, written only by the
     * admin's workflow actions; the old column goes back to meaning payment.
     *
     * Existing rows are split the same way: fulfilment words found in the
     * payment column move to fulfilment_status, and a "confirmed" or
     * "completed" booking is recorded as paid, which is what reaching that
     * point implied.
     */
    private const PAYMENT_COLUMN_TABLES = [
        'car_hires' => 'paid',
        'transfers' => 'paid',
        'aircargo' => 'successful',
        'support_yellow_cards' => 'paid',
        'support_extra_luggage_requests' => 'paid',
        'support_flight_assists' => 'paid',
        'support_visa_confirmations' => 'paid',
    ];

    /** Tables whose "status" column holds the payment result (created after payment). */
    private const STATUS_COLUMN_TABLES = ['lounge_service', 'protocol_bookings', 'insurance_purchases'];

    public function up(): void
    {
        foreach ($this->tables() as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->string('fulfilment_status', 30)->default('new')->index();
            });
        }

        foreach (array_intersect_key(self::PAYMENT_COLUMN_TABLES, array_flip($this->tables())) as $table => $paid) {
            DB::table($table)->whereRaw('LOWER(payment_status) = ?', ['completed'])
                ->update(['fulfilment_status' => 'completed', 'payment_status' => $paid]);
            DB::table($table)->whereRaw('LOWER(payment_status) = ?', ['confirmed'])
                ->update(['payment_status' => $paid]);
            DB::table($table)->whereRaw('LOWER(payment_status) = ?', ['cancelled'])
                ->update(['fulfilment_status' => 'cancelled']);
        }

        foreach (array_intersect(['car_hires', 'transfers'], $this->tables()) as $table) {
            DB::table($table)->where('driver_assigned', true)->where('fulfilment_status', 'new')
                ->update(['fulfilment_status' => 'driver_assigned']);
        }

        foreach (array_intersect(self::STATUS_COLUMN_TABLES, $this->tables()) as $table) {
            DB::table($table)->whereRaw('LOWER(status) = ?', ['cancelled'])
                ->update(['fulfilment_status' => 'cancelled']);
        }
    }

    public function down(): void
    {
        foreach ($this->tables() as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropIndex(['fulfilment_status']);
                $blueprint->dropColumn('fulfilment_status');
            });
        }
    }

    /**
     * The tables that exist. Some development databases never ran the
     * migrations that create a few of these; production has them all.
     *
     * @return list<string>
     */
    private function tables(): array
    {
        return array_values(array_filter(
            [...array_keys(self::PAYMENT_COLUMN_TABLES), ...self::STATUS_COLUMN_TABLES],
            fn (string $table): bool => Schema::hasTable($table),
        ));
    }
};
