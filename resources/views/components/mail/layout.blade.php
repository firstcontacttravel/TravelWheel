@props([
    'title' => null,
    // Inbox preview text. Clients show this next to the subject line; with no
    // preheader they scrape the first visible words instead, which on the old
    // templates meant the header eyebrow ("ELECTRONIC TICKET") every time.
    'preheader' => null,
    'eyebrow' => null,
    'heading' => null,
    'intro' => null,
    'badge' => null,
    // neutral | success | warning | danger
    'tone' => 'neutral',
    // Internal emails go to our own desks, so the customer support footer
    // ("reply to us at support@…") would just point the support team back at
    // itself. They get a provenance line instead.
    'internal' => false,
])
@php
    $c = config('brand.colors');
    $brandName = config('brand.name');

    $tones = [
        'neutral' => ['bg' => '#ffffff', 'fg' => $c['brand']],
        'success' => ['bg' => $c['success_bg'], 'fg' => $c['success']],
        'warning' => ['bg' => $c['warning_bg'], 'fg' => $c['warning']],
        'danger' => ['bg' => $c['danger_bg'], 'fg' => $c['danger']],
    ];
    $badgeTone = $tones[$tone] ?? $tones['neutral'];

    $font = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,Helvetica,sans-serif";
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="color-scheme" content="light only" />
<meta name="supported-color-schemes" content="light only" />
<title>{{ $title ?? $heading ?? $brandName }}</title>
<!--[if mso]>
<style type="text/css">
    table, td, div, p, a { font-family: Arial, Helvetica, sans-serif !important; }
</style>
<![endif]-->
</head>
<body style="margin:0;padding:0;width:100%;background-color:{{ $c['surface'] }};-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">

@if ($preheader)
<div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;color:{{ $c['surface'] }};">
    {{ $preheader }}
    {{-- Padding so the client cannot pull body copy into the preview line. --}}
    &#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;
</div>
@endif

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:{{ $c['surface'] }};">
<tr>
<td align="center" style="padding:24px 12px;">

    <!--[if mso]><table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"><tr><td><![endif]-->
    <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:600px;background-color:#ffffff;border:1px solid {{ $c['line'] }};border-radius:12px;">

        {{--
            Solid brand colour, not a gradient. Eleven templates used
            linear-gradient() here; Outlook drops it entirely, leaving white
            text on a white header.
        --}}
        <tr>
        <td style="background-color:{{ $c['brand'] }};padding:26px 28px;border-radius:12px 12px 0 0;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td style="font-family:{{ $font }};font-size:19px;font-weight:700;color:#ffffff;letter-spacing:-.2px;line-height:1.2;">
                    {{ $brandName }}
                    @if ($eyebrow)
                    <div style="font-size:11px;font-weight:600;color:#c3c4f2;letter-spacing:.06em;margin-top:5px;">{{ $eyebrow }}</div>
                    @endif
                </td>
                @if ($badge)
                <td align="right" style="vertical-align:top;">
                    <span style="display:inline-block;background-color:{{ $badgeTone['bg'] }};color:{{ $badgeTone['fg'] }};border-radius:999px;padding:6px 12px;font-family:{{ $font }};font-size:11px;font-weight:700;white-space:nowrap;">{{ $badge }}</span>
                </td>
                @endif
            </tr>
            </table>

            @if ($heading)
            <div style="font-family:{{ $font }};font-size:23px;font-weight:700;color:#ffffff;line-height:1.3;margin-top:22px;">{{ $heading }}</div>
            @endif
            @if ($intro)
            <div style="font-family:{{ $font }};font-size:14px;line-height:1.65;color:#dcdcf5;margin-top:8px;">{{ $intro }}</div>
            @endif
        </td>
        </tr>

        <tr>
        <td style="padding:26px 28px 8px;font-family:{{ $font }};font-size:14px;line-height:1.65;color:{{ $c['text'] }};">
            {{ $slot }}
        </td>
        </tr>

        <tr>
        <td style="padding:14px 28px 26px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr><td style="border-top:1px solid {{ $c['line'] }};font-size:0;line-height:0;">&nbsp;</td></tr>
            </table>
            @if ($internal)
            <div style="font-family:{{ $font }};font-size:11.5px;line-height:1.7;color:{{ $c['subtle'] }};padding-top:16px;">
                Automated internal notification from the {{ $brandName }} platform. Not sent to the customer.
            </div>
            @else
            <div style="font-family:{{ $font }};font-size:12.5px;line-height:1.7;color:{{ $c['muted'] }};padding-top:16px;">
                Need a hand? Reply to this email or reach us at
                <a href="mailto:{{ config('brand.support_email') }}" style="color:{{ $c['brand'] }};text-decoration:none;font-weight:600;">{{ config('brand.support_email') }}</a>,
                {{ config('brand.support_phone') }} &nbsp;·&nbsp; WhatsApp {{ config('brand.support_whatsapp') }}.
            </div>
            <div style="font-family:{{ $font }};font-size:11.5px;line-height:1.7;color:{{ $c['subtle'] }};padding-top:12px;">
                {{ $brandName }} &nbsp;·&nbsp; {{ config('brand.address') }}<br />
                This message was sent to you because of a booking or request made with {{ $brandName }}.
            </div>
            @endif
        </td>
        </tr>

    </table>
    <!--[if mso]></td></tr></table><![endif]-->

</td>
</tr>
</table>
</body>
</html>
