@props(['url', 'tone' => 'brand'])
@php
    $c = config('brand.colors');
    $font = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,Helvetica,sans-serif";
    $bg = $tone === 'accent' ? $c['accent'] : $c['brand'];
@endphp
{{--
    Bulletproof button: Outlook ignores padding on <a>, so the VML rectangle
    below carries the shape there and the anchor is used everywhere else.
--}}
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:4px 0 22px;">
<tr><td align="center" style="border-radius:8px;background-color:{{ $bg }};">
<!--[if mso]>
<v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ $url }}" style="height:44px;v-text-anchor:middle;width:260px;" arcsize="18%" stroke="f" fillcolor="{{ $bg }}">
<w:anchorlock/>
<center style="color:#ffffff;font-family:Arial,sans-serif;font-size:14px;font-weight:bold;">{{ trim($slot) }}</center>
</v:roundrect>
<![endif]-->
<!--[if !mso]><!-->
<a href="{{ $url }}" style="display:inline-block;padding:13px 28px;font-family:{{ $font }};font-size:14px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:8px;background-color:{{ $bg }};">{{ $slot }}</a>
<!--<![endif]-->
</td></tr>
</table>
