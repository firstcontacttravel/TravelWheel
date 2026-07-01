<?php

namespace Tests\Feature;

use App\Mail\VisaPortalAccessCodeMail;
use App\Models\Country;
use App\Models\VisaApplication;
use App\Models\VisaProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class VisaCustomerPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_reference_and_one_time_email_code_unlock_the_portal(): void
    {
        Mail::fake();
        $application = $this->application();

        $this->post(route('visa.portal.code.request'), ['reference' => strtolower($application->reference), 'email' => strtoupper($application->contact_email)])
            ->assertRedirect(route('visa.portal.verify.form'));

        $code = null;
        Mail::assertQueued(VisaPortalAccessCodeMail::class, function ($mail) use (&$code, $application) {
            $code = $mail->code;

            return $mail->application->is($application);
        });

        $this->withSession(['visa_portal_pending_reference' => $application->reference])
            ->post(route('visa.portal.verify'), ['code' => $code])
            ->assertRedirect(route('visa.portal.show', $application));

        $this->get(route('visa.portal.show', $application))->assertOk()->assertSee($application->reference);
    }

    public function test_portal_rejects_an_email_that_does_not_match_the_application(): void
    {
        Mail::fake();
        $application = $this->application();

        $this->post(route('visa.portal.code.request'), ['reference' => $application->reference, 'email' => 'wrong@example.com'])
            ->assertSessionHasErrors('email');
        Mail::assertNothingQueued();
    }

    public function test_customer_can_upload_a_requested_document_and_download_only_issued_documents(): void
    {
        Storage::fake('local');
        $application = $this->application();
        $requirement = $application->product->requirements()->create(['name' => 'Passport bio page', 'category' => 'passport', 'scope' => 'per_application', 'requirement_state' => 'required', 'maximum_file_size_kb' => 5120, 'is_active' => true]);
        $documentRequest = $application->additionalDocumentRequests()->create(['visa_requirement_id' => $requirement->id, 'title' => 'Upload a clearer passport copy', 'status' => 'open']);
        $session = ["visa_portal_access.{$application->reference}" => now()->addHour()->timestamp];

        $this->withSession($session)->post(route('visa.portal.requests.upload', [$application, $documentRequest]), [
            'document' => UploadedFile::fake()->create('passport.pdf', 120, 'application/pdf'),
        ])->assertRedirect();
        $this->assertSame('submitted', $documentRequest->fresh()->status);
        Storage::disk('local')->assertExists($documentRequest->fresh()->path);

        $issuedPath = "visa-applications/{$application->reference}/issued/visa.pdf";
        Storage::disk('local')->put($issuedPath, 'issued visa');
        $document = $application->documents()->create(['visa_requirement_id' => $requirement->id, 'disk' => 'local', 'path' => $issuedPath, 'original_name' => 'visa.pdf', 'mime_type' => 'application/pdf', 'size' => 11, 'status' => 'issued']);
        $this->withSession($session)->get(route('visa.portal.documents.download', [$application, $document]))->assertOk();
    }

    private function application(): VisaApplication
    {
        $nationality = Country::query()->create(['alpha2' => 'GH', 'name' => 'Ghana']);
        $destination = Country::query()->create(['alpha2' => 'GB', 'name' => 'United Kingdom']);
        $product = VisaProduct::query()->create(['destination_country_id' => $destination->id, 'name' => 'Visitor visa', 'slug' => 'visitor-'.Str::random(6), 'family' => 'standard', 'category' => 'tourist', 'entry_type' => 'single', 'publication_status' => 'published', 'published_at' => now(), 'version' => 1]);
        $application = VisaApplication::query()->create([
            'reference' => (string) Str::ulid(), 'resume_token_hash' => hash('sha256', 'resume'), 'visa_product_id' => $product->id, 'product_version' => 1,
            'status' => 'submitted', 'current_step' => 8, 'completed_step' => 8, 'nationality_country_id' => $nationality->id, 'destination_country_id' => $destination->id,
            'arrival_date' => now()->addMonth(), 'departure_date' => now()->addMonths(2), 'adult_count' => 1, 'contact_email' => 'customer@example.com',
            'search_snapshot' => ['destination_name' => 'United Kingdom'], 'product_snapshot' => [], 'last_activity_at' => now(), 'expires_at' => now()->addDays(30),
        ]);
        $application->statusHistory()->create(['to_status' => 'submitted', 'actor_type' => 'system', 'reason' => 'Payment verified']);

        return $application;
    }
}
