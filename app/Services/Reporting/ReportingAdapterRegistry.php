<?php

namespace App\Services\Reporting;

use App\Contracts\ReportingSourceAdapter;
use Illuminate\Support\Facades\DB;

class ReportingAdapterRegistry
{
    /** @return array<int, ReportingSourceAdapter> */
    public function adapters(): array
    {
        return [
            $this->flights(),
            $this->travelFlex(),
            $this->visa(),
            $this->airCargo(),
            $this->transport('car_hires', 'car_hire', 'car_hire'),
            $this->transport('transfers', 'transfer', 'transfer'),
            $this->lounge(),
            $this->protocol(),
            $this->insurance(),
            ...$this->supportServices(),
        ];
    }

    private function flights(): ReportingSourceAdapter
    {
        return new DatabaseReportingAdapter('flight_bookings', 'flight_booking', function (object $row, DatabaseReportingAdapter $a): array {
            $payment = $a->payment(data_get($row, 'payment_status'));
            $fulfillment = $a->fulfillment(data_get($row, 'booking_status'));
            $gross = (float) data_get($row, 'total_price', 0);
            $supplier = data_get($row, 'supplier_price');
            $revenue = (float) data_get($row, 'markup_amount', 0);
            $snapshot = json_decode((string) data_get($row, 'flight_snapshot', '{}'), true) ?: [];
            $serviceAt = data_get($snapshot, 'departure_time')
                ?? data_get($snapshot, 'segments.0.departure.time')
                ?? data_get($snapshot, 'segments.0.departDT');
            $quantity = (int) data_get($row, 'adult_count', 0)
                + (int) data_get($row, 'child_count', 0)
                + (int) data_get($row, 'infant_count', 0);

            return $a->fact($row, [
                'product' => 'flights',
                'sub_product' => data_get($row, 'trip_type'),
                'reference' => data_get($row, 'booking_ref') ?: data_get($row, 'unique_id'),
                'customer_hash' => $a->customer(data_get($row, 'contact_email'), data_get($row, 'contact_phone')),
                'currency' => strtoupper((string) data_get($row, 'currency', 'NGN')),
                'gross_value' => $gross,
                'verified_collections' => $payment === 'paid'
                    ? (float) (data_get($row, 'payment_charged_amount') ?: data_get($row, 'payment_amount') ?: $gross)
                    : 0,
                'travelwheel_revenue' => $revenue,
                'supplier_cost' => $supplier === null ? null : (float) $supplier,
                'gross_profit' => $revenue,
                'payment_status' => $payment,
                'fulfillment_status' => $fulfillment,
                'payment_method' => data_get($row, 'payment_method'),
                'payment_gateway' => data_get($row, 'payment_gateway'),
                'quantity' => max(1, $quantity),
                'paid_at' => $payment === 'paid' ? ($a->date(data_get($row, 'payment_verified_at')) ?: $a->date(data_get($row, 'updated_at'))) : null,
                'service_at' => $a->date($serviceAt),
                'completed_at' => $a->date(data_get($row, 'ticket_ordered_at')),
                'dimensions' => [
                    'route' => data_get($row, 'route'),
                    'airline' => data_get($row, 'airline'),
                    'cabin' => data_get($row, 'cabin'),
                    'fare_type' => data_get($row, 'fare_type'),
                    'markup_category' => data_get($row, 'markup_category'),
                ],
                'data_quality' => $a->issues([
                    'missing_reference' => blank(data_get($row, 'booking_ref')) && blank(data_get($row, 'unique_id')),
                    'missing_supplier_cost' => $supplier === null,
                    'paid_not_fulfilled' => $payment === 'paid' && $fulfillment !== 'completed',
                    'fulfilled_not_paid' => $fulfillment === 'completed' && $payment !== 'paid',
                ]),
            ]);
        });
    }

    private function travelFlex(): ReportingSourceAdapter
    {
        return new DatabaseReportingAdapter('travel_flex_applications', 'travel_flex_application', function (object $row, DatabaseReportingAdapter $a): array {
            $applicant = json_decode((string) data_get($row, 'applicant_details', '{}'), true) ?: [];
            $payment = $a->payment(data_get($row, 'payment_status'));
            $fulfillment = $a->fulfillment(data_get($row, 'application_status'));
            $gross = (float) data_get($row, 'grand_total', 0);

            return $a->fact($row, [
                'product' => 'travel_flex',
                'sub_product' => 'flight_financing',
                'reference' => data_get($row, 'booking_ref') ?: data_get($row, 'unique_id'),
                'customer_hash' => $a->customer(data_get($applicant, 'email'), data_get($applicant, 'phone')),
                'gross_value' => $gross,
                'verified_collections' => $payment === 'paid' ? (float) data_get($row, 'down_payment', 0) : 0,
                'travelwheel_revenue' => (float) data_get($row, 'total_interest', 0),
                'supplier_cost' => null,
                'gross_profit' => null,
                'financially_additive' => false,
                'payment_status' => $payment,
                'fulfillment_status' => $fulfillment,
                'payment_method' => data_get($row, 'payment_method'),
                'paid_at' => $payment === 'paid' ? $a->date(data_get($row, 'updated_at')) : null,
                'completed_at' => $a->date(data_get($row, 'approved_at')) ?: $a->date(data_get($row, 'rejected_at')),
                'dimensions' => [
                    'application_status' => data_get($row, 'application_status'),
                    'provider_status' => data_get($row, 'provider_status'),
                    'down_percent' => data_get($row, 'down_percent'),
                    'financed_value' => max(0, $gross - (float) data_get($row, 'down_payment', 0)),
                ],
                'data_quality' => $a->issues([
                    'missing_reference' => blank(data_get($row, 'booking_ref')) && blank(data_get($row, 'unique_id')),
                    'missing_customer_identity' => blank(data_get($applicant, 'email')) && blank(data_get($applicant, 'phone')),
                    'approved_without_deposit' => $fulfillment === 'completed' && $payment !== 'paid',
                ]),
            ]);
        });
    }

    private function visa(): ReportingSourceAdapter
    {
        return new DatabaseReportingAdapter('visa_applications', 'visa_application', function (object $row, DatabaseReportingAdapter $a): array {
            $quote = DB::table('visa_quotes')
                ->where('visa_application_id', $row->id)
                ->orderByRaw("CASE WHEN status IN ('active', 'accepted', 'consumed') THEN 0 ELSE 1 END")
                ->orderByDesc('id')
                ->first();
            $paymentRow = DB::table('visa_payments')
                ->where('visa_application_id', $row->id)
                ->orderByDesc('id')
                ->first();
            $items = $quote
                ? DB::table('visa_quote_items')->where('visa_quote_id', $quote->id)->get()
                : collect();
            $payment = $a->payment(data_get($paymentRow, 'status'));
            $fulfillment = $a->fulfillment(data_get($row, 'status'));
            $gross = (float) data_get($quote, 'payable_total', 0);
            $revenue = (float) $items
                ->filter(fn (object $item): bool => strtolower((string) data_get($item, 'payee')) === 'travelwheel')
                ->sum('checkout_total');
            $authority = (float) $items
                ->reject(fn (object $item): bool => strtolower((string) data_get($item, 'payee')) === 'travelwheel')
                ->sum('checkout_total');
            $quantity = (int) data_get($row, 'adult_count', 0)
                + (int) data_get($row, 'child_count', 0)
                + (int) data_get($row, 'infant_count', 0);

            return $a->fact($row, [
                'product' => 'visa',
                'sub_product' => (string) data_get($row, 'visa_product_id'),
                'reference' => data_get($row, 'reference'),
                'customer_hash' => $a->customer(data_get($row, 'contact_email')),
                'currency' => strtoupper((string) data_get($quote, 'checkout_currency', 'NGN')),
                'gross_value' => $gross,
                'verified_collections' => $payment === 'paid'
                    ? (float) (data_get($paymentRow, 'verified_amount') ?: data_get($paymentRow, 'expected_amount') ?: $gross)
                    : 0,
                'travelwheel_revenue' => $revenue,
                'supplier_cost' => $authority > 0 ? $authority : null,
                'gross_profit' => $revenue,
                'payment_status' => $payment,
                'fulfillment_status' => $fulfillment,
                'payment_gateway' => data_get($paymentRow, 'provider'),
                'provider' => data_get($paymentRow, 'provider'),
                'quantity' => max(1, $quantity),
                'paid_at' => $a->date(data_get($paymentRow, 'verified_at')),
                'service_at' => $a->date(data_get($row, 'arrival_date')),
                'completed_at' => $a->date(data_get($row, 'issued_at')) ?: $a->date(data_get($row, 'decision_date')),
                'dimensions' => [
                    'destination_country_id' => data_get($row, 'destination_country_id'),
                    'processing_option_id' => data_get($row, 'visa_processing_option_id'),
                    'application_status' => data_get($row, 'status'),
                    'current_step' => data_get($row, 'current_step'),
                ],
                'data_quality' => $a->issues([
                    'missing_quote' => ! $quote && ! in_array(data_get($row, 'status'), ['draft', 'abandoned'], true),
                    'paid_not_fulfilled' => $payment === 'paid' && $fulfillment !== 'completed',
                    'fulfilled_not_paid' => $fulfillment === 'completed' && $payment !== 'paid',
                    'missing_customer_identity' => blank(data_get($row, 'contact_email')),
                ]),
            ]);
        });
    }

    private function airCargo(): ReportingSourceAdapter
    {
        return new DatabaseReportingAdapter('aircargo', 'aircargo', function (object $row, DatabaseReportingAdapter $a): array {
            $payment = $a->payment(data_get($row, 'payment_status'));
            $gross = (float) data_get($row, 'total_price', data_get($row, 'amount', 0));

            return $a->fact($row, [
                'product' => 'air_cargo',
                'sub_product' => data_get($row, 'shipment_type'),
                'reference' => data_get($row, 'shipping_id') ?: data_get($row, 'trans_id'),
                'customer_hash' => $a->customer(data_get($row, 'email'), data_get($row, 'phone')),
                'gross_value' => $gross,
                'verified_collections' => $payment === 'paid' ? $gross : 0,
                'travelwheel_revenue' => $gross,
                'supplier_cost' => null,
                'gross_profit' => null,
                'tax_amount' => data_get($row, 'vat'),
                'payment_status' => $payment,
                'fulfillment_status' => 'unknown',
                'payment_method' => data_get($row, 'payment_option'),
                'paid_at' => $payment === 'paid' ? $a->date(data_get($row, 'updated_at')) : null,
                'dimensions' => [
                    'destination' => data_get($row, 'shipping_to'),
                    'shipment_type' => data_get($row, 'shipment_type'),
                ],
                'data_quality' => $a->issues([
                    'missing_reference' => blank(data_get($row, 'shipping_id')) && blank(data_get($row, 'trans_id')),
                    'missing_supplier_cost' => true,
                    'missing_fulfillment_status' => true,
                ]),
            ]);
        });
    }

    private function transport(string $table, string $type, string $product): ReportingSourceAdapter
    {
        return new DatabaseReportingAdapter($table, $type, function (object $row, DatabaseReportingAdapter $a) use ($product): array {
            $payment = $a->payment(data_get($row, 'payment_status'));
            $gross = (float) data_get($row, 'amount', 0);
            $assigned = (bool) data_get($row, 'driver_assigned', false);
            $fulfillment = $assigned ? 'in_progress' : 'pending';

            return $a->fact($row, [
                'product' => $product,
                'sub_product' => data_get($row, 'category') ?: data_get($row, 'vehicle_type'),
                'reference' => data_get($row, 'payment_reference'),
                'customer_hash' => $a->customer(data_get($row, 'email'), data_get($row, 'phone_number')),
                'gross_value' => $gross,
                'verified_collections' => $payment === 'paid' ? $gross : 0,
                'travelwheel_revenue' => $gross,
                'supplier_cost' => null,
                'gross_profit' => null,
                'payment_status' => $payment,
                'fulfillment_status' => $fulfillment,
                'payment_method' => data_get($row, 'payment_option'),
                'quantity' => max(1, (int) data_get($row, 'passengers', 1)),
                'paid_at' => $payment === 'paid' ? $a->date(data_get($row, 'updated_at')) : null,
                'service_at' => $a->date(data_get($row, 'pickup_date')),
                'dimensions' => [
                    'pickup' => data_get($row, 'pickup_location'),
                    'dropoff' => data_get($row, 'dropoff_location'),
                    'vehicle' => data_get($row, 'car_model') ?: data_get($row, 'vehicle_name'),
                    'driver_assigned' => $assigned,
                ],
                'data_quality' => $a->issues([
                    'missing_supplier_cost' => true,
                    'paid_unassigned' => $payment === 'paid' && ! $assigned,
                ]),
            ]);
        });
    }

    private function lounge(): ReportingSourceAdapter
    {
        return new DatabaseReportingAdapter('lounge_service', 'lounge_booking', function (object $row, DatabaseReportingAdapter $a): array {
            $payment = $a->payment(data_get($row, 'status'));
            $gross = (float) data_get($row, 'amount', 0);

            return $a->fact($row, [
                'product' => 'lounge',
                'sub_product' => data_get($row, 'service'),
                'reference' => data_get($row, 'ref_id') ?: data_get($row, 'trans_id'),
                'customer_hash' => $a->customer(data_get($row, 'email'), data_get($row, 'phone_no')),
                'gross_value' => $gross,
                'verified_collections' => $payment === 'paid' ? $gross : 0,
                'travelwheel_revenue' => $gross,
                'supplier_cost' => null,
                'gross_profit' => null,
                'tax_amount' => data_get($row, 'vat'),
                'payment_status' => $payment,
                'fulfillment_status' => $a->fulfillment(data_get($row, 'status')),
                'payment_method' => data_get($row, 'payment_option'),
                'provider' => data_get($row, 'lounge_name'),
                'quantity' => max(1, (int) data_get($row, 'nop', 1)),
                'paid_at' => $payment === 'paid' ? $a->date(data_get($row, 'updated_at')) : null,
                'service_at' => $a->date(data_get($row, 'travel_date')),
                'dimensions' => ['terminal' => data_get($row, 'terminal'), 'airline' => data_get($row, 'airline')],
                'data_quality' => $a->issues(['missing_supplier_cost' => true]),
            ]);
        });
    }

    private function protocol(): ReportingSourceAdapter
    {
        return new DatabaseReportingAdapter('protocol_bookings', 'protocol_booking', function (object $row, DatabaseReportingAdapter $a): array {
            $payment = $a->payment(data_get($row, 'status'));
            $gross = (float) data_get($row, 'amount', 0);

            return $a->fact($row, [
                'product' => 'protocol',
                'sub_product' => data_get($row, 'package') ?: data_get($row, 'service_type'),
                'reference' => data_get($row, 'ref_id') ?: data_get($row, 'trans_id'),
                'customer_hash' => $a->customer(data_get($row, 'email'), data_get($row, 'phone')),
                'gross_value' => $gross,
                'verified_collections' => $payment === 'paid' ? $gross : 0,
                'travelwheel_revenue' => $gross,
                'supplier_cost' => null,
                'gross_profit' => null,
                'tax_amount' => data_get($row, 'vat'),
                'payment_status' => $payment,
                'fulfillment_status' => $a->fulfillment(data_get($row, 'status')),
                'payment_method' => data_get($row, 'paymentoption'),
                'quantity' => max(1, (int) data_get($row, 'passenger', 1)),
                'paid_at' => $payment === 'paid' ? $a->date(data_get($row, 'updated_at')) : null,
                'service_at' => $a->date(data_get($row, 'travel_date')),
                'dimensions' => ['airport' => data_get($row, 'airport'), 'airline' => data_get($row, 'airline')],
                'data_quality' => $a->issues(['missing_supplier_cost' => true]),
            ]);
        });
    }

    private function insurance(): ReportingSourceAdapter
    {
        return new DatabaseReportingAdapter('insurance_purchases', 'insurance_purchase', function (object $row, DatabaseReportingAdapter $a): array {
            $payment = $a->payment(data_get($row, 'status', data_get($row, 'payment_status')));
            $gross = (float) data_get($row, 't_amount', data_get($row, 'amount', 0));
            $supplier = data_get($row, 'c_amount');
            $revenue = $supplier === null ? $gross : max(0, $gross - (float) $supplier);

            return $a->fact($row, [
                'product' => 'insurance',
                'sub_product' => data_get($row, 'product_name', data_get($row, 'plan')),
                'reference' => data_get($row, 'ref_id') ?: data_get($row, 'trans_id') ?: data_get($row, 'reference'),
                'customer_hash' => $a->customer(data_get($row, 'email'), data_get($row, 'phone')),
                'currency' => strtoupper((string) data_get($row, 'currency', 'NGN')),
                'gross_value' => $gross,
                'verified_collections' => $payment === 'paid' ? $gross : 0,
                'travelwheel_revenue' => $revenue,
                'supplier_cost' => $supplier === null ? null : (float) $supplier,
                'gross_profit' => $supplier === null ? null : $revenue,
                'tax_amount' => data_get($row, 'vat'),
                'payment_status' => $payment,
                'fulfillment_status' => $a->fulfillment(data_get($row, 'status')),
                'payment_method' => data_get($row, 'payment_option'),
                'provider' => data_get($row, 'provider'),
                'paid_at' => $payment === 'paid' ? $a->date(data_get($row, 'updated_at')) : null,
                'service_at' => $a->date(data_get($row, 'start_date')),
                'data_quality' => $a->issues(['missing_supplier_cost' => $supplier === null]),
            ]);
        });
    }

    /** @return array<int, ReportingSourceAdapter> */
    private function supportServices(): array
    {
        $definitions = [
            ['support_flight_assists', 'support_flight_assist', 'Flight assist', 'email', 'phone', 'travel_date_oneway'],
            ['support_extra_luggage_requests', 'support_extra_luggage', 'Extra luggage', 'email', 'contact_number', null],
            ['support_visa_confirmations', 'support_visa_confirmation', 'Visa confirmation', 'email', 'phone_number', null],
            ['support_yellow_cards', 'support_yellow_card', 'Yellow card', 'email', 'phone_number', null],
        ];

        return array_map(function (array $definition): ReportingSourceAdapter {
            [$table, $type, $label, $email, $phone, $serviceDate] = $definition;

            return new DatabaseReportingAdapter($table, $type, function (object $row, DatabaseReportingAdapter $a) use ($label, $email, $phone, $serviceDate): array {
                $payment = $a->payment(data_get($row, 'payment_status'));
                $gross = (float) data_get($row, 'amount', 0);

                return $a->fact($row, [
                    'product' => 'support',
                    'sub_product' => $label,
                    'reference' => data_get($row, 'payment_reference'),
                    'customer_hash' => $a->customer(data_get($row, $email), data_get($row, $phone)),
                    'gross_value' => $gross,
                    'verified_collections' => $payment === 'paid' ? $gross : 0,
                    'travelwheel_revenue' => $gross,
                    'supplier_cost' => null,
                    'gross_profit' => null,
                    'payment_status' => $payment,
                    'fulfillment_status' => 'unknown',
                    'payment_method' => data_get($row, 'payment_option'),
                    'paid_at' => $payment === 'paid' ? $a->date(data_get($row, 'updated_at')) : null,
                    'service_at' => $serviceDate ? $a->date(data_get($row, $serviceDate)) : null,
                    'dimensions' => ['service' => $label],
                    'data_quality' => $a->issues([
                        'missing_supplier_cost' => true,
                        'missing_fulfillment_status' => true,
                    ]),
                ]);
            });
        }, $definitions);
    }
}
