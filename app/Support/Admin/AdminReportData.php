<?php

namespace App\Support\Admin;

use App\Models\FlightBooking;
use App\Models\PostTicketingRequest;
use App\Models\TravelFlexApplication;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminReportData
{
    public static function range(?string $from = null, ?string $to = null): array
    {
        $start = filled($from)
            ? CarbonImmutable::parse($from)->startOfDay()
            : now()->subDays(30)->toImmutable()->startOfDay();

        $end = filled($to)
            ? CarbonImmutable::parse($to)->endOfDay()
            : now()->toImmutable()->endOfDay();

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->startOfDay(), $start->endOfDay()];
        }

        return [$start, $end];
    }

    public static function summary(?string $from = null, ?string $to = null): array
    {
        $bookings = self::bookingQuery($from, $to);

        return [
            'bookings' => (clone $bookings)->count(),
            'paid_revenue' => (float) (clone $bookings)
                ->where('payment_status', 'paid')
                ->sum(DB::raw('COALESCE(payment_charged_amount, payment_amount, total_price, 0)')),
            'paid_service_charges' => (float) (clone $bookings)
                ->where('payment_status', 'paid')
                ->sum('markup_amount'),
            'ticketed' => (clone $bookings)->where('booking_status', 'ticketed')->count(),
            'awaiting_bank_transfer' => (clone $bookings)->where('payment_status', 'awaiting_bank_transfer')->count(),
            'ticketing_failures' => (clone $bookings)
                ->whereIn('booking_status', ['failed', 'ticketing_failed'])
                ->where('payment_status', 'paid')
                ->count(),
            'pending_payment' => (clone $bookings)->where('payment_status', 'pending')->count(),
            'post_ticketing' => PostTicketingRequest::query()
                ->whereBetween('created_at', self::range($from, $to))
                ->whereIn('status', ['pending', 'submitted', 'in_process', 'inprocess'])
                ->count(),
            'travelflex' => TravelFlexApplication::query()
                ->whereBetween('created_at', self::range($from, $to))
                ->count(),
        ];
    }

    public static function breakdown(string $field, ?string $from = null, ?string $to = null, int $limit = 10): Collection
    {
        return self::bookingQuery($from, $to)
            ->selectRaw("COALESCE(NULLIF({$field}, ''), 'Unspecified') as label")
            ->selectRaw('COUNT(*) as bookings')
            ->selectRaw("SUM(CASE WHEN payment_status = 'paid' THEN COALESCE(payment_charged_amount, payment_amount, total_price, 0) ELSE 0 END) as revenue")
            ->selectRaw("SUM(CASE WHEN payment_status = 'paid' THEN COALESCE(markup_amount, 0) ELSE 0 END) as service_charges")
            ->groupBy('label')
            ->orderByDesc('revenue')
            ->orderByDesc('bookings')
            ->limit($limit)
            ->get();
    }

    public static function bankTransferRows(?string $from = null, ?string $to = null): Collection
    {
        return self::bookingQuery($from, $to)
            ->whereIn('payment_method', ['bank_transfer', 'flex_bank_transfer'])
            ->orderByDesc('created_at')
            ->get([
                'created_at',
                'booking_ref',
                'unique_id',
                'contact_email',
                'contact_phone',
                'payment_status',
                'payment_method',
                'bank_transfer_reference',
                'bank_transfer_notified_at',
                'currency',
                'supplier_price',
                'markup_amount',
                'markup_category',
                'total_price',
                'payment_amount',
                'payment_charged_amount',
            ]);
    }

    public static function ticketingFailureRows(?string $from = null, ?string $to = null): Collection
    {
        return self::bookingQuery($from, $to)
            ->whereIn('booking_status', ['failed', 'ticketing_failed'])
            ->with(['ticketingRecords' => fn ($query) => $query->latest()->limit(1)])
            ->orderByDesc('created_at')
            ->get([
                'id',
                'created_at',
                'booking_ref',
                'unique_id',
                'route',
                'airline',
                'payment_status',
                'booking_status',
                'ticket_ordered',
                'currency',
                'supplier_price',
                'markup_amount',
                'markup_category',
                'total_price',
            ]);
    }

    public static function postTicketingRows(?string $from = null, ?string $to = null): Collection
    {
        return PostTicketingRequest::query()
            ->with(['booking:id,booking_ref,route,airline', 'admin:id,name,email'])
            ->whereBetween('created_at', self::range($from, $to))
            ->orderByDesc('created_at')
            ->get();
    }

    public static function travelFlexRows(?string $from = null, ?string $to = null): Collection
    {
        return TravelFlexApplication::query()
            ->with('booking:id,booking_ref,route,airline')
            ->whereBetween('created_at', self::range($from, $to))
            ->orderByDesc('created_at')
            ->get();
    }

    public static function csv(string $report, ?string $from = null, ?string $to = null): Responsable|StreamedResponse
    {
        [$headers, $rows] = match ($report) {
            'bank-transfers' => self::bankTransferExport($from, $to),
            'ticketing-failures' => self::ticketingFailureExport($from, $to),
            'post-ticketing' => self::postTicketingExport($from, $to),
            'travelflex' => self::travelFlexExport($from, $to),
            'bookings' => self::bookingExport($from, $to),
            default => abort(404),
        };

        return response()->streamDownload(function () use ($headers, $rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $report . '-' . now()->format('Ymd-His') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public static function bookingQuery(?string $from = null, ?string $to = null): Builder
    {
        return FlightBooking::query()->whereBetween('created_at', self::range($from, $to));
    }

    private static function bookingExport(?string $from, ?string $to): array
    {
        $rows = self::bookingQuery($from, $to)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (FlightBooking $booking): array => [
                self::watDateTime($booking->created_at),
                $booking->booking_ref,
                $booking->unique_id,
                $booking->route,
                $booking->airline,
                $booking->fare_type,
                $booking->payment_method,
                $booking->payment_status,
                $booking->booking_status,
                $booking->currency,
                $booking->supplier_price,
                $booking->markup_amount,
                $booking->markup_category,
                $booking->total_price,
                $booking->payment_charged_amount ?: $booking->payment_amount,
            ]);

        return [[
            'Created At', 'Booking Ref', 'UniqueID', 'Route', 'Airline', 'Fare Type',
            'Payment Method', 'Payment Status', 'Booking Status', 'Currency', 'Supplier Fare',
            'Service Charge', 'Markup Category', 'Total Price', 'Paid Amount',
        ], $rows];
    }

    private static function bankTransferExport(?string $from, ?string $to): array
    {
        $rows = self::bankTransferRows($from, $to)->map(fn (FlightBooking $booking): array => [
            self::watDateTime($booking->created_at),
            self::watDateTime($booking->bank_transfer_notified_at),
            $booking->booking_ref,
            $booking->unique_id,
            $booking->contact_email,
            $booking->contact_phone,
            $booking->payment_method,
            $booking->payment_status,
            $booking->bank_transfer_reference,
            $booking->currency,
            $booking->supplier_price,
            $booking->markup_amount,
            $booking->markup_category,
            $booking->payment_charged_amount ?: ($booking->payment_amount ?: $booking->total_price),
        ]);

        return [[
            'Created At', 'Transfer Notified At', 'Booking Ref', 'UniqueID', 'Email', 'Phone',
            'Payment Method', 'Payment Status', 'Transfer Reference', 'Currency', 'Supplier Fare',
            'Service Charge', 'Markup Category', 'Amount',
        ], $rows];
    }

    private static function ticketingFailureExport(?string $from, ?string $to): array
    {
        $rows = self::ticketingFailureRows($from, $to)->map(function (FlightBooking $booking): array {
            $latest = $booking->ticketingRecords->first();

            return [
                self::watDateTime($booking->created_at),
                $booking->booking_ref,
                $booking->unique_id,
                $booking->route,
                $booking->airline,
                $booking->payment_status,
                $booking->booking_status,
                $booking->currency,
                $booking->supplier_price,
                $booking->markup_amount,
                $booking->markup_category,
                $booking->total_price,
                $latest?->action,
                $latest?->message,
            ];
        });

        return [[
            'Created At', 'Booking Ref', 'UniqueID', 'Route', 'Airline', 'Payment Status',
            'Booking Status', 'Currency', 'Supplier Fare', 'Service Charge', 'Markup Category',
            'Total Price', 'Latest Ticketing Action', 'Latest Message',
        ], $rows];
    }

    private static function postTicketingExport(?string $from, ?string $to): array
    {
        $rows = self::postTicketingRows($from, $to)->map(fn (PostTicketingRequest $request): array => [
            self::watDateTime($request->created_at),
            $request->booking?->booking_ref,
            $request->unique_id,
            $request->ptr_unique_id,
            $request->operation_type,
            $request->status,
            $request->error_message,
            $request->admin?->email,
            $request->admin_note,
        ]);

        return [[
            'Created At', 'Booking Ref', 'UniqueID', 'PTR UniqueID', 'Operation',
            'Status', 'Error Message', 'Admin', 'Admin Note',
        ], $rows];
    }

    private static function travelFlexExport(?string $from, ?string $to): array
    {
        $rows = self::travelFlexRows($from, $to)->map(fn (TravelFlexApplication $application): array => [
            self::watDateTime($application->created_at),
            $application->booking_ref,
            $application->unique_id,
            data_get($application->applicant_details, 'full_name'),
            data_get($application->applicant_details, 'email'),
            $application->payment_method,
            $application->payment_status,
            $application->application_status,
            $application->provider_status,
            $application->down_payment,
            $application->grand_total,
            $application->total_interest,
        ]);

        return [[
            'Created At', 'Booking Ref', 'UniqueID', 'Applicant', 'Email', 'Payment Method',
            'Payment Status', 'Application Status', 'Provider Status', 'Down Payment', 'Grand Total', 'Interest',
        ], $rows];
    }

    private static function watDateTime(mixed $value, string $format = 'Y-m-d H:i:s'): string
    {
        if (blank($value)) {
            return '';
        }

        try {
            return \Carbon\Carbon::parse($value)->timezone('Africa/Lagos')->format($format);
        } catch (\Throwable) {
            return (string) $value;
        }
    }
}
