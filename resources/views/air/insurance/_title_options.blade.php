<option value="">-- Select Title --</option>
@foreach (\App\Support\InsuranceOptions::TITLES as $value => $label)
<option value="{{ $value }}">{{ $label }}</option>
@endforeach
