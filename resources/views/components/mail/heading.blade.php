@php $c = config('brand.colors'); $font = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,Helvetica,sans-serif"; @endphp
<div style="font-family:{{ $font }};font-size:13px;font-weight:700;color:{{ $c['ink'] }};line-height:1.4;margin:4px 0 10px;">{{ $slot }}</div>
