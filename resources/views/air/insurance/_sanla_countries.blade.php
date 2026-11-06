<option value="">-- Select Country --</option>
@foreach (\App\Support\InsuranceOptions::COUNTRIES as $value => $label)
<option value="{{ $value }}">{{ $label }}</option>
@endforeach
