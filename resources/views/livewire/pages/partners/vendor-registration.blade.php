<div class="vnd-page">
    <style>
        .vnd-page { --vnd-main: rgba(13, 24, 131, 1); --vnd-green: #0d9c53; padding: 155px 0 60px; background: #f5f7fb; }
        .vnd-wrap { max-width: 920px; margin: 0 auto; padding: 0 16px; }
        .vnd-head h1 { color: var(--vnd-main); font-size: 1.9rem; font-weight: 800; margin-bottom: .35rem; }
        .vnd-head p { color: #555; margin-bottom: 1.5rem; }
        .vnd-steps { display: flex; gap: 6px; list-style: none; padding: 0; margin: 0 0 1.25rem; overflow-x: auto; }
        .vnd-steps li { flex: 1 0 auto; min-width: 92px; text-align: center; font-size: .78rem; color: #8a8fa3; }
        .vnd-steps li .bar { height: 5px; border-radius: 4px; background: #dfe3ee; margin-bottom: 6px; }
        .vnd-steps li.done .bar { background: var(--vnd-green); }
        .vnd-steps li.current .bar { background: var(--vnd-main); }
        .vnd-steps li.current { color: var(--vnd-main); font-weight: 700; }
        .vnd-steps li.done button { color: var(--vnd-green); }
        .vnd-steps button { all: unset; cursor: pointer; }
        .vnd-card { background: #fff; border-radius: 14px; box-shadow: 0 4px 18px rgba(13, 24, 131, .07); padding: 28px; margin-bottom: 18px; }
        .vnd-card h2 { color: var(--vnd-main); font-size: 1.2rem; font-weight: 700; margin-bottom: .25rem; }
        .vnd-card .vnd-sub { color: #6b7080; font-size: .9rem; margin-bottom: 1.25rem; }
        .vnd-card h3 { color: var(--vnd-main); font-size: 1rem; font-weight: 700; margin: 1.5rem 0 .75rem; }
        .vnd-page label.form-label { font-weight: 600; font-size: .9rem; color: #222; }
        .vnd-req { color: #d33; }
        .vnd-option { display: flex; align-items: flex-start; gap: 10px; border: 1px solid #dfe3ee; border-radius: 10px; padding: 10px 12px; cursor: pointer; height: 100%; margin: 0; color: #222; font-size: .92rem; background: #fff; transition: border-color .15s, background .15s; }
        .vnd-option:hover { border-color: #b9c0d8; }
        .vnd-option input { margin-top: 3px; flex-shrink: 0; }
        .vnd-option:has(input:checked) { border-color: var(--vnd-main); background: #f1f3fc; }
        .vnd-option small { display: block; color: #6b7080; font-weight: 400; }
        .vnd-service { border-left: 4px solid var(--vnd-main); }
        .vnd-doc { border: 1px solid #e3e6ef; border-radius: 10px; padding: 14px; margin-bottom: 10px; }
        .vnd-doc .vnd-doc-name { font-weight: 600; font-size: .92rem; }
        .vnd-badge { font-size: .7rem; padding: 2px 8px; border-radius: 20px; background: #eef0f6; color: #555; margin-left: 6px; white-space: nowrap; }
        .vnd-badge.req { background: #fde8e8; color: #b42318; }
        .vnd-file { color: var(--vnd-green); font-size: .85rem; font-weight: 600; word-break: break-all; }
        .vnd-note { background: #fff8e6; border: 1px solid #f5d98b; border-radius: 10px; padding: 12px 14px; font-size: .88rem; color: #6b5200; }
        .vnd-declaration { background: #f5f7fb; border-left: 4px solid var(--vnd-main); border-radius: 8px; padding: 14px 16px; font-size: .9rem; color: #333; }
        .vnd-nav { display: flex; justify-content: space-between; gap: 12px; }
        .vnd-btn { background: var(--vnd-main); color: #fff; border: none; border-radius: 30px; padding: 10px 30px; font-weight: 600; }
        .vnd-btn:hover { background: rgba(9, 18, 100, 1); color: #fff; }
        .vnd-btn-light { background: #fff; color: var(--vnd-main); border: 1px solid var(--vnd-main); border-radius: 30px; padding: 10px 26px; font-weight: 600; }
        .vnd-hp { position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden; }

        /* File upload: a tappable drop area instead of the browser's "Choose File" button */
        .vnd-upload { position: relative; display: flex; align-items: center; gap: 12px; border: 1.5px dashed #b9c0d8; border-radius: 10px; padding: 12px 14px; background: #fafbff; cursor: pointer; margin: 0; transition: border-color .15s, background .15s; }
        .vnd-upload:hover, .vnd-upload:focus-within { border-color: var(--vnd-main); background: #f1f3fc; }
        .vnd-upload.is-invalid { border-color: #dc3545; background: #fff7f7; }
        .vnd-upload input[type=file] { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
        .vnd-upload-icon { width: 40px; height: 40px; flex-shrink: 0; border-radius: 10px; background: #e9ecfa; color: var(--vnd-main); display: grid; place-items: center; font-size: 18px; }
        .vnd-upload-text { display: flex; flex-direction: column; line-height: 1.3; min-width: 0; }
        .vnd-upload-text strong { color: var(--vnd-main); font-size: .9rem; }
        .vnd-upload-text small { color: #6b7080; font-size: .78rem; }
        .vnd-uploaded { display: flex; align-items: center; gap: 12px; border: 1px solid #bfe5cf; background: #f0faf4; border-radius: 10px; padding: 10px 14px; }
        .vnd-uploaded .vnd-upload-icon { background: #d5f0e0; color: var(--vnd-green); }
        .vnd-uploaded .vnd-file { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; word-break: normal; }
        .vnd-file-size { color: #6b7080; font-weight: 400; }
        .vnd-replace { background: none; border: 0; color: var(--vnd-main); font-size: .82rem; font-weight: 600; padding: 4px 0; flex-shrink: 0; }

        /* Phone progress: one bar and "Step x of 7" instead of seven cramped labels */
        .vnd-progress-mobile { display: none; margin-bottom: 1rem; }
        .vnd-progress-mobile .label { display: flex; justify-content: space-between; align-items: baseline; font-size: .82rem; color: #6b7080; margin-bottom: 6px; }
        .vnd-progress-mobile .label strong { color: var(--vnd-main); font-size: .95rem; }
        .vnd-progress-mobile .track { height: 6px; border-radius: 6px; background: #dfe3ee; overflow: hidden; }
        .vnd-progress-mobile .fill { height: 100%; border-radius: 6px; background: var(--vnd-main); transition: width .3s; }

        /* Tablets: less padding inside the cards */
        @media (min-width: 768px) and (max-width: 991px) {
            .vnd-page { padding-bottom: 40px; }
            .vnd-wrap { padding: 0 20px; }
            .vnd-card { padding: 22px; }
        }

        /*
         * Phones. The site menu collapses to 104px here, but the layout still
         * starts pages 128px down, leaving a white strip; pull the page up to
         * close it.
         */
        @media (max-width: 767px) {
            .vnd-page { margin-top: -24px; padding: 18px 0 0; }
            .vnd-wrap { padding: 0 12px; }
            .vnd-steps { display: none; }
            .vnd-progress-mobile { display: block; }
            .vnd-head h1 { font-size: 1.4rem; line-height: 1.25; }
            .vnd-head p { font-size: .9rem; margin-bottom: 1.1rem; }
            .vnd-card { padding: 18px 14px; border-radius: 12px; margin-bottom: 12px; }
            .vnd-card h2 { font-size: 1.08rem; }
            .vnd-card h3 { font-size: .95rem; margin-top: 1.25rem; }
            /* 16px stops iPhones zooming into a field when it is tapped */
            .vnd-page .form-control, .vnd-page .form-select { font-size: 16px; }
            .vnd-page .row.g-2 { --bs-gutter-y: .4rem; }
            .vnd-option { padding: 10px 12px; }
            .vnd-doc { padding: 12px; }
            /* Back / Continue stay within thumb reach at the bottom of the screen */
            .vnd-nav { position: sticky; bottom: 0; z-index: 5; margin: 0 -12px; padding: 12px 12px calc(12px + env(safe-area-inset-bottom)); background: #fff; box-shadow: 0 -4px 16px rgba(13, 24, 131, .08); }
            .vnd-nav > * { flex: 1; }
            .vnd-btn, .vnd-btn-light { padding: 12px 16px; }
            .vnd-nav > span:empty { display: none; }
        }
    </style>

    <div class="vnd-wrap">
        <div class="vnd-head">
            <h1>Vendor / Partner Registration</h1>
            @if ($step === 1)
                <p>Thank you for your interest in partnering with TravelWheel. Complete the sections that apply to you and upload your supporting documents. It takes about 15–20 minutes; have your company documents ready before you start.</p>
            @endif
        </div>

        <div class="vnd-progress-mobile" aria-hidden="true">
            <div class="label"><strong>{{ $steps[$step] }}</strong><span>Step {{ $step }} of {{ count($steps) }}</span></div>
            <div class="track"><div class="fill" style="width: {{ round($step / count($steps) * 100) }}%"></div></div>
        </div>

        <ol class="vnd-steps" aria-label="Progress">
            @foreach ($steps as $number => $name)
                <li class="{{ $number < $step ? 'done' : ($number === $step ? 'current' : '') }}" @if ($number === $step) aria-current="step" @endif>
                    <div class="bar"></div>
                    @if ($number < $step)
                        <button type="button" wire:click="goTo({{ $number }})">{{ $number }}. {{ $name }}</button>
                    @else
                        {{ $number }}. {{ $name }}
                    @endif
                </li>
            @endforeach
        </ol>

        @if ($errors->any())
            <div class="alert alert-danger">Please correct the highlighted fields below.</div>
        @endif

        <form wire:submit.prevent="{{ $step === count($steps) ? 'submit' : 'next' }}" novalidate>
            <div class="vnd-hp" aria-hidden="true">
                <label>Company fax <input type="text" wire:model="company_fax" tabindex="-1" autocomplete="off"></label>
            </div>

            {{-- 1. Company --}}
            @if ($step === 1)
                <div class="vnd-card">
                    <h2>1. Company &amp; business information</h2>
                    <p class="vnd-sub">As it appears on your registration documents.</p>
                    <div class="row g-3">
                        @foreach ([
                            ['registered_name', 'Registered business / company name', true, 'text', 'col-md-6'],
                            ['trading_name', 'Trading / brand name', false, 'text', 'col-md-6'],
                            ['registration_number', 'Business registration number (e.g. CAC RC/BN)', true, 'text', 'col-md-6'],
                            ['tax_id', 'Tax identification number (TIN)', false, 'text', 'col-md-6'],
                            ['country', 'Country of registration', true, 'text', 'col-md-6'],
                            ['year_established', 'Year established', false, 'text', 'col-md-6'],
                            ['address', 'Business address', true, 'textarea', 'col-12'],
                            ['website', 'Website', false, 'text', 'col-md-6'],
                            ['business_email', 'Business email', true, 'email', 'col-md-6'],
                            ['business_phone', 'Business phone / WhatsApp', true, 'tel', 'col-md-6'],
                            ['locations_served', 'Countries / locations served', true, 'textarea', 'col-12'],
                        ] as [$name, $label, $required, $type, $col])
                            @include('livewire.pages.partners.partials.input', ['model' => "form.$name"])
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- 2. Contacts --}}
            @if ($step === 2)
                <div class="vnd-card">
                    <h2>2. Contacts</h2>
                    <p class="vnd-sub">The person authorised to act for the company, and who we call day to day.</p>

                    <h3 class="mt-0">Authorised contact person</h3>
                    <div class="row g-3">
                        @foreach ([
                            ['contact_name', 'Full name', true, 'text', 'col-md-6'],
                            ['contact_title', 'Position / job title', true, 'text', 'col-md-6'],
                            ['contact_email', 'Email', true, 'email', 'col-md-6'],
                            ['contact_phone', 'Phone / WhatsApp', true, 'tel', 'col-md-6'],
                        ] as [$name, $label, $required, $type, $col])
                            @include('livewire.pages.partners.partials.input', ['model' => "form.$name"])
                        @endforeach
                    </div>

                    <h3>Operations / 24-hour contact</h3>
                    <p class="vnd-sub mb-2">Who we reach about a live booking, including evenings and weekends.</p>
                    <div class="row g-3">
                        @foreach ([
                            ['operations_name', 'Name', true, 'text', 'col-md-4'],
                            ['operations_phone', 'Phone / WhatsApp', true, 'tel', 'col-md-4'],
                            ['operations_email', 'Email', false, 'email', 'col-md-4'],
                        ] as [$name, $label, $required, $type, $col])
                            @include('livewire.pages.partners.partials.input', ['model' => "form.$name"])
                        @endforeach
                    </div>

                    <h3>Accounts contact</h3>
                    <p class="vnd-sub mb-2">Who handles invoices and payments, if different.</p>
                    <div class="row g-3">
                        @foreach ([
                            ['accounts_name', 'Name', false, 'text', 'col-md-4'],
                            ['accounts_phone', 'Phone', false, 'tel', 'col-md-4'],
                            ['accounts_email', 'Email', false, 'email', 'col-md-4'],
                        ] as [$name, $label, $required, $type, $col])
                            @include('livewire.pages.partners.partials.input', ['model' => "form.$name"])
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- 3. Services --}}
            @if ($step === 3)
                <div class="vnd-card">
                    <h2>3. Business type &amp; services</h2>
                    <p class="vnd-sub">Tick everything that applies.</p>

                    <h3 class="mt-0">Business type <span class="vnd-req">*</span></h3>
                    <div class="row g-2">
                        @foreach ($businessTypes as $key => $label)
                            <div class="col-md-6">
                                <label class="vnd-option"><input type="checkbox" class="form-check-input" wire:model="form.business_types" value="{{ $key }}"> {{ $label }}</label>
                            </div>
                        @endforeach
                        <div class="col-12">
                            <input type="text" class="form-control" placeholder="Other business type (describe)" wire:model="form.business_type_other">
                        </div>
                    </div>
                    @error('form.business_types') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

                    <h3>Services you wish to offer <span class="vnd-req">*</span></h3>
                    <div class="row g-2">
                        @foreach ($services as $key => $service)
                            <div class="col-md-6">
                                <label class="vnd-option">
                                    <input type="checkbox" class="form-check-input" wire:model="form.services" value="{{ $key }}">
                                    <span>{{ $service['label'] }}<small>{{ $service['intro'] }}</small></span>
                                </label>
                            </div>
                        @endforeach
                        <div class="col-12">
                            <input type="text" class="form-control" placeholder="Any other service you offer (optional)" wire:model="form.service_other">
                        </div>
                    </div>
                    @error('form.services') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

                    <h3>Do you currently work with other travel agencies / B2B platforms? <span class="vnd-req">*</span></h3>
                    <div class="d-flex gap-4">
                        <label class="form-check"><input type="radio" class="form-check-input" wire:model.live="form.works_with_other_platforms" value="yes"> Yes</label>
                        <label class="form-check"><input type="radio" class="form-check-input" wire:model.live="form.works_with_other_platforms" value="no"> No</label>
                    </div>
                    @error('form.works_with_other_platforms') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    @if ($form['works_with_other_platforms'] === 'yes')
                        <div class="mt-3">
                            <label class="form-label">Please give details <span class="vnd-req">*</span></label>
                            <textarea class="form-control @error('form.other_platforms_details') is-invalid @enderror" rows="3" wire:model="form.other_platforms_details"></textarea>
                            @error('form.other_platforms_details') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endif
                </div>
            @endif

            {{-- 4. Service details --}}
            @if ($step === 4)
                <div class="vnd-card">
                    <h2>4. Service details</h2>
                    <p class="vnd-sub mb-0">Only for the services you chose. If an answer is in your rate sheet or terms, say "See rate sheet" and upload it in step 6.</p>
                </div>

                @foreach ($this->selectedServices() as $serviceKey => $service)
                    <div class="vnd-card vnd-service" wire:key="service-{{ $serviceKey }}">
                        <h2>{{ $service['label'] }}</h2>
                        <p class="vnd-sub">{{ $service['intro'] }}</p>
                        <div class="row g-3">
                            @foreach ($service['fields'] as $field => $def)
                                @php $model = "details.$serviceKey.$field"; $required = $def['required'] ?? false; @endphp
                                <div class="{{ in_array($def['type'], ['textarea', 'checkboxes'], true) ? 'col-12' : 'col-md-6' }}" wire:key="{{ $model }}">
                                    <label class="form-label">{{ $def['label'] }} @if ($required)<span class="vnd-req">*</span>@endif</label>

                                    @if ($def['type'] === 'textarea')
                                        <textarea class="form-control @error($model) is-invalid @enderror" rows="3" wire:model="{{ $model }}"></textarea>
                                    @elseif ($def['type'] === 'yesno')
                                        <div class="d-flex gap-4">
                                            <label class="form-check"><input type="radio" class="form-check-input" wire:model="{{ $model }}" value="yes"> Yes</label>
                                            <label class="form-check"><input type="radio" class="form-check-input" wire:model="{{ $model }}" value="no"> No</label>
                                        </div>
                                    @elseif ($def['type'] === 'checkboxes')
                                        <div class="row g-2">
                                            @foreach ($def['options'] as $option)
                                                <div class="col-sm-6 col-lg-4">
                                                    <label class="vnd-option"><input type="checkbox" class="form-check-input" wire:model="{{ $model }}" value="{{ $option }}"> {{ $option }}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <input type="text" class="form-control @error($model) is-invalid @enderror" wire:model="{{ $model }}">
                                    @endif

                                    @error($model) <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>
                            @endforeach

                            @isset($service['acknowledgement'])
                                <div class="col-12">
                                    <label class="vnd-option vnd-note"><input type="checkbox" class="form-check-input" wire:model="acknowledged.{{ $serviceKey }}"> {{ $service['acknowledgement'] }} <span class="vnd-req">*</span></label>
                                    @error("acknowledged.$serviceKey") <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>
                            @endisset
                        </div>
                    </div>
                @endforeach
            @endif

            {{-- 5. Bookings & payment --}}
            @if ($step === 5)
                <div class="vnd-card">
                    <h2>5. Bookings &amp; payment</h2>
                    <p class="vnd-sub">How we send you bookings, and how you are paid.</p>

                    <h3 class="mt-0">How can you receive bookings? <span class="vnd-req">*</span></h3>
                    <div class="row g-2">
                        @foreach ($bookingChannels as $key => $label)
                            <div class="col-sm-6 col-lg-4">
                                <label class="vnd-option"><input type="checkbox" class="form-check-input" wire:model="form.booking_channels" value="{{ $key }}"> {{ $label }}</label>
                            </div>
                        @endforeach
                    </div>
                    @error('form.booking_channels') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

                    <div class="row g-3 mt-1">
                        @foreach ([
                            ['booking_email', 'Email address for new bookings', true, 'email', 'col-md-6'],
                            ['confirmation_time', 'How quickly you confirm a booking (e.g. within 2 hours)', false, 'text', 'col-md-6'],
                        ] as [$name, $label, $required, $type, $col])
                            @include('livewire.pages.partners.partials.input', ['model' => "form.$name"])
                        @endforeach
                    </div>

                    <h3>Rates &amp; settlement</h3>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Rate model <span class="vnd-req">*</span></label>
                            <select class="form-select @error('form.rate_model') is-invalid @enderror" wire:model="form.rate_model">
                                <option value="">Choose…</option>
                                @foreach ($rateModels as $key => $label) <option value="{{ $key }}">{{ $label }}</option> @endforeach
                            </select>
                            @error('form.rate_model') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Settlement currency <span class="vnd-req">*</span></label>
                            <select class="form-select @error('form.settlement_currency') is-invalid @enderror" wire:model="form.settlement_currency">
                                @foreach ($currencies as $key => $label) <option value="{{ $key }}">{{ $label }}</option> @endforeach
                            </select>
                            @error('form.settlement_currency') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        @php [$name, $label, $required, $type, $col] = ['payment_terms', 'Payment terms (e.g. weekly settlement, 30 days)', false, 'text', 'col-12']; @endphp
                        @include('livewire.pages.partners.partials.input', ['model' => "form.$name"])
                    </div>

                    <h3>Bank details for payments</h3>
                    <p class="vnd-sub mb-2">Stored encrypted and only visible to our finance team.</p>
                    <div class="row g-3">
                        @foreach ([
                            ['bank_name', 'Bank name', true, 'text', 'col-md-4'],
                            ['account_name', 'Account name', true, 'text', 'col-md-4'],
                            ['account_number', 'Account number', true, 'text', 'col-md-4'],
                        ] as [$name, $label, $required, $type, $col])
                            @include('livewire.pages.partners.partials.input', ['model' => "form.$name"])
                        @endforeach
                    </div>

                    <h3>References</h3>
                    <div class="row g-3">
                        @php [$name, $label, $required, $type, $col] = ['references', '2–3 current clients or partners we may contact (name, company, phone or email)', false, 'textarea', 'col-12']; @endphp
                        @include('livewire.pages.partners.partials.input', ['model' => "form.$name"])
                    </div>
                </div>
            @endif

            {{-- 6. Documents --}}
            @if ($step === 6)
                <div class="vnd-card">
                    <h2>6. Supporting documents</h2>
                    <p class="vnd-sub">Accepted files: PDF, JPG, PNG, Word or Excel, <strong>up to {{ $maxUploadLabel }} each</strong>. Scanned documents are usually well under this; if a file is larger, compress it or save it at a lower resolution first. Documents marked <span class="vnd-badge req">Required</span> must be attached; the rest are where applicable.</p>

                    @php $grouped = collect($documents)->groupBy(fn ($doc) => $doc['service'] ? $services[$doc['service']]['label'] : 'Company & compliance', preserveKeys: true); @endphp
                    @foreach ($grouped as $group => $docs)
                        <h3 @if ($loop->first) class="mt-0" @endif>{{ $group }}</h3>
                        @foreach ($docs as $type => $doc)
                            <div class="vnd-doc" wire:key="doc-{{ $type }}">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                                    <span class="vnd-doc-name">{{ $doc['label'] }}<span class="vnd-badge {{ $doc['required'] ? 'req' : '' }}">{{ $doc['required'] ? 'Required' : 'If applicable' }}</span></span>
                                </div>
                                <div class="row g-2 align-items-center">
                                    <div class="{{ $doc['expires'] ? 'col-md-8' : 'col-12' }}">
                                        @if (isset($uploads[$type]) && method_exists($uploads[$type], 'getClientOriginalName'))
                                            <div class="vnd-uploaded">
                                                <span class="vnd-upload-icon"><i class="fa-solid fa-check"></i></span>
                                                <span class="vnd-file" title="{{ $uploads[$type]->getClientOriginalName() }}">{{ $uploads[$type]->getClientOriginalName() }} <span class="vnd-file-size">({{ \Illuminate\Support\Number::fileSize($uploads[$type]->getSize(), precision: 1) }})</span></span>
                                                <button type="button" class="vnd-replace" wire:click="removeUpload('{{ $type }}')">Replace</button>
                                            </div>
                                        @else
                                            <label class="vnd-upload @error("uploads.$type") is-invalid @enderror">
                                                <input type="file" wire:model="uploads.{{ $type }}" accept="{{ collect($uploadExtensions)->map(fn ($ext) => '.'.$ext)->implode(',') }}" aria-label="{{ $doc['label'] }}"
                                                       data-max-bytes="{{ $maxUploadBytes }}" data-max-label="{{ $maxUploadLabel }}" data-extensions="{{ implode(',', $uploadExtensions) }}">
                                                <span class="vnd-upload-icon">
                                                    <i class="fa-solid fa-cloud-arrow-up" wire:loading.remove wire:target="uploads.{{ $type }}"></i>
                                                    <span class="spinner-border spinner-border-sm" wire:loading wire:target="uploads.{{ $type }}"></span>
                                                </span>
                                                <span class="vnd-upload-text">
                                                    <strong wire:loading.remove wire:target="uploads.{{ $type }}">Choose a file</strong>
                                                    <strong wire:loading wire:target="uploads.{{ $type }}">Uploading…</strong>
                                                    <small>PDF, JPG, PNG, Word or Excel · max {{ $maxUploadLabel }}</small>
                                                </span>
                                            </label>
                                        @endif
                                        <div class="invalid-feedback d-block" data-upload-error hidden></div>
                                        @error("uploads.$type") <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>
                                    @if ($doc['expires'])
                                        <div class="col-md-4">
                                            <label class="small text-muted mb-1 d-block">Expiry date (if any)</label>
                                            <input type="date" class="form-control @error("expiries.$type") is-invalid @enderror" wire:model="expiries.{{ $type }}">
                                            @error("expiries.$type") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            @endif

            {{-- 7. Declaration --}}
            @if ($step === 7)
                <div class="vnd-card">
                    <h2>7. Vendor declaration</h2>
                    <p class="vnd-sub">Please read carefully before submitting.</p>

                    <div class="vnd-declaration mb-3">{{ config('vendor_onboarding.declaration') }}</div>

                    <label class="vnd-option mb-2"><input type="checkbox" class="form-check-input" wire:model="agree"> <span>I confirm the declaration above on behalf of the company. <span class="vnd-req">*</span></span></label>
                    @error('agree') <div class="invalid-feedback d-block mb-2">{{ $message }}</div> @enderror

                    <label class="vnd-option mb-2"><input type="checkbox" class="form-check-input" wire:model="consent"> <span>{{ config('vendor_onboarding.consent') }} <span class="vnd-req">*</span></span></label>
                    @error('consent') <div class="invalid-feedback d-block mb-2">{{ $message }}</div> @enderror

                    <div class="row g-3 mt-1">
                        @foreach ([
                            ['declarant_name', 'Authorised representative name (this is your signature)', true, 'text', 'col-md-6'],
                            ['declarant_title', 'Position / job title', true, 'text', 'col-md-6'],
                        ] as [$name, $label, $required, $type, $col])
                            @include('livewire.pages.partners.partials.input', ['model' => "form.$name"])
                        @endforeach
                    </div>
                    <p class="small text-muted mt-3 mb-0">Date: {{ now()->format('j F Y') }}. Submitting this form is your electronic signature.</p>
                </div>
            @endif

            <div class="vnd-nav">
                @if ($step > 1)
                    <button type="button" class="vnd-btn-light" wire:click="back">&larr; Back</button>
                @else
                    <span></span>
                @endif
                <button type="submit" class="vnd-btn" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="next,submit">{{ $step === count($steps) ? 'Submit application' : 'Continue →' }}</span>
                    <span wire:loading wire:target="next,submit">Please wait…</span>
                </button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('vendor-step-changed', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
        });

        // Check each file in the browser before it uploads: an oversized or wrong-type
        // file is refused at once with the reason, instead of failing part-way.
        (() => {
            const errorFor = input => input.closest('.vnd-doc')?.querySelector('[data-upload-error]');
            const show = (input, message) => {
                const box = errorFor(input);
                if (box) { box.textContent = message; box.hidden = !message; }
                input.closest('.vnd-upload')?.classList.toggle('is-invalid', !!message);
            };
            const mb = bytes => (bytes / 1048576).toFixed(1) + ' MB';

            // Capture phase: runs before Livewire's own listener, so a refused file never uploads
            document.addEventListener('change', e => {
                const input = e.target;
                if (!input.matches?.('input[type=file][data-max-bytes]')) return;
                const file = input.files?.[0];
                if (!file) return;

                const ext = (file.name.split('.').pop() || '').toLowerCase();
                const allowed = input.dataset.extensions.split(',');
                let problem = '';
                if (!allowed.includes(ext)) {
                    problem = '"' + file.name + '" is not an accepted file type. Please upload a PDF, JPG, PNG, Word or Excel file.';
                } else if (file.size > Number(input.dataset.maxBytes)) {
                    problem = '"' + file.name + '" is ' + mb(file.size) + '. The maximum is ' + input.dataset.maxLabel + ' per file; please compress it or upload a smaller copy.';
                } else if (file.size === 0) {
                    problem = '"' + file.name + '" is empty. Please choose the file again.';
                }

                if (problem) {
                    e.stopImmediatePropagation();
                    input.value = '';
                    show(input, problem);
                } else {
                    show(input, '');
                }
            }, true);

            // A dropped connection or a server limit can still stop an upload; say so plainly
            document.addEventListener('livewire-upload-error', e => {
                const input = e.target;
                if (input.matches?.('input[type=file][data-max-bytes]')) {
                    input.value = '';
                    show(input, 'The upload did not complete. Check your connection and try again; files must be ' + input.dataset.maxLabel + ' or smaller.');
                }
            });
        })();
    </script>
</div>
