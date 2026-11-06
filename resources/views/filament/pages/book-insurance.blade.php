<x-filament-panels::page>
@include('filament.pages.partials.booking-styles')

<div class="bk">
    <div class="bk-one">
        <section class="bk-step tint">
            <div class="bk-label">1 · Trip &amp; quote</div>
            <div class="bk-options" style="margin-bottom:14px;">
                <button type="button" class="bk-pay {{ $family ? '' : 'on' }}" wire:click="setBookingType(1)">
                    <b>Individual</b>
                    <small>One traveller</small>
                </button>
                <button type="button" class="bk-pay {{ $family ? 'on' : '' }}" wire:click="setBookingType(2)">
                    <b>Family</b>
                    <small>Two adults and their children</small>
                </button>
            </div>
            @if ($family)
                <div class="bk-guest">
                    <div><b>Children</b><small>Up to 6</small></div>
                    <div class="bk-count">
                        <button type="button" wire:click="changeChildren(-1)" aria-label="One fewer child">&minus;</button>
                        <span id="bk-children">{{ $children }}</span>
                        <button type="button" wire:click="changeChildren(1)" aria-label="One more child">+</button>
                    </div>
                </div>
            @endif
            <div class="bk-grid" style="margin-top:12px;">
                <div class="bk-field">
                    <label for="bk-country">Destination</label>
                    <select id="bk-country" wire:model.live="country">
                        <option value="">Choose a country</option>
                        @foreach ($countries as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('country') <div class="bk-err">{{ $message }}</div> @enderror
                </div>
                <div class="bk-field">
                    <label for="bk-purpose">Purpose of travel</label>
                    <select id="bk-purpose" wire:model.live="purpose">
                        <option value="">Choose a purpose</option>
                        @foreach ($purposes as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                    @error('purpose') <div class="bk-err">{{ $message }}</div> @enderror
                </div>
                <div class="bk-field">
                    <label for="bk-begin">Cover starts</label>
                    <input id="bk-begin" type="date" min="{{ now()->toDateString() }}" wire:model.live="beginDate">
                    @error('beginDate') <div class="bk-err">{{ $message }}</div> @enderror
                </div>
                <div class="bk-field">
                    <label for="bk-end">Cover ends</label>
                    <input id="bk-end" type="date" min="{{ now()->toDateString() }}" wire:model.live="endDate">
                    @error('endDate') <div class="bk-err">{{ $message }}</div> @enderror
                </div>
                <div class="bk-field">
                    <label for="bk-lead-dob">{{ $family ? "Lead adult's" : "Traveller's" }} date of birth</label>
                    <input id="bk-lead-dob" type="date" max="{{ now()->subDay()->toDateString() }}" wire:model.live="leadDob">
                    @error('leadDob') <div class="bk-err">{{ $message }}</div> @enderror
                </div>
                <div class="bk-field">
                    <label for="bk-email">Email</label>
                    <input id="bk-email" type="email" wire:model.blur="email">
                    @error('email') <div class="bk-err">{{ $message }}</div> @enderror
                </div>
                <div class="bk-field">
                    <label for="bk-phone">Phone</label>
                    <input id="bk-phone" type="tel" wire:model.blur="phone">
                    @error('phone') <div class="bk-err">{{ $message }}</div> @enderror
                </div>
                <div class="bk-field">
                    <label>Plan</label>
                    <div class="bk-spec" style="margin-top:0;">{{ $plan ?? 'Follows the destination' }}</div>
                </div>
            </div>

            <button type="button" class="bk-btn-line" wire:click="getQuote" wire:loading.attr="disabled" wire:target="getQuote">
                <span wire:loading.remove wire:target="getQuote">{{ $quote ? 'Get a new quote' : 'Get quote from Sanlam Allianz' }}</span>
                <span wire:loading wire:target="getQuote">Asking Sanlam…</span>
            </button>
            @if ($quoteError)
                <div class="bk-route bad" role="alert">{{ $quoteError }}</div>
            @elseif ($price)
                <div class="bk-route">Quote: &#8358;{{ number_format($price['amount'], 2) }} for this cover</div>
            @endif
        </section>

        @if ($price)
            @foreach ($family ? ['lead' => 'Lead adult', 'partner' => 'Second adult'] : ['lead' => 'Traveller'] as $who => $heading)
                <section class="bk-step" wire:key="person-{{ $who }}">
                    <div class="bk-label">{{ $loop->iteration + 1 }} · {{ $heading }}</div>
                    <div class="bk-grid">
                        @foreach (['firstname' => 'First name', 'middlename' => 'Middle name (optional)'] as $field => $label)
                            <div class="bk-field">
                                <label for="bk-{{ $who }}-{{ $field }}">{{ $label }}</label>
                                <input id="bk-{{ $who }}-{{ $field }}" type="text" wire:model="{{ $who }}.{{ $field }}">
                                @error("$who.$field") <div class="bk-err">{{ $message }}</div> @enderror
                            </div>
                        @endforeach
                        @foreach (['title' => [$titles, 'Title'], 'gender' => [$genders, 'Gender']] as $field => [$options, $label])
                            <div class="bk-field">
                                <label for="bk-{{ $who }}-{{ $field }}">{{ $label }}</label>
                                <select id="bk-{{ $who }}-{{ $field }}" wire:model="{{ $who }}.{{ $field }}">
                                    <option value="">Choose</option>
                                    @foreach ($options as $value => $name)
                                        <option value="{{ $value }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error("$who.$field") <div class="bk-err">{{ $message }}</div> @enderror
                            </div>
                        @endforeach
                        <div class="bk-field">
                            <label for="bk-{{ $who }}-dob">Date of birth</label>
                            <input id="bk-{{ $who }}-dob" type="date" wire:model="{{ $who }}.dob">
                            @error("$who.dob") <div class="bk-err">{{ $message }}</div> @enderror
                        </div>
                        <div class="bk-field">
                            <label for="bk-{{ $who }}-nationality">Nationality</label>
                            <select id="bk-{{ $who }}-nationality" wire:model="{{ $who }}.nationality">
                                <option value="">Choose a country</option>
                                @foreach ($countries as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            @error("$who.nationality") <div class="bk-err">{{ $message }}</div> @enderror
                        </div>
                        @foreach (['email' => ['Email', 'email'], 'phone' => ['Phone', 'tel'], 'passport_no' => [$who === 'lead' ? 'Passport number' : 'Passport number (optional)', 'text'], 'nin' => ['NIN (optional, 11 digits)', 'text'], 'occupation' => ['Occupation', 'text']] as $field => [$label, $type])
                            <div class="bk-field">
                                <label for="bk-{{ $who }}-{{ $field }}">{{ $label }}</label>
                                <input id="bk-{{ $who }}-{{ $field }}" type="{{ $type }}" wire:model="{{ $who }}.{{ $field }}">
                                @error("$who.$field") <div class="bk-err">{{ $message }}</div> @enderror
                            </div>
                        @endforeach
                        @if ($who === 'lead')
                            <div class="bk-field">
                                <label for="bk-lead-marital">Marital status</label>
                                <select id="bk-lead-marital" wire:model="lead.marital_status">
                                    <option value="">Choose</option>
                                    @foreach ($maritalStatuses as $value => $name)
                                        <option value="{{ $value }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('lead.marital_status') <div class="bk-err">{{ $message }}</div> @enderror
                            </div>
                        @endif
                        <div class="bk-field full">
                            <label for="bk-{{ $who }}-medical">Pre-existing medical condition (optional)</label>
                            <input id="bk-{{ $who }}-medical" type="text" maxlength="100" wire:model="{{ $who }}.medical">
                        </div>
                    </div>
                </section>
            @endforeach

            @if ($family)
                @foreach ($kids as $i => $kid)
                    <section class="bk-step" wire:key="kid-{{ $i }}">
                        <div class="bk-label">Child {{ $i + 1 }}</div>
                        <div class="bk-grid">
                            @foreach (['firstname' => 'First name', 'middlename' => 'Middle name (optional)'] as $field => $label)
                                <div class="bk-field">
                                    <label for="bk-kid-{{ $i }}-{{ $field }}">{{ $label }}</label>
                                    <input id="bk-kid-{{ $i }}-{{ $field }}" type="text" wire:model="kids.{{ $i }}.{{ $field }}">
                                    @error("kids.$i.$field") <div class="bk-err">{{ $message }}</div> @enderror
                                </div>
                            @endforeach
                            @foreach (['title' => [$titles, 'Title'], 'gender' => [$genders, 'Gender'], 'nationality' => [$countries, 'Nationality']] as $field => [$options, $label])
                                <div class="bk-field">
                                    <label for="bk-kid-{{ $i }}-{{ $field }}">{{ $label }}</label>
                                    <select id="bk-kid-{{ $i }}-{{ $field }}" wire:model="kids.{{ $i }}.{{ $field }}">
                                        <option value="">Choose</option>
                                        @foreach ($options as $value => $name)
                                            <option value="{{ $value }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                    @error("kids.$i.$field") <div class="bk-err">{{ $message }}</div> @enderror
                                </div>
                            @endforeach
                            <div class="bk-field">
                                <label for="bk-kid-{{ $i }}-dob">Date of birth</label>
                                <input id="bk-kid-{{ $i }}-dob" type="date" wire:model="kids.{{ $i }}.dob">
                                @error("kids.$i.dob") <div class="bk-err">{{ $message }}</div> @enderror
                            </div>
                            @foreach (['passport_no' => 'Passport number (optional)', 'nin' => 'NIN (optional, 11 digits)'] as $field => $label)
                                <div class="bk-field">
                                    <label for="bk-kid-{{ $i }}-{{ $field }}">{{ $label }}</label>
                                    <input id="bk-kid-{{ $i }}-{{ $field }}" type="text" wire:model="kids.{{ $i }}.{{ $field }}">
                                    @error("kids.$i.$field") <div class="bk-err">{{ $message }}</div> @enderror
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            @endif

            <section class="bk-step">
                <div class="bk-label">Address &amp; next of kin</div>
                <div class="bk-grid">
                    <div class="bk-field">
                        <label for="bk-surname">{{ $family ? 'Family surname' : 'Surname' }}</label>
                        <input id="bk-surname" type="text" wire:model="home.surname">
                        @error('home.surname') <div class="bk-err">{{ $message }}</div> @enderror
                    </div>
                    <div class="bk-field">
                        <label for="bk-state">State</label>
                        <select id="bk-state" wire:model="home.state">
                            <option value="">Choose a state</option>
                            @foreach ($states as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('home.state') <div class="bk-err">{{ $message }}</div> @enderror
                    </div>
                    <div class="bk-field">
                        <label for="bk-address">Address</label>
                        <input id="bk-address" type="text" wire:model="home.address">
                        @error('home.address') <div class="bk-err">{{ $message }}</div> @enderror
                    </div>
                    <div class="bk-field">
                        <label for="bk-zipcode">Zip code</label>
                        <input id="bk-zipcode" type="text" wire:model="home.zipcode">
                        @error('home.zipcode') <div class="bk-err">{{ $message }}</div> @enderror
                    </div>
                    @foreach (['fullname' => 'Next of kin name', 'relationship' => 'Relationship', 'phone' => 'Next of kin phone', 'address' => 'Next of kin address'] as $field => $label)
                        <div class="bk-field">
                            <label for="bk-nok-{{ $field }}">{{ $label }}</label>
                            <input id="bk-nok-{{ $field }}" type="text" wire:model="nok.{{ $field }}">
                            @error("nok.$field") <div class="bk-err">{{ $message }}</div> @enderror
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="bk-step">
                <div class="bk-label">Payment</div>
                <div class="bk-price" style="margin-top:0; margin-bottom:14px;">
                    <h4>Price breakdown</h4>
                    <div><span>Sanlam Allianz quote ({{ $plan }})</span><b>&#8358;{{ number_format($price['amount'], 2) }}</b></div>
                    <div><span>VAT ({{ $vatPercent }}%)</span><b>&#8358;{{ number_format($price['vat'], 2) }}</b></div>
                    <div class="total"><span>Total</span><b>&#8358;{{ number_format($price['total'], 2) }}</b></div>
                </div>
                <div class="bk-pays">
                    <button type="button" class="bk-pay {{ $sendLink ? 'on' : '' }}" wire:click="$set('sendLink', true)">
                        <b>Email a payment link</b>
                        <small>The customer pays online by SeerBit</small>
                    </button>
                    <button type="button" class="bk-pay {{ $sendLink ? '' : 'on' }}" wire:click="$set('sendLink', false)">
                        <b>Don't send a link</b>
                        <small>Paying by transfer, POS or cash: Finance records it</small>
                    </button>
                </div>
                <div class="bk-note" style="margin-bottom:12px;">The policy is issued by Sanlam Allianz once the payment is confirmed.</div>
                <button type="button" class="bk-submit" wire:click="create" wire:loading.attr="disabled" wire:target="create">
                    <span wire:loading.remove wire:target="create">Create booking · &#8358;{{ number_format($price['total'], 2) }}</span>
                    <span wire:loading wire:target="create">Creating booking…</span>
                </button>
            </section>
        @endif
    </div>
</div>
</x-filament-panels::page>
