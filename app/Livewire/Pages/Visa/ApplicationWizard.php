<?php

namespace App\Livewire\Pages\Visa;

use App\Models\Country;
use App\Models\VisaApplication;
use App\Models\VisaApplicationAnswer;
use App\Models\VisaApplicationDocument;
use App\Models\VisaApplicationServiceSelection;
use App\Services\VisaApplicationDraftService;
use App\Services\VisaFormWorkflow;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class ApplicationWizard extends Component
{
    use WithFileUploads;

    public VisaApplication $application;

    public int $step = 1;

    public ?string $contactEmail = null;

    public ?int $processingOptionId = null;

    public array $travelers = [];

    public array $answers = [];

    public array $serviceSelections = [];

    public array $uploads = [];

    public bool $declarationAccepted = false;

    public string $savedMessage = '';

    public function mount(VisaApplication $application, VisaApplicationDraftService $drafts): void
    {
        abort_unless($drafts->authorize($application), 403);
        $this->application = $application->load(['product.processingOptions', 'product.questions', 'product.requirements', 'product.optionalServices', 'travelers', 'answers', 'documents', 'serviceSelections']);
        $this->step = max(1, min(count($this->formFlow()), $application->current_step));
        $this->contactEmail = $application->contact_email;
        $this->processingOptionId = $application->visa_processing_option_id;
        $this->declarationAccepted = $application->declaration_accepted;
        $this->travelers = $application->travelers->mapWithKeys(fn ($traveler) => [$traveler->id => [
            'applicant_type' => $traveler->applicant_type,
            'title' => $traveler->title,
            'first_name' => $traveler->first_name,
            'middle_name' => $traveler->middle_name,
            'last_name' => $traveler->last_name,
            'sex' => $traveler->sex,
            'date_of_birth' => $traveler->date_of_birth?->format('Y-m-d'),
            'place_of_birth' => $traveler->place_of_birth,
            'nationality_country_id' => $traveler->nationality_country_id,
            'email' => $traveler->email,
            'phone' => $traveler->phone,
            'home_address' => $traveler->home_address,
            'passport_number' => $traveler->passport_number,
            'passport_type' => $traveler->passport_type,
            'passport_issued_at' => $traveler->passport_issued_at?->format('Y-m-d'),
            'passport_expires_at' => $traveler->passport_expires_at?->format('Y-m-d'),
            'passport_issuing_country_id' => $traveler->passport_issuing_country_id,
        ]])->all();
        $this->answers = $application->answers->mapWithKeys(fn ($answer) => [$this->answerKey($answer->visa_question_id, $answer->visa_traveler_id) => data_get($answer->value, 'answer')])->all();
        $this->serviceSelections = $application->serviceSelections->pluck('selected', 'visa_optional_service_id')->map(fn ($selected) => (bool) $selected)->all();
    }

    public function updated(string $property): void
    {
        if (str_starts_with($property, 'uploads.')) {
            return;
        }

        $rules = $this->rulesForStep($this->step);
        if (array_key_exists($property, $rules)) {
            $this->validateOnly($property, $rules, $this->validationMessages(), $this->validationAttributes());
        }

        $this->persist(false);
        $this->savedMessage = 'Draft saved';
    }

    public function next(VisaApplicationDraftService $drafts): void
    {
        $this->validateStep();
        $this->persist(true);
        $this->step = min(count($this->formFlow()), $this->step + 1);
        $drafts->touch($this->application, $this->step, $this->step - 1);
        $this->savedMessage = 'Step saved';
        $this->dispatch('visa-step-changed');
    }

    public function previous(VisaApplicationDraftService $drafts): void
    {
        if ($this->application->status === 'awaiting_payment') {
            $this->step = count($this->formFlow());

            return;
        }
        $this->persist(false);
        $this->step = max(1, $this->step - 1);
        $drafts->touch($this->application, $this->step, $this->application->completed_step);
        $this->dispatch('visa-step-changed');
    }

    public function goTo(int $step, VisaApplicationDraftService $drafts): void
    {
        abort_if($this->application->status === 'awaiting_payment' && $step !== count($this->formFlow()), 422, 'Application details are locked while a quote is active.');
        abort_if($step < 1 || $step > min(count($this->formFlow()), $this->application->completed_step + 1), 422);
        $this->persist(false);
        $this->step = $step;
        $drafts->touch($this->application, $step, $this->application->completed_step);
        $this->dispatch('visa-step-changed');
    }

    private function validateStep(): void
    {
        $stepKey = $this->currentStepKey();
        if (in_array($stepKey, ['trip', 'travelers', 'passports', 'questions', 'services', 'review'], true) || $this->questionsForCurrentStep()->isNotEmpty()) {
            $rules = $this->rulesForStep($this->step);
            if ($rules !== []) {
                $this->validate($rules, $this->validationMessages(), $this->validationAttributes());
            }
        }
        if ($stepKey === 'documents') {
            $this->storeDocuments();
            foreach ($this->requiredDocumentSlots() as $slot) {
                if (! $this->application->documents()->where('visa_requirement_id', $slot['requirement_id'])->where('visa_traveler_id', $slot['traveler_id'])->exists()) {
                    $this->addError('uploads.'.$slot['key'], 'This document is required.');
                }
            }
            if ($this->getErrorBag()->isNotEmpty()) {
                throw \Illuminate\Validation\ValidationException::withMessages($this->getErrorBag()->toArray());
            }
        }
    }

    private function rulesForStep(int $step): array
    {
        $stepKey = $this->formFlow()[$step - 1]['key'] ?? null;

        if ($stepKey === 'trip') {
            return [
                'contactEmail' => ['required', 'email:rfc', 'max:255'],
                'processingOptionId' => ['required', 'integer', Rule::exists('visa_processing_options', 'id')->where(fn ($query) => $query->where('visa_product_id', $this->application->visa_product_id)->where('is_active', true))],
            ];
        }

        $rules = [];
        if ($stepKey === 'travelers') {
            foreach ($this->application->travelers as $traveler) {
                $id = $traveler->id;
                $fieldRules = [
                    'applicant_type' => ['required', Rule::in($traveler->traveler_type === 'adult' ? ['individual', 'company'] : ['minor_nigerian', 'minor_foreign'])],
                    'first_name' => ['required', 'string', 'max:100'],
                    'middle_name' => ['nullable', 'string', 'max:100'],
                    'last_name' => ['required', 'string', 'max:100'],
                    'sex' => ['required', Rule::in(['male', 'female'])],
                    'date_of_birth' => ['required', 'date', 'before:today'],
                    'place_of_birth' => ['required', 'string', 'max:150'],
                    'nationality_country_id' => ['required', Rule::exists('countries', 'id')->where('is_active', true)],
                    'email' => ['nullable', 'email:rfc', 'max:255'],
                    'phone' => ['required', 'string', 'regex:/^\+?[0-9 ()-]{7,40}$/'],
                    'home_address' => ['required', 'string', 'min:5', 'max:1000'],
                ];
                foreach ($this->enabledTravelerFields() as $field) {
                    $rules["travelers.$id.$field"] = $fieldRules[$field];
                }
            }
        }

        if ($stepKey === 'passports') {
            foreach ($this->application->travelers as $traveler) {
                $id = $traveler->id;
                $fieldRules = [
                    'passport_number' => ['required', 'string', 'regex:/^[A-Za-z0-9]{5,50}$/'],
                    'passport_type' => ['required', Rule::in(['ordinary', 'official', 'diplomatic'])],
                    'passport_issued_at' => ['required', 'date', 'before_or_equal:today'],
                    'passport_expires_at' => ['required', 'date', 'after:'.$this->application->departure_date->format('Y-m-d')],
                    'passport_issuing_country_id' => ['required', Rule::exists('countries', 'id')->where('is_active', true)],
                ];
                foreach ($this->enabledPassportFields() as $field) {
                    $rules["travelers.$id.$field"] = $fieldRules[$field];
                }
            }
        }

        foreach ($this->questionsForStep($stepKey) as $question) {
                $travelerIds = $question->scope === 'traveler' ? $this->application->travelers->pluck('id')->all() : [null];
                foreach ($travelerIds as $travelerId) {
                    $field = 'answers.'.$this->answerKey($question->id, $travelerId);
                    $questionRules = [$question->is_required ? 'required' : 'nullable'];
                    $questionRules = array_merge($questionRules, match ($question->input_type) {
                        'email' => ['email:rfc', 'max:255'],
                        'number' => ['numeric'],
                        'date' => ['date'],
                        'select' => [Rule::in(collect($question->options ?? [])->map(fn ($option) => is_array($option) ? ($option['value'] ?? $option['label'] ?? null) : $option)->filter()->all())],
                        'checkbox' => $question->is_required ? ['accepted'] : [],
                        default => ['string', 'max:5000'],
                    });
                    $rules[$field] = array_merge($questionRules, $question->validation_rules ?? []);
                }
        }

        if ($stepKey === 'review') {
            $rules['declarationAccepted'] = ['accepted'];
        }

        return $rules;
    }

    private function validationMessages(): array
    {
        return [
            'required' => 'This field is required.',
            'accepted' => 'Please confirm this before continuing.',
            'email' => 'Enter a valid email address.',
            'regex' => 'Enter a valid :attribute.',
            'date' => 'Enter a valid date.',
            'before' => 'The :attribute must be before today.',
            'before_or_equal' => 'The :attribute cannot be in the future.',
            'after' => 'The :attribute must be after your planned departure date.',
            'exists' => 'Select a valid :attribute.',
            'in' => 'Select a valid :attribute.',
        ];
    }

    protected function validationAttributes(): array
    {
        $attributes = ['contactEmail' => 'email address', 'processingOptionId' => 'processing option', 'declarationAccepted' => 'declaration'];
        foreach ($this->application->travelers as $traveler) {
            foreach (['applicant_type' => 'application profile', 'first_name' => 'first name', 'last_name' => 'last name', 'sex' => 'sex', 'date_of_birth' => 'date of birth', 'place_of_birth' => 'place of birth', 'nationality_country_id' => 'nationality', 'email' => 'email address', 'phone' => 'phone number', 'home_address' => 'home address', 'passport_number' => 'passport number', 'passport_type' => 'passport type', 'passport_issued_at' => 'passport issue date', 'passport_expires_at' => 'passport expiry date', 'passport_issuing_country_id' => 'issuing country'] as $key => $label) {
                $attributes["travelers.{$traveler->id}.$key"] = $label;
            }
        }
        foreach ($this->application->product->questions as $question) {
            $travelerIds = $question->scope === 'traveler' ? $this->application->travelers->pluck('id')->all() : [null];
            foreach ($travelerIds as $travelerId) {
                $attributes['answers.'.$this->answerKey($question->id, $travelerId)] = strtolower($question->label);
            }
        }

        return $attributes;
    }

    private function persist(bool $validated): void
    {
        $this->application->forceFill([
            'contact_email' => $this->contactEmail,
            'visa_processing_option_id' => $this->processingOptionId,
            'declaration_accepted' => $this->declarationAccepted,
            'declaration_accepted_at' => $this->declarationAccepted ? ($this->application->declaration_accepted_at ?: now()) : null,
            'last_activity_at' => now(), 'expires_at' => now()->addDays(30),
        ])->save();

        foreach ($this->application->travelers as $traveler) {
            $data = $this->travelers[$traveler->id] ?? [];
            $traveler->fill(array_intersect_key($data, array_flip($traveler->getFillable())))->save();
        }

        foreach ($this->application->product->questions as $question) {
            $travelerIds = $question->scope === 'traveler' ? $this->application->travelers->pluck('id')->all() : [null];
            foreach ($travelerIds as $travelerId) {
                $key = $this->answerKey($question->id, $travelerId);
                if (array_key_exists($key, $this->answers)) {
                    VisaApplicationAnswer::query()->updateOrCreate(['visa_application_id' => $this->application->id, 'visa_traveler_id' => $travelerId, 'visa_question_id' => $question->id], ['value' => ['answer' => $this->answers[$key]]]);
                }
            }
        }

        foreach ($this->application->product->optionalServices as $service) {
            VisaApplicationServiceSelection::query()->updateOrCreate(['visa_application_id' => $this->application->id, 'visa_optional_service_id' => $service->id], ['selected' => (bool) ($this->serviceSelections[$service->id] ?? false)]);
        }

        if ($validated) {
            $this->application->completed_step = max($this->application->completed_step, $this->step);
            $this->application->save();
        }
    }

    private function storeDocuments(): void
    {
        foreach ($this->uploads as $key => $upload) {
            if (! $upload) {
                continue;
            }
            [$requirementId, $scope] = explode('_', $key, 2);
            $travelerId = $scope === 'app' ? null : (int) $scope;
            $requirement = $this->application->product->requirements->firstWhere('id', (int) $requirementId);
            abort_unless($requirement, 422);

            // ✅ Remove 'max' from validate — it calls getSize() via Flysystem
            $this->validate(["uploads.$key" => ['file', 'mimes:pdf,jpg,jpeg,png']]);

            // ✅ Check file size using PHP native filesize() directly
            $realPath = $upload->getRealPath();
            $fileSizeBytes = $realPath ? filesize($realPath) : 0;
            $maxBytes = min(10240, $requirement->maximum_file_size_kb) * 1024;

            if ($fileSizeBytes > $maxBytes) {
                $this->addError("uploads.$key", 'File must not exceed '.min(10240, $requirement->maximum_file_size_kb).' KB.');
                throw \Illuminate\Validation\ValidationException::withMessages($this->getErrorBag()->toArray());
            }

            $existing = VisaApplicationDocument::query()->where([
                'visa_application_id' => $this->application->id,
                'visa_traveler_id' => $travelerId,
                'visa_requirement_id' => $requirement->id,
            ])->first();

            if ($existing) {
                Storage::disk($existing->disk)->delete($existing->path);
            }

            $path = $upload->store("visa-applications/{$this->application->reference}/documents", 'local');

            VisaApplicationDocument::query()->updateOrCreate(
                [
                    'visa_application_id' => $this->application->id,
                    'visa_traveler_id' => $travelerId,
                    'visa_requirement_id' => $requirement->id,
                ],
                [
                    'disk' => 'local',
                    'path' => $path,
                    'original_name' => $upload->getClientOriginalName(),
                    'mime_type' => $upload->getClientMimeType() ?: 'application/octet-stream', // ✅ client mime, not Flysystem
                    'size' => $fileSizeBytes,  // ✅ native filesize, not Flysystem
                    'status' => 'uploaded',
                ]
            );
        }

        $this->uploads = [];
        $this->application->load('documents');
    }

    public function requiredDocumentSlots(): array
    {
        return collect($this->documentSlots())->where('required', true)->values()->all();
    }

    public function documentSlots(): array
    {
        return $this->application->product->requirements->flatMap(function ($requirement) {
            if ($requirement->optional_service_code) {
                $service = $this->application->product->optionalServices->firstWhere('code', $requirement->optional_service_code);
                if (! $service || ! ($this->serviceSelections[$service->id] ?? false)) {
                    return [];
                }
            }

            if ($requirement->scope === 'traveler') {
                return $this->application->travelers
                    ->filter(fn ($traveler) => $this->requirementApplies($requirement->conditions ?? [], $traveler))
                    ->map(fn ($traveler) => ['key' => $requirement->id.'_'.$traveler->id, 'requirement_id' => $requirement->id, 'traveler_id' => $traveler->id, 'label' => $requirement->name.' — '.$this->travelerLabel($traveler), 'required' => in_array($requirement->requirement_state, ['required', 'conditional'], true), 'guidance' => $requirement->guidance ?: $requirement->description]);
            }

            return $this->requirementAppliesToApplication($requirement->conditions ?? [])
                ? [['key' => $requirement->id.'_app', 'requirement_id' => $requirement->id, 'traveler_id' => null, 'label' => $requirement->name, 'required' => in_array($requirement->requirement_state, ['required', 'conditional'], true), 'guidance' => $requirement->guidance ?: $requirement->description]]
                : [];
        })->values()->all();
    }

    private function requirementApplies(array $conditions, $traveler): bool
    {
        return collect($conditions)->every(function (mixed $expected, string $key) use ($traveler): bool {
            $actual = $key === 'applicant_type'
                ? data_get($this->travelers, $traveler->id.'.applicant_type', $traveler->applicant_type)
                : data_get($traveler, $key);

            return is_array($expected) ? in_array($actual, $expected, true) : $actual === $expected;
        });
    }

    private function requirementAppliesToApplication(array $conditions): bool
    {
        if (array_key_exists('applicant_type', $conditions)) {
            return $this->application->travelers->contains(fn ($traveler) => $this->requirementApplies($conditions, $traveler));
        }

        return $conditions === [];
    }

    public function travelerLabel($traveler): string
    {
        $typeCount = $this->application->travelers->where('traveler_type', $traveler->traveler_type)->count();

        return ucfirst($traveler->traveler_type).($typeCount > 1 ? ' '.$traveler->position : '');
    }

    private function answerKey(int $questionId, ?int $travelerId = null): string
    {
        return $questionId.'_'.($travelerId ?: 'app');
    }

    public function formFlow(): array
    {
        return app(VisaFormWorkflow::class)->applicationFlow(
            $this->application->form_configuration,
            $this->application->product->questions->where('is_active', true)->isNotEmpty(),
            $this->application->product->optionalServices->where('is_active', true)->isNotEmpty(),
            $this->application->product->requirements->where('is_active', true)->isNotEmpty(),
        );
    }

    public function currentStepKey(): string
    {
        return $this->formFlow()[$this->step - 1]['key'] ?? 'trip';
    }

    public function enabledTravelerFields(): array
    {
        return app(VisaFormWorkflow::class)->normalize($this->application->form_configuration)['traveler_fields'];
    }

    public function enabledPassportFields(): array
    {
        return app(VisaFormWorkflow::class)->normalize($this->application->form_configuration)['passport_fields'];
    }

    public function questionsForCurrentStep(): \Illuminate\Support\Collection
    {
        return $this->questionsForStep($this->currentStepKey());
    }

    private function questionsForStep(?string $stepKey): \Illuminate\Support\Collection
    {
        return $stepKey === 'questions'
            ? $this->application->product->questions->where('is_active', true)
            : collect();
    }

    public function render()
    {
        return view('livewire.pages.visa.application-wizard', ['countries' => Country::query()->where('is_active', true)->orderBy('name')->get()]);
    }
}
