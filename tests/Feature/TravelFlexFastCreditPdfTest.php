<?php

namespace Tests\Feature;

use App\Models\TravelFlexApplication;
use App\Services\TravelFlexApplicationPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use Tests\TestCase;

class TravelFlexFastCreditPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_and_stores_an_immutable_two_page_fast_credit_form(): void
    {
        Storage::fake('local');
        $signature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

        $application = TravelFlexApplication::create([
            'booking_ref' => 'TW-FAST-CREDIT-001',
            'applicant_type' => 'company',
            'applicant_details' => [
                'full_name' => 'Mr Chidi Okafor',
                'email' => 'chidi@example.com',
                'phone_primary' => '08012345678',
                'phone_secondary' => '08087654321',
                'home_address' => '12 Broad Street, Lagos',
            ],
            'bvn_metadata' => ['last_four' => '5678'],
            'identity_details' => [
                'nin' => '12345678901',
                'title' => 'Mr',
                'surname' => 'Okafor',
                'first_name' => 'Chidi',
                'marital_status' => 'married',
                'gender' => 'male',
                'date_of_birth' => '1988-04-12',
                'passport_number' => 'A12345678',
                'passport_expiry_date' => '2030-10-20',
                'government_id_type' => 'national_id',
                'social_media_platform' => 'instagram',
                'social_media_handle' => '@chidi',
            ],
            'employment_details' => [
                'employer_name' => 'Okafor Ventures Limited',
                'employer_address' => '25 Marina Road, Lagos',
                'occupation' => 'Managing Director',
                'job_description' => 'Business owner',
                'office_id' => 'OVL-001',
                'sector' => 'private',
            ],
            'bank_details' => [
                'monthly_salary' => 850000,
                'salary_account_number' => '0123456789',
                'bank_name' => 'Example Bank',
            ],
            'next_of_kin_details' => [
                'surname' => 'Okafor',
                'first_name' => 'Ada',
                'relationship' => 'Spouse',
                'date_of_birth' => '1990-01-15',
                'gender' => 'female',
                'title' => 'Mrs',
                'residential_address' => '12 Broad Street, Lagos',
                'phone_primary' => '08055555555',
                'email' => 'ada@example.com',
            ],
            'agreement_acceptance' => [
                'digital_signature' => 'Mr Chidi Okafor',
                'signature_image' => $signature,
                'accepted_at' => '2026-07-22T10:00:00+01:00',
                'witness' => [
                    'full_name' => 'Ada Okafor',
                    'signature_image' => $signature,
                ],
            ],
            'repayment_plan' => [
                'loan_amount' => 700000,
                'repayment_plan' => '3 Months',
            ],
        ]);

        $stored = app(TravelFlexApplicationPdfService::class)->generateAndStore($application, [
            'bvn' => '12345675678',
        ]);

        Storage::disk('local')->assertExists($stored->generated_application_path);
        $bytes = Storage::disk('local')->get($stored->generated_application_path);
        $this->assertStringStartsWith('%PDF-', $bytes);
        $this->assertSame(hash('sha256', $bytes), $stored->generated_application_sha256);
        $this->assertSame(TravelFlexApplicationPdfService::TEMPLATE_VERSION, $stored->generated_application_version);

        $reader = new Fpdi;
        $this->assertSame(2, $reader->setSourceFile(Storage::disk('local')->path($stored->generated_application_path)));
    }

    public function test_business_owners_are_required_to_supply_the_unified_fast_credit_fields(): void
    {
        $response = $this->post(route('flights.travelflex.submit-application'), [
            'applicant_type' => 'company',
        ]);

        $response->assertSessionHasErrors([
            'bvn',
            'nin',
            'marital_status',
            'government_id_type',
            'employer_name',
            'office_id',
            'monthly_salary',
            'next_of_kin_surname',
            'witness_full_name',
            'witness_signature_image',
        ]);
        $response->assertSessionDoesntHaveErrors([
            'company_name',
            'company_rc_number',
            'company_bank_name',
        ]);
    }
}
