<?php

namespace App\Services;

use App\Models\TravelFlexApplication;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use setasign\Fpdi\Fpdi;

class TravelFlexApplicationPdfService
{
    public const TEMPLATE_VERSION = 'fast-credit-tnpl-v1';

    public function generate(TravelFlexApplication $application, array $sensitive = []): string
    {
        $template = public_path('assets/fast_creadit.pdf');

        if (! is_file($template)) {
            throw new RuntimeException('The Fast Credit application PDF template is missing.');
        }

        $application->loadMissing('booking');
        $pdf = new Fpdi('P', 'pt');
        $pdf->SetAutoPageBreak(false);
        $pageCount = $pdf->setSourceFile($template);

        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            $templateId = $pdf->importPage($pageNumber);
            $size = $pdf->getTemplateSize($templateId);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId, 0, 0, $size['width'], $size['height']);

            if ($pageNumber === 1) {
                $this->fillApplicationPage($pdf, $application, $sensitive);
            } elseif ($pageNumber === 2) {
                $this->fillAgreementPage($pdf, $application);
            }
        }

        return $pdf->Output('S');
    }

    public function generateAndStore(TravelFlexApplication $application, array $sensitive = []): TravelFlexApplication
    {
        $bytes = $this->generate($application, $sensitive);
        $reference = preg_replace(
            '/[^A-Za-z0-9_-]+/',
            '_',
            $application->booking_ref ?: $application->unique_id ?: (string) $application->id,
        );
        $path = "travelflex_applications/{$application->id}/Fast_Credit_Application_{$reference}.pdf";

        if (! Storage::disk('local')->put($path, $bytes)) {
            throw new RuntimeException('The completed Fast Credit application could not be stored.');
        }

        $application->update([
            'generated_application_path' => $path,
            'generated_application_sha256' => hash('sha256', $bytes),
            'generated_application_version' => self::TEMPLATE_VERSION,
            'generated_application_at' => now(),
        ]);

        return $application->fresh(['booking']);
    }

    public function storedAbsolutePath(TravelFlexApplication $application): ?string
    {
        $path = $application->generated_application_path;

        if (blank($path) || ! Storage::disk('local')->exists($path)) {
            return null;
        }

        return Storage::disk('local')->path($path);
    }

    private function fillApplicationPage(Fpdi $pdf, TravelFlexApplication $application, array $sensitive): void
    {
        $applicant = $application->applicant_details ?? [];
        $identity = $application->identity_details ?? [];
        $employment = $application->employment_details ?? [];
        $bank = $application->bank_details ?? [];
        $nextOfKin = $application->next_of_kin_details ?? [];
        $agreement = $application->agreement_acceptance ?? [];
        $plan = $application->repayment_plan ?? [];
        $acceptedAt = $this->date(data_get($agreement, 'accepted_at')) ?? now('Africa/Lagos');
        $bvn = (string) ($sensitive['bvn'] ?? '');

        if ($bvn === '') {
            $lastFour = (string) data_get($application->bvn_metadata, 'last_four', '');
            $bvn = $lastFour !== '' ? '*******'.$lastFour : '';
        }

        $pdf->SetTextColor(17, 24, 39);
        $pdf->SetFont('Helvetica', '', 9);

        $this->boxedCharacters($pdf, 249, 174, 244, 25, data_get($identity, 'nin'), 11);
        $this->dateBoxes($pdf, $acceptedAt, [[685, 44], [733, 44], [781, 88]], 183, 16);
        $this->boxedCharacters($pdf, 227, 220, 244, 25, $bvn, 11);

        $this->field($pdf, 85, 296, 222, 26, data_get($identity, 'title'));
        $this->field($pdf, 392, 296, 333, 26, data_get($identity, 'surname'));
        $this->field($pdf, 117, 333, 333, 26, data_get($identity, 'first_name'));
        $this->field($pdf, 526, 333, 332, 26, data_get($identity, 'other_name'));

        $marital = strtolower((string) data_get($identity, 'marital_status'));
        $this->check($pdf, 302, 370, $marital === 'single');
        $this->check($pdf, 370, 370, $marital === 'married');
        $this->check($pdf, 445, 370, $marital === 'divorced');
        $this->check($pdf, 520, 370, $marital === 'separated');
        $gender = strtolower((string) data_get($identity, 'gender'));
        $this->check($pdf, 794, 370, $gender === 'female');
        $this->check($pdf, 840, 370, $gender === 'male');

        $this->dateBoxes($pdf, $this->date(data_get($identity, 'date_of_birth')), [[132, 44], [180, 44], [228, 88]], 417, 16);
        $this->field($pdf, 185, 445, 244, 25, data_get($applicant, 'phone_primary'));
        $this->field($pdf, 561, 445, 244, 25, data_get($applicant, 'phone_secondary'));
        $this->field($pdf, 134, 482, 443, 25, data_get($applicant, 'email'), 8);

        $sector = $application->applicant_type === 'company'
            ? 'private'
            : strtolower((string) data_get($employment, 'sector'));
        $this->check($pdf, 141, 518, $sector === 'private');
        $this->check($pdf, 209, 518, $sector === 'public');
        $this->field($pdf, 439, 521, 285, 25, data_get($identity, 'passport_number'));
        $this->dateBoxes($pdf, $this->date(data_get($identity, 'passport_expiry_date')), [[724, 43], [769, 43], [814, 90]], 520, 26);

        $this->field($pdf, 106, 557, 443, 25, data_get($employment, 'occupation'));
        $this->field($pdf, 210, 616, 244, 25, data_get($employment, 'ippis_number'));
        $this->field($pdf, 149, 653, 665, 26, data_get($employment, 'employer_name'), 8);
        $this->field($pdf, 159, 690, 665, 26, data_get($employment, 'employer_address'), 7);
        $this->field($pdf, 178, 728, 333, 26, $this->money(data_get($bank, 'monthly_salary')));
        $this->field($pdf, 180, 765, 333, 26, data_get($bank, 'salary_account_number'));
        $this->field($pdf, 121, 802, 333, 26, data_get($bank, 'bank_name'));

        $platform = strtolower((string) data_get($identity, 'social_media_platform'));
        $this->check($pdf, 256, 837, $platform === 'facebook');
        $this->check($pdf, 358, 837, $platform === 'instagram');
        $this->check($pdf, 507, 835, $platform === 'x');
        $this->field($pdf, 164, 876, 665, 25, data_get($identity, 'social_media_handle'), 8);
        $this->field($pdf, 161, 913, 443, 26, data_get($applicant, 'home_address'), 7);
        $this->field($pdf, 138, 951, 222, 25, $this->governmentIdLabel(data_get($identity, 'government_id_type')));
        $this->field($pdf, 106, 988, 221, 26, data_get($employment, 'office_id', data_get($employment, 'staff_number')));

        $this->lineText($pdf, 214, 1038, 235, 23, $this->proposedTravelDate($application));

        $this->field($pdf, 109, 1104, 333, 26, data_get($nextOfKin, 'surname'));
        $this->field($pdf, 571, 1104, 333, 26, data_get($nextOfKin, 'other_names'));
        $this->field($pdf, 117, 1141, 311, 26, data_get($nextOfKin, 'first_name'));
        $this->field($pdf, 125, 1179, 155, 25, data_get($nextOfKin, 'relationship'));
        $this->dateBoxes($pdf, $this->date(data_get($nextOfKin, 'date_of_birth')), [[132, 44], [180, 44], [228, 88]], 1226, 16);
        $nextGender = strtolower((string) data_get($nextOfKin, 'gender'));
        $this->check($pdf, 433, 1215, $nextGender === 'female');
        $this->check($pdf, 479, 1215, $nextGender === 'male');
        $this->field($pdf, 585, 1216, 155, 26, data_get($nextOfKin, 'title'));
        $this->field($pdf, 161, 1253, 443, 26, data_get($nextOfKin, 'residential_address'), 7);
        $this->field($pdf, 143, 1290, 244, 26, data_get($nextOfKin, 'phone_primary'));
        $this->field($pdf, 505, 1290, 244, 26, data_get($nextOfKin, 'phone_secondary'));
        $this->field($pdf, 142, 1327, 443, 26, data_get($nextOfKin, 'email'), 8);

        $this->field($pdf, 143, 1365, 333, 25, 'TravelWheel');
        $this->field($pdf, 149, 1403, 333, 25, $this->travelPackage($application), 7);
        $this->field($pdf, 129, 1439, 333, 26, $this->money(data_get($plan, 'loan_amount', data_get($plan, 'remaining_balance'))));
        $this->field($pdf, 124, 1477, 333, 26, data_get($plan, 'repayment_plan'));
        $this->signature($pdf, data_get($agreement, 'signature_image'), 162, 1505, 234, 34);
        $this->dateBoxes($pdf, $acceptedAt, [[718, 44], [766, 44], [814, 88]], 1529, 16);
    }

    private function fillAgreementPage(Fpdi $pdf, TravelFlexApplication $application): void
    {
        $agreement = $application->agreement_acceptance ?? [];
        $name = data_get($application->applicant_details, 'full_name');
        $acceptedAt = $this->date(data_get($agreement, 'accepted_at')) ?? now('Africa/Lagos');
        $date = $acceptedAt->format('d/m/Y');

        $this->lineText($pdf, 520, 944, 320, 24, $name, 9);
        $this->signature($pdf, data_get($agreement, 'signature_image'), 520, 974, 320, 30);
        $this->lineText($pdf, 520, 1017, 320, 20, $date, 9);

        $this->lineText($pdf, 500, 1088, 176, 24, $name, 8);
        $this->lineText($pdf, 603, 1237, 256, 24, $name, 8);
        $this->signature($pdf, data_get($agreement, 'signature_image'), 633, 1263, 228, 30);
        $this->lineText($pdf, 594, 1310, 266, 24, data_get($agreement, 'witness.full_name'), 8);
        $this->signature($pdf, data_get($agreement, 'witness.signature_image'), 630, 1336, 235, 30);
        $this->lineText($pdf, 558, 1384, 300, 24, $date, 9);
    }

    private function field(Fpdi $pdf, float $x, float $y, float $width, float $height, mixed $value, float $maxFont = 9): void
    {
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect($x + 1, $y + 1, max(0, $width - 2), max(0, $height - 2), 'F');
        $this->lineText($pdf, $x + 3, $y + 1, $width - 6, $height - 2, $value, $maxFont);
    }

    private function lineText(Fpdi $pdf, float $x, float $y, float $width, float $height, mixed $value, float $maxFont = 9): void
    {
        $text = $this->pdfText($value);

        if ($text === '') {
            return;
        }

        $fontSize = $maxFont;
        do {
            $pdf->SetFont('Helvetica', '', $fontSize);
            $fontSize -= 0.25;
        } while ($fontSize >= 5 && $pdf->GetStringWidth($text) > $width);

        $pdf->SetTextColor(17, 24, 39);
        $pdf->SetXY($x, $y);
        $pdf->Cell($width, $height, $text, 0, 0, 'C');
    }

    private function boxedCharacters(Fpdi $pdf, float $x, float $y, float $width, float $height, mixed $value, int $slots): void
    {
        $characters = preg_split('//u', mb_strtoupper(trim((string) $value)), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $cellWidth = $width / $slots;

        foreach (array_slice($characters, 0, $slots) as $index => $character) {
            $this->lineText($pdf, $x + ($index * $cellWidth), $y, $cellWidth, $height, $character, 9);
        }
    }

    private function dateBoxes(Fpdi $pdf, ?Carbon $date, array $groups, float $y, float $height): void
    {
        if (! $date) {
            return;
        }

        $values = [$date->format('d'), $date->format('m'), $date->format('Y')];

        foreach ($groups as $index => [$x, $width]) {
            $this->boxedCharacters($pdf, $x, $y, $width, $height, $values[$index], strlen($values[$index]));
        }
    }

    private function check(Fpdi $pdf, float $x, float $y, bool $checked): void
    {
        if (! $checked) {
            return;
        }

        $pdf->SetFont('Helvetica', 'B', 15);
        $pdf->SetTextColor(17, 24, 39);
        $pdf->SetXY($x, $y);
        $pdf->Cell(22, 27, 'X', 0, 0, 'C');
    }

    private function signature(Fpdi $pdf, mixed $dataUri, float $x, float $y, float $width, float $height): void
    {
        if (! is_string($dataUri) || ! preg_match('/^data:image\/png;base64,(.+)$/s', $dataUri, $matches)) {
            return;
        }

        $bytes = base64_decode($matches[1], true);

        if ($bytes === false) {
            return;
        }

        $temp = tempnam(sys_get_temp_dir(), 'travelflex-signature-');

        if ($temp === false) {
            throw new RuntimeException('A temporary signature file could not be created.');
        }

        try {
            file_put_contents($temp, $bytes);
            $dimensions = getimagesize($temp);
            $imageWidth = (float) ($dimensions[0] ?? 1);
            $imageHeight = (float) ($dimensions[1] ?? 1);
            $scale = min($width / $imageWidth, $height / $imageHeight);
            $renderWidth = $imageWidth * $scale;
            $renderHeight = $imageHeight * $scale;
            $pdf->Image($temp, $x + (($width - $renderWidth) / 2), $y + (($height - $renderHeight) / 2), $renderWidth, $renderHeight, 'PNG');
        } finally {
            @unlink($temp);
        }
    }

    private function date(mixed $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value, 'Africa/Lagos');
        } catch (\Throwable) {
            return null;
        }
    }

    private function money(mixed $value): string
    {
        return is_numeric($value) ? number_format((float) $value, 2, '.', ',') : '';
    }

    private function governmentIdLabel(mixed $value): string
    {
        return [
            'national_id' => 'National ID',
            'drivers_licence' => "Driver's Licence",
            'international_passport' => 'International Passport',
            'voters_card' => "Voter's Card",
        ][(string) $value] ?? (string) $value;
    }

    private function proposedTravelDate(TravelFlexApplication $application): string
    {
        $flight = $application->booking?->flight_snapshot ?? [];
        $value = data_get($flight, 'departDT')
            ?: data_get($flight, 'segments.0.departDT')
            ?: data_get($flight, 'multiLegs.0.departDT')
            ?: data_get($flight, 'multiLegs.0.segments.0.departDT')
            ?: data_get($flight, 'departDate')
            ?: data_get($flight, 'segments.0.departDate')
            ?: data_get($flight, 'multiLegs.0.segments.0.departDate');

        return $this->date($value)?->format('d/m/Y') ?? '';
    }

    private function travelPackage(TravelFlexApplication $application): string
    {
        $flight = $application->booking?->flight_snapshot ?? [];
        $multiLegs = data_get($flight, 'multiLegs', []);

        if (is_array($multiLegs) && $multiLegs !== []) {
            return collect($multiLegs)
                ->map(fn (array $leg): string => trim(($leg['from'] ?? '').' - '.($leg['to'] ?? '')))
                ->filter(fn (string $route): bool => $route !== ' - ')
                ->implode(' / ');
        }

        $segments = data_get($flight, 'segments', []);
        if (is_array($segments) && $segments !== []) {
            $first = $segments[0] ?? [];
            $last = $segments[array_key_last($segments)] ?? [];

            return trim(($first['from'] ?? '').' - '.($last['to'] ?? ''));
        }

        return $application->booking_ref ?: 'TravelFlex flight package';
    }

    private function pdfText(mixed $value): string
    {
        $text = preg_replace('/\s+/u', ' ', trim((string) $value)) ?? '';

        $converted = iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $text);

        return $converted === false ? '' : $converted;
    }
}
