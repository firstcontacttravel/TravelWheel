{{--
    Passenger snapshot captured at checkout.

    @param list<array{name:string, type:string, rows:array<string,string>}> $passengers
--}}
@if ($passengers === [])
    <p class="tc-empty">No passenger snapshot was captured for this booking.</p>
@else
    <div class="tc-pax">
        @foreach ($passengers as $passenger)
            <div class="tc-pax-card">
                <div class="tc-pax-head">
                    <span class="tc-pax-name">{{ $passenger['name'] }}</span>
                    <span class="tc-tag">{{ $passenger['type'] }}</span>
                </div>
                <dl class="tc-pax-body">
                    @foreach ($passenger['rows'] as $label => $value)
                        <div>
                            <dt>{{ $label }}</dt>
                            <dd @class(['tc-mono' => in_array($label, ['Passport', 'E-ticket'], true)])>{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        @endforeach
    </div>
@endif
