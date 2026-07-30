<?php

namespace App\Services;

use App\Models\TravelFlexApplication;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use setasign\Fpdi\Fpdi;

class TravelFlexApplicationPdfService
{
    public const TEMPLATE_VERSION = 'fast-credit-tnpl-v2';

    private const OUTPUT_PAGE_WIDTH = 961.56;

    private const OUTPUT_PAGE_HEIGHT = 1583.04;

    public function generate(TravelFlexApplication $application, array $sensitive = []): string
    {
        $template = public_path('assets/fastcredit_tnpl_v2.pdf');

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
            $pdf->AddPage($size['orientation'], [self::OUTPUT_PAGE_WIDTH, self::OUTPUT_PAGE_HEIGHT]);
            $pdf->useTemplate($templateId, 0, 0, self::OUTPUT_PAGE_WIDTH, self::OUTPUT_PAGE_HEIGHT);

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

        $this->field($pdf, 59, 293, 222, 26, data_get($identity, 'title'));
        $this->field($pdf, 366, 293, 333, 26, data_get($identity, 'surname'));
        $this->field($pdf, 91, 330, 333, 26, data_get($identity, 'first_name'));
        $this->field($pdf, 500, 330, 333, 26, data_get($identity, 'other_name'));

        $marital = strtolower((string) data_get($identity, 'marital_status'));
        $this->check($pdf, 276, 366, $marital === 'single');
        $this->check($pdf, 344, 366, $marital === 'married');
        $this->check($pdf, 419, 366, $marital === 'divorced');
        $this->check($pdf, 495, 366, $marital === 'separated');
        $gender = strtolower((string) data_get($identity, 'gender'));
        $this->check($pdf, 769, 366, $gender === 'female');
        $this->check($pdf, 815, 366, $gender === 'male');

        $this->dateBoxes($pdf, $this->date(data_get($identity, 'date_of_birth')), [[106, 44], [153, 44], [202, 88]], 414, 16);
        $this->field($pdf, 159, 441, 244, 25, data_get($applicant, 'phone_primary'));
        $this->field($pdf, 535, 441, 244, 25, data_get($applicant, 'phone_secondary'));
        $this->field($pdf, 107, 479, 443, 25, data_get($applicant, 'email'), 8);

        $sector = $application->applicant_type === 'company'
            ? 'private'
            : strtolower((string) data_get($employment, 'sector'));
        $this->check($pdf, 115, 514, $sector === 'private');
        $this->check($pdf, 183, 514, $sector === 'public');
        $this->field($pdf, 384, 514, 285, 25, data_get($identity, 'passport_number'));
        $this->dateBoxes($pdf, $this->date(data_get($identity, 'passport_expiry_date')), [[673, 43], [721, 43], [769, 90]], 513, 26);

        $this->field($pdf, 80, 552, 443, 25, data_get($employment, 'occupation'));
        $this->field($pdf, 184, 589, 244, 25, data_get($employment, 'ippis_number'));
        $this->field($pdf, 123, 626, 665, 26, data_get($employment, 'employer_name'), 8);
        $this->field($pdf, 133, 663, 665, 26, data_get($employment, 'employer_address'), 7);
        $this->field($pdf, 151, 701, 333, 26, $this->money(data_get($bank, 'monthly_salary')));
        $this->field($pdf, 154, 738, 333, 26, data_get($bank, 'salary_account_number'));
        $this->field($pdf, 94, 775, 333, 26, data_get($bank, 'bank_name'));

        $platform = strtolower((string) data_get($identity, 'social_media_platform'));
        $this->check($pdf, 230, 812, $platform === 'facebook');
        $this->check($pdf, 332, 812, $platform === 'instagram');
        $this->check($pdf, 481, 812, $platform === 'x');
        $this->field($pdf, 137, 849, 665, 25, data_get($identity, 'social_media_handle'), 8);
        $this->field($pdf, 134, 886, 443, 26, data_get($applicant, 'home_address'), 7);
        $this->field($pdf, 111, 924, 222, 25, $this->governmentIdLabel(data_get($identity, 'government_id_type')));
        $this->field($pdf, 80, 961, 221, 26, data_get($employment, 'office_id', data_get($employment, 'staff_number')));

        $this->lineText($pdf, 144, 987, 278, 23, $this->proposedTravelDate($application));

        $this->field($pdf, 83, 1050, 333, 26, data_get($nextOfKin, 'surname'));
        $this->field($pdf, 545, 1050, 333, 26, data_get($nextOfKin, 'other_names'));
        $this->field($pdf, 91, 1088, 311, 26, data_get($nextOfKin, 'first_name'));
        $this->field($pdf, 98, 1125, 155, 25, data_get($nextOfKin, 'relationship'));
        $this->dateBoxes($pdf, $this->date(data_get($nextOfKin, 'date_of_birth')), [[106, 44], [153, 44], [202, 88]], 1172, 16);
        $nextGender = strtolower((string) data_get($nextOfKin, 'gender'));
        $this->check($pdf, 407, 1161, $nextGender === 'female');
        $this->check($pdf, 453, 1161, $nextGender === 'male');
        $this->field($pdf, 559, 1162, 155, 26, data_get($nextOfKin, 'title'));
        $this->field($pdf, 134, 1199, 443, 26, data_get($nextOfKin, 'residential_address'), 7);
        $this->field($pdf, 117, 1236, 244, 26, data_get($nextOfKin, 'phone_primary'));
        $this->field($pdf, 479, 1236, 244, 26, data_get($nextOfKin, 'phone_secondary'));
        $this->field($pdf, 107, 1273, 443, 26, data_get($nextOfKin, 'email'), 8);

        $this->field($pdf, 117, 1311, 333, 25, 'TravelWheel');
        $this->field($pdf, 113, 1348, 333, 25, $this->travelPackage($application), 7);
        $this->field($pdf, 104, 1385, 333, 26, $this->money(data_get($plan, 'loan_amount', data_get($plan, 'remaining_balance'))));
        $this->field($pdf, 92, 1422, 333, 26, data_get($plan, 'repayment_plan'));
        $this->signature($pdf, data_get($agreement, 'signature_image'), 162, 1496, 234, 34);
        $this->dateBoxes($pdf, $acceptedAt, [[718, 44], [766, 44], [814, 88]], 1516, 16);
    }

    private function fillAgreementPage(Fpdi $pdf, TravelFlexApplication $application): void
    {
        $agreement = $application->agreement_acceptance ?? [];
        $name = data_get($application->applicant_details, 'full_name');
        $acceptedAt = $this->date(data_get($agreement, 'accepted_at')) ?? now('Africa/Lagos');
        $date = $acceptedAt->format('d/m/Y');

        $this->lineText($pdf, 535, 1078, 222, 18, $name, 9);
        $this->signature($pdf, data_get($agreement, 'signature_image'), 530, 1101, 222, 18);
        $this->lineText($pdf, 540, 1116, 222, 18, $date, 9);

        $this->lineText($pdf, 740, 1154, 133, 18, $name, 8);
        $this->lineText($pdf, 595, 1257, 179, 18, $name, 8);
        $this->signature($pdf, data_get($agreement, 'signature_image'), 605, 1276, 159, 18);
        $this->lineText($pdf, 589, 1300, 186, 18, data_get($agreement, 'witness.full_name'), 8);
        $this->signature($pdf, data_get($agreement, 'witness.signature_image'), 596, 1319, 164, 18);
        $this->lineText($pdf, 532, 1343, 209, 18, $date, 9);
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
