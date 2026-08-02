<?php

namespace App\Services;

class TravelFlexRiskAssessmentService
{
    public function assess(array $flight, array $selectedExtras = []): array
    {
        $extrasTotal = $this->selectedExtrasTotal($selectedExtras);
        $ticketTotal = round((float) ($flight['price'] ?? 0) + $extrasTotal, 2);
        $minimumFloorPercent = max(30, (int) config('travelwheel.travelflex_minimum_down_payment_percent', 30));
        $percentageStep = max(1, (int) config('travelwheel.travelflex_down_payment_percentage_step', 10));
        $maximumPercent = min(100, max($minimumFloorPercent, (int) config('travelwheel.travelflex_maximum_down_payment_percent', 90)));

        if ($ticketTotal <= 0) {
            return $this->ineligible('Unable to calculate the TravelFlex ticket cost.', $ticketTotal);
        }

        $fareBreakdown = $flight['fareBreakdown'] ?? [];

        if (! is_array($fareBreakdown) || $fareBreakdown === []) {
            return $this->ineligible(
                'TravelFlex is unavailable because the airline did not provide refund penalty details for this fare.',
                $ticketTotal,
            );
        }

        $refundPenaltyTotal = 0.0;
        $penaltyBreakdown = [];

        foreach ($fareBreakdown as $index => $fare) {
            if (! is_array($fare)) {
                return $this->ineligible('The airline returned incomplete refund penalty details for this fare.', $ticketTotal);
            }

            $passengerType = (string) ($fare['passengerType'] ?? 'Passenger '.($index + 1));
            $quantity = (int) ($fare['qty'] ?? 0);
            $refundAllowed = $this->boolean($fare['refundAllowed'] ?? null);
            $rawPenalty = $fare['refundPenalty'] ?? null;

            if ($quantity < 1 || $refundAllowed !== true || ! $this->validMoney($rawPenalty)) {
                return $this->ineligible(
                    'TravelFlex is unavailable because complete refundable-fare penalties were not supplied for every passenger type.',
                    $ticketTotal,
                );
            }

            $penaltyPerPassenger = round((float) $rawPenalty, 2);
            $subtotal = round($penaltyPerPassenger * $quantity, 2);
            $refundPenaltyTotal = round($refundPenaltyTotal + $subtotal, 2);
            $penaltyBreakdown[] = [
                'passenger_type' => $passengerType,
                'quantity' => $quantity,
                'penalty_per_passenger' => $penaltyPerPassenger,
                'subtotal' => $subtotal,
            ];
        }

        $processingFee = max(0, round((float) config('travelwheel.travelflex_refund_processing_fee', 0), 2));
        $bufferRate = max(0, (float) config('travelwheel.travelflex_refund_risk_buffer_rate', 0.05));
        $fixedBuffer = max(0, round((float) config('travelwheel.travelflex_refund_risk_buffer_fixed', 0), 2));
        $riskBuffer = max(round($ticketTotal * $bufferRate, 2), $fixedBuffer);
        $estimatedCancellationCost = round($refundPenaltyTotal + $extrasTotal + $processingFee + $riskBuffer, 2);
        $floorAmount = round($ticketTotal * ($minimumFloorPercent / 100), 2);
        $requiredCoverageAmount = max($floorAmount, $estimatedCancellationCost);
        $rawRequiredPercent = ($requiredCoverageAmount / $ticketTotal) * 100;
        $minimumDownPercent = (int) (ceil($rawRequiredPercent / $percentageStep) * $percentageStep);
        $minimumDownPercent = max($minimumFloorPercent, $minimumDownPercent);

        if ($minimumDownPercent > $maximumPercent) {
            return [
                ...$this->ineligible(
                    "This fare needs more than {$maximumPercent}% upfront to cover its estimated cancellation risk.",
                    $ticketTotal,
                ),
                'refund_penalty_total' => $refundPenaltyTotal,
                'non_refundable_extras' => $extrasTotal,
                'refund_processing_fee' => $processingFee,
                'risk_buffer' => $riskBuffer,
                'risk_buffer_rate' => $bufferRate,
                'estimated_cancellation_cost' => $estimatedCancellationCost,
                'required_coverage_amount' => round($requiredCoverageAmount, 2),
                'raw_required_percent' => round($rawRequiredPercent, 2),
                'penalty_breakdown' => $penaltyBreakdown,
            ];
        }

        return [
            'eligible' => true,
            'reason' => '',
            'ticket_total' => $ticketTotal,
            'minimum_floor_percent' => $minimumFloorPercent,
            'maximum_down_percent' => $maximumPercent,
            'percentage_step' => $percentageStep,
            'refund_penalty_total' => $refundPenaltyTotal,
            'non_refundable_extras' => $extrasTotal,
            'refund_processing_fee' => $processingFee,
            'risk_buffer' => $riskBuffer,
            'risk_buffer_rate' => $bufferRate,
            'estimated_cancellation_cost' => $estimatedCancellationCost,
            'required_coverage_amount' => round($requiredCoverageAmount, 2),
            'raw_required_percent' => round($rawRequiredPercent, 2),
            'minimum_down_percent' => $minimumDownPercent,
            'minimum_down_payment' => round($ticketTotal * ($minimumDownPercent / 100), 2),
            'penalty_breakdown' => $penaltyBreakdown,
            'assessed_at' => now()->toIso8601String(),
        ];
    }

    private function selectedExtrasTotal(array $selectedExtras): float
    {
        $total = 0.0;

        foreach ($selectedExtras as $category) {
            if (! is_array($category)) {
                continue;
            }

            foreach ($category as $item) {
                if (! is_array($item)) {
                    continue;
                }

                if (isset($item['line_total'])) {
                    $total += (float) $item['line_total'];

                    continue;
                }

                $quantity = max(1, (int) ($item['quantity'] ?? 1));
                $total += (float) ($item['unit_price'] ?? 0) * $quantity;
            }
        }

        return round($total, 2);
    }

    private function validMoney(mixed $value): bool
    {
        return $value !== null && $value !== '' && is_numeric($value) && (float) $value >= 0;
    }

    private function boolean(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === null || $value === '') {
            return null;
        }

        $normalized = strtolower(trim((string) $value));

        if (in_array($normalized, ['1', 'true', 'yes', 'y', 'allowed', 'refundable'], true)) {
            return true;
        }

        if (in_array($normalized, ['0', 'false', 'no', 'n', 'not allowed', 'non-refundable', 'nonrefundable'], true)) {
            return false;
        }

        return null;
    }

    private function ineligible(string $reason, float $ticketTotal): array
    {
        return [
            'eligible' => false,
            'reason' => $reason,
            'ticket_total' => round($ticketTotal, 2),
            'minimum_down_percent' => null,
            'minimum_down_payment' => null,
        ];
    }
}
