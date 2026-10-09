<?php

namespace App\Livewire\Pages\Partners;

use App\Mail\VendorApplicationReceivedMail;
use App\Mail\VendorApplicationSubmittedMail;
use App\Models\VendorApplication;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * The digital vendor / partner registration form we send to suppliers.
 * Seven steps; each is validated before moving on, and nothing is saved
 * until the declaration is signed on the last one.
 */
class VendorRegistration extends Component
{
    use WithFileUploads;

    public const STEPS = [
        1 => 'Company',
        2 => 'Contacts',
        3 => 'Services',
        4 => 'Service details',
        5 => 'Bookings & payment',
        6 => 'Documents',
        7 => 'Declaration',
    ];

    private const UPLOAD_RULE = 'file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:10240';

    public int $step = 1;

    public array $form = [
        'registered_name' => '', 'trading_name' => '', 'registration_number' => '', 'tax_id' => '',
        'country' => 'Nigeria', 'address' => '', 'website' => '', 'business_email' => '',
        'business_phone' => '', 'year_established' => '', 'locations_served' => '',
        'contact_name' => '', 'contact_title' => '', 'contact_email' => '', 'contact_phone' => '',
        'operations_name' => '', 'operations_email' => '', 'operations_phone' => '',
        'accounts_name' => '', 'accounts_email' => '', 'accounts_phone' => '',
        'business_types' => [], 'business_type_other' => '', 'services' => [], 'service_other' => '',
        'works_with_other_platforms' => '', 'other_platforms_details' => '',
        'booking_channels' => [], 'booking_email' => '', 'confirmation_time' => '',
        'rate_model' => '', 'settlement_currency' => 'NGN', 'payment_terms' => '',
        'bank_name' => '', 'account_name' => '', 'account_number' => '', 'references' => '',
        'declarant_name' => '', 'declarant_title' => '',
    ];

    /** service key => field key => answer */
    public array $details = [];

    /** service key => true once its acknowledgement is ticked */
    public array $acknowledged = [];

    /** document type => uploaded file */
    public array $uploads = [];

    /** document type => expiry date */
    public array $expiries = [];

    public bool $agree = false;

    public bool $consent = false;

    /** Honeypot: people never see this field, bots fill it */
    public string $company_fax = '';

    public function next(): void
    {
        $this->validateStep($this->step);
        $this->step = min($this->step + 1, count(self::STEPS));
        $this->prepareServiceDetails();
        $this->dispatch('vendor-step-changed');
    }

    /**
     * Tick-box questions must start as empty lists. Bound to anything else,
     * Livewire treats the whole group as one on/off switch, so ticking one
     * box ticks them all.
     */
    private function prepareServiceDetails(): void
    {
        foreach ($this->selectedServices() as $service => $config) {
            foreach ($config['fields'] as $field => $def) {
                if ($def['type'] === 'checkboxes' && ! is_array($this->details[$service][$field] ?? null)) {
                    $this->details[$service][$field] = [];
                }
            }
        }
    }

    public function back(): void
    {
        $this->step = max($this->step - 1, 1);
        $this->dispatch('vendor-step-changed');
    }

    public function goTo(int $step): void
    {
        // Only backwards: forward moves must pass each step's checks
        if ($step >= 1 && $step < $this->step) {
            $this->step = $step;
            $this->prepareServiceDetails();
            $this->dispatch('vendor-step-changed');
        }
    }

    public function removeUpload(string $type): void
    {
        unset($this->uploads[$type]);
    }

    public function submit()
    {
        foreach (array_keys(self::STEPS) as $step) {
            try {
                $this->validateStep($step);
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->step = $step;
                throw $e;
            }
        }

        if ($this->company_fax !== '') {
            return redirect()->route('partners.register.submitted');
        }

        $key = 'vendor-application:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('form.declarant_name', 'Too many applications from this connection. Please try again in an hour.');

            return null;
        }
        RateLimiter::hit($key, 3600);

        $application = $this->store();

        $this->sendEmails($application);

        session()->flash('vendor_application_reference', $application->reference);

        return redirect()->route('partners.register.submitted');
    }

    /** Every document this vendor must or may upload, deduplicated by type. */
    public function documentList(): array
    {
        $list = [];

        foreach (config('vendor_onboarding.company_documents') as $type => $doc) {
            $list[$type] = $doc + ['service' => null, 'expires' => false];
        }

        foreach ($this->selectedServices() as $service => $config) {
            foreach ($config['documents'] ?? [] as $type => $doc) {
                if (isset($list[$type])) {
                    // Same document for two services (e.g. fleet list): one upload, required if either needs it
                    $list[$type]['required'] = $list[$type]['required'] || ($doc['required'] ?? false);

                    continue;
                }
                $list[$type] = $doc + ['service' => $service, 'expires' => false];
            }
        }

        return $list;
    }

    /** @return array<string, array> the chosen services' config, in the form's order */
    public function selectedServices(): array
    {
        return Arr::only(config('vendor_onboarding.services'), $this->form['services']);
    }

    private function validateStep(int $step): void
    {
        [$rules, $attributes] = $this->rulesFor($step);

        $messages = [
            'agree.accepted' => 'Please confirm the declaration.',
            'consent.accepted' => 'Please give consent for us to verify your information.',
            'acknowledged.*.accepted' => 'Please tick this acknowledgement.',
            'expiries.*.after' => 'This document has expired. Please upload a current one.',
            'form.services.required' => 'Choose at least one service you want to offer.',
            'form.booking_channels.required' => 'Choose at least one way to receive bookings.',
        ];

        $this->validate($rules, $messages, $attributes);

        if ($step === 3 && empty($this->form['business_types']) && blank($this->form['business_type_other'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'form.business_types' => 'Choose your business type, or describe it under "Other".',
            ]);
        }
    }

    /** @return array{0: array, 1: array} rules and attribute names for one step */
    private function rulesFor(int $step): array
    {
        $optionalText = 'nullable|string|max:255';

        return match ($step) {
            1 => [[
                'form.registered_name' => 'required|string|max:255',
                'form.trading_name' => $optionalText,
                'form.registration_number' => 'required|string|max:100',
                'form.tax_id' => 'nullable|string|max:100',
                'form.country' => 'required|string|max:100',
                'form.address' => 'required|string|max:1000',
                'form.website' => $optionalText,
                'form.business_email' => 'required|email|max:255',
                'form.business_phone' => 'required|string|max:50',
                'form.year_established' => 'nullable|digits:4|integer|min:1900|max:'.date('Y'),
                'form.locations_served' => 'required|string|max:2000',
            ], [
                'form.registered_name' => 'registered company name',
                'form.registration_number' => 'registration number',
                'form.business_email' => 'business email',
                'form.business_phone' => 'business phone',
                'form.year_established' => 'year established',
                'form.locations_served' => 'countries / locations served',
                'form.address' => 'business address',
            ]],

            2 => [[
                'form.contact_name' => 'required|string|max:255',
                'form.contact_title' => 'required|string|max:255',
                'form.contact_email' => 'required|email|max:255',
                'form.contact_phone' => 'required|string|max:50',
                'form.operations_name' => 'required|string|max:255',
                'form.operations_email' => 'nullable|email|max:255',
                'form.operations_phone' => 'required|string|max:50',
                'form.accounts_name' => $optionalText,
                'form.accounts_email' => 'nullable|email|max:255',
                'form.accounts_phone' => 'nullable|string|max:50',
            ], [
                'form.contact_name' => 'full name',
                'form.contact_title' => 'position / job title',
                'form.contact_email' => 'email',
                'form.contact_phone' => 'phone / WhatsApp',
                'form.operations_name' => 'operations contact name',
                'form.operations_email' => 'operations email',
                'form.operations_phone' => 'operations phone',
                'form.accounts_email' => 'accounts email',
            ]],

            3 => [[
                'form.business_types' => 'array',
                'form.business_types.*' => Rule::in(array_keys(config('vendor_onboarding.business_types'))),
                'form.business_type_other' => $optionalText,
                'form.services' => 'required|array|min:1',
                'form.services.*' => Rule::in(array_keys(config('vendor_onboarding.services'))),
                'form.service_other' => $optionalText,
                'form.works_with_other_platforms' => 'required|in:yes,no',
                'form.other_platforms_details' => 'required_if:form.works_with_other_platforms,yes|nullable|string|max:2000',
            ], [
                'form.works_with_other_platforms' => 'this question',
                'form.other_platforms_details' => 'details',
            ]],

            4 => $this->serviceDetailRules(),

            5 => [[
                'form.booking_channels' => 'required|array|min:1',
                'form.booking_channels.*' => Rule::in(array_keys(config('vendor_onboarding.booking_channels'))),
                'form.booking_email' => 'required|email|max:255',
                'form.confirmation_time' => $optionalText,
                'form.rate_model' => ['required', Rule::in(array_keys(config('vendor_onboarding.rate_models')))],
                'form.settlement_currency' => ['required', Rule::in(array_keys(config('vendor_onboarding.settlement_currencies')))],
                'form.payment_terms' => $optionalText,
                'form.bank_name' => 'required|string|max:255',
                'form.account_name' => 'required|string|max:255',
                'form.account_number' => 'required|string|max:34',
                'form.references' => 'nullable|string|max:3000',
            ], [
                'form.booking_email' => 'booking email',
                'form.rate_model' => 'rate model',
                'form.settlement_currency' => 'settlement currency',
                'form.bank_name' => 'bank name',
                'form.account_name' => 'account name',
                'form.account_number' => 'account number',
            ]],

            6 => $this->documentRules(),

            7 => [[
                'form.declarant_name' => 'required|string|max:255',
                'form.declarant_title' => 'required|string|max:255',
                'agree' => 'accepted',
                'consent' => 'accepted',
            ], [
                'form.declarant_name' => 'authorised representative name',
                'form.declarant_title' => 'position / job title',
            ]],

            default => [[], []],
        };
    }

    private function serviceDetailRules(): array
    {
        $rules = [];
        $attributes = [];

        foreach ($this->selectedServices() as $service => $config) {
            foreach ($config['fields'] as $field => $def) {
                $key = "details.$service.$field";
                $required = ($def['required'] ?? false) ? 'required' : 'nullable';
                $attributes[$key] = strtolower($def['label']);

                $rules[$key] = match ($def['type']) {
                    'checkboxes' => [$required, 'array'],
                    'yesno' => [$required, 'in:yes,no'],
                    'textarea' => [$required, 'string', 'max:3000'],
                    default => [$required, 'string', 'max:255'],
                };

                if ($def['type'] === 'checkboxes') {
                    $rules["$key.*"] = Rule::in($def['options']);
                }
            }

            if (isset($config['acknowledgement'])) {
                $rules["acknowledged.$service"] = 'accepted';
            }
        }

        return [$rules, $attributes];
    }

    private function documentRules(): array
    {
        $rules = [];
        $attributes = [];

        foreach ($this->documentList() as $type => $doc) {
            $rules["uploads.$type"] = ($doc['required'] ? 'required|' : 'nullable|').self::UPLOAD_RULE;
            $attributes["uploads.$type"] = strtolower($doc['label']);

            if ($doc['expires']) {
                $rules["expiries.$type"] = 'nullable|date|after:today';
                $attributes["expiries.$type"] = 'expiry date';
            }
        }

        return [$rules, $attributes];
    }

    private function store(): VendorApplication
    {
        $reference = VendorApplication::newReference();
        $stored = [];

        try {
            return DB::transaction(function () use ($reference, &$stored) {
                $answers = [];
                foreach ($this->selectedServices() as $service => $config) {
                    $answers[$service] = Arr::only($this->details[$service] ?? [], array_keys($config['fields']));
                }

                $form = $this->form;
                $application = VendorApplication::create([
                    'reference' => $reference,
                    'status' => 'received',
                    ...Arr::except($form, ['works_with_other_platforms', 'declarant_name', 'declarant_title']),
                    'business_types' => array_values($form['business_types']),
                    'services' => array_values(array_keys($this->selectedServices())),
                    'service_details' => $answers,
                    'works_with_other_platforms' => $form['works_with_other_platforms'] === 'yes',
                    'other_platforms_details' => $form['works_with_other_platforms'] === 'yes' ? $form['other_platforms_details'] : null,
                    'booking_channels' => array_values($form['booking_channels']),
                    'declarant_name' => $form['declarant_name'],
                    'declarant_title' => $form['declarant_title'],
                    'declared_at' => now(),
                    'submitted_ip' => request()->ip(),
                ]);

                foreach ($this->documentList() as $type => $doc) {
                    $file = $this->uploads[$type] ?? null;
                    if (! $file instanceof TemporaryUploadedFile) {
                        continue;
                    }

                    $path = $file->storeAs(
                        "vendor-applications/$reference",
                        $type.'-'.bin2hex(random_bytes(4)).'.'.strtolower($file->getClientOriginalExtension()),
                        'local',
                    );
                    $stored[] = $path;

                    $application->documents()->create([
                        'service' => $doc['service'],
                        'type' => $type,
                        'label' => $doc['label'],
                        'disk' => 'local',
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                        'expires_on' => $doc['expires'] ? ($this->expiries[$type] ?? null ?: null) : null,
                    ]);
                }

                return $application;
            });
        } catch (\Throwable $e) {
            // Don't leave files behind for an application that was never saved
            Storage::disk('local')->delete($stored);
            throw $e;
        }
    }

    private function sendEmails(VendorApplication $application): void
    {
        // A mail outage mustn't lose an application that is already saved
        try {
            Mail::to($application->contact_email)
                ->cc(strcasecmp($application->business_email, $application->contact_email) === 0 ? [] : [$application->business_email])
                ->send(new VendorApplicationReceivedMail($application));
        } catch (\Throwable $e) {
            Log::error('Vendor application acknowledgement email failed', ['reference' => $application->reference, 'error' => $e->getMessage()]);
        }

        try {
            Mail::to(config('vendor_onboarding.notify_email'))->send(new VendorApplicationSubmittedMail($application));
        } catch (\Throwable $e) {
            Log::error('Vendor application team notification failed', ['reference' => $application->reference, 'error' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.pages.partners.vendor-registration', [
            'steps' => self::STEPS,
            'businessTypes' => config('vendor_onboarding.business_types'),
            'services' => config('vendor_onboarding.services'),
            'bookingChannels' => config('vendor_onboarding.booking_channels'),
            'rateModels' => config('vendor_onboarding.rate_models'),
            'currencies' => config('vendor_onboarding.settlement_currencies'),
            'documents' => $this->step === 6 ? $this->documentList() : [],
        ])->layout('layouts.app', ['title' => 'Partner Registration - TravelWheel']);
    }
}
