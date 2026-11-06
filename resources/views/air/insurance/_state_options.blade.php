<option value="">-- Select state --</option>
@foreach (\App\Support\InsuranceOptions::STATES as $value => $label)
<option value="{{ $value }}">{{ $label }}</option>
@endforeach
