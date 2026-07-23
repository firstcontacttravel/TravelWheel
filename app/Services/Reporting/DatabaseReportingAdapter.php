<?php

namespace App\Services\Reporting;

use App\Contracts\ReportingSourceAdapter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\LazyCollection;

class DatabaseReportingAdapter implements ReportingSourceAdapter
{
    public function __construct(
        private readonly string $table,
        private readonly string $type,
        private readonly \Closure $mapper,
    ) {}

    public function sourceType(): string
    {
        return $this->type;
    }

    public function available(): bool
    {
        return Schema::hasTable($this->table);
    }

    public function facts(): LazyCollection
    {
        if (! $this->available()) {
            return LazyCollection::make([]);
        }

        return DB::table($this->table)
            ->orderBy('id')
            ->lazyById(500)
            ->map(fn (object $row): array => ($this->mapper)($row, $this));
    }

    public function fact(object $row, array $data): array
    {
        $gross = (float) ($data['gross_value'] ?? 0);
        $revenue = (float) ($data['travelwheel_revenue'] ?? 0);
        $supplier = array_key_exists('supplier_cost', $data) && $data['supplier_cost'] !== null
            ? (float) $data['supplier_cost']
            : null;

        $fact = array_merge([
            'source_type' => $this->type,
            'source_id' => (int) $row->id,
            'sub_product' => null,
            'reference' => null,
            'customer_hash' => null,
            'currency' => 'NGN',
            'gross_value' => $gross,
            'verified_collections' => 0,
            'travelwheel_revenue' => $revenue,
            'supplier_cost' => $supplier,
            'tax_amount' => null,
            'gross_profit' => $supplier === null ? null : $revenue - $supplier,
            'financially_additive' => true,
            'payment_status' => 'unknown',
            'fulfillment_status' => 'unknown',
            'payment_method' => null,
            'payment_gateway' => null,
            'provider' => null,
            'quantity' => 1,
            'created_at_source' => $this->date($row->created_at ?? now()) ?? now(),
            'paid_at' => null,
            'service_at' => null,
            'completed_at' => null,
            'dimensions' => [],
            'data_quality' => [],
            'last_synced_at' => now(),
        ], $data);

        foreach (['gross_value', 'verified_collections', 'travelwheel_revenue', 'quantity'] as $field) {
            $fact[$field] = $this->number($fact[$field]) ?? 0;
        }
        foreach (['supplier_cost', 'tax_amount', 'gross_profit'] as $field) {
            $fact[$field] = $this->number($fact[$field]);
        }

        return $fact;
    }

    public function customer(?string $email, ?string $phone = null): ?string
    {
        $identity = strtolower(trim((string) ($email ?: $phone)));

        return $identity === '' ? null : hash_hmac('sha256', $identity, (string) config('app.key'));
    }

    public function payment(mixed $status): string
    {
        $value = strtolower(trim((string) $status));

        return match (true) {
            in_array($value, ['paid', 'successful', 'success', 'verified', 'completed'], true) => 'paid',
            in_array($value, ['failed', 'cancelled', 'canceled', 'declined', 'abandoned'], true) => 'failed',
            in_array($value, ['refunded', 'reversed'], true) => 'refunded',
            in_array($value, ['awaiting_bank_transfer', 'processing', 'initialized'], true) => 'processing',
            in_array($value, ['pending', 'pending payment', ''], true) => 'pending',
            default => 'unknown',
        };
    }

    public function fulfillment(mixed $status, bool $assigned = false): string
    {
        $value = strtolower(trim((string) $status));

        return match (true) {
            in_array($value, ['ticketed', 'issued', 'completed', 'successful', 'approved', 'confirmed'], true) => 'completed',
            in_array($value, ['failed', 'rejected', 'cancelled', 'canceled', 'expired'], true) => 'failed',
            $assigned || in_array($value, ['processing', 'in_process', 'inprocess', 'submitted', 'under_review', 'on_hold'], true) => 'in_progress',
            in_array($value, ['draft', 'pending', ''], true) => 'pending',
            default => 'unknown',
        };
    }

    public function date(mixed $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    public function issues(array $checks): array
    {
        return collect($checks)->filter()->keys()->values()->all();
    }

    public function number(mixed $value): ?float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        $number = (float) $value;

        return is_finite($number) ? $number : null;
    }
}
