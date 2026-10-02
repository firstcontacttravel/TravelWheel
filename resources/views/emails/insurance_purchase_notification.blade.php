<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Insurance Purchase</title>
    <style>
        body { margin: 0; padding: 0; background: #f4f6f9; font-family: 'Segoe UI', Arial, sans-serif; color: #1a1a1a; }
        .wrapper { max-width: 580px; margin: 32px auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.07); }
        .header { background: #0d1883; padding: 28px 32px; }
        .header img { height: 32px; margin-bottom: 12px; }
        .header h1 { color: #ffffff; font-size: 20px; margin: 0 0 4px; font-weight: 700; }
        .header p { color: rgba(255,255,255,0.75); font-size: 13px; margin: 0; }
        .alert-bar { padding: 12px 20px; font-size: 13.5px; font-weight: 600; }
        .alert-ok { background: #e8f5e9; border-left: 4px solid #2e7d32; color: #1b5e20; }
        .alert-fail { background: #fff4e5; border-left: 4px solid #e65100; color: #8a3a00; }
        .body { padding: 28px 32px; }
        .section-title { font-size: 11.5px; font-weight: 700; color: #0d1883; text-transform: uppercase; letter-spacing: 0.09em; margin: 0 0 12px; }
        .details-card { background: #f7f8ff; border: 1px solid #e0e4f8; border-radius: 10px; padding: 16px 20px; margin-bottom: 20px; }
        .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eaecf8; font-size: 13px; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #888; font-weight: 500; width: 45%; }
        .detail-value { color: #1a1a1a; font-weight: 600; text-align: right; width: 55%; }
        .amount-row .detail-value { color: #0d1883; font-size: 14.5px; }
        .ref-row .detail-value { font-family: monospace; font-size: 12px; }
        .footer { background: #f7f8ff; padding: 18px 32px; text-align: center; border-top: 1px solid #e8eaf5; }
        .footer p { font-size: 12px; color: #aaa; margin: 0; }
    </style>
</head>
<body>
<div class="wrapper">

    <div class="header">
        <img src="{{ asset('assets/twlogo.png') }}" alt="TravelWheel Logo">
        <h1>🛡️ New Insurance Purchase</h1>
        <p>A customer has paid for travel insurance</p>
    </div>

    @if ($failed)
        <div class="alert-bar alert-fail">
            ⚠️ Payment received, but the Sanlam policy could not be confirmed automatically. Please issue the policy manually and email the customer their documents.
        </div>
    @else
        <div class="alert-bar alert-ok">
            ✅ Payment received and policy confirmed with Sanlam
        </div>
    @endif

    <div class="body">
        <p class="section-title">Customer</p>
        <div class="details-card">
            <div class="detail-row"><span class="detail-label">Name</span><span class="detail-value">{{ $name ?: '—' }}</span></div>
            <div class="detail-row"><span class="detail-label">Email</span><span class="detail-value">{{ $purchase->email ?: '—' }}</span></div>
            <div class="detail-row"><span class="detail-label">Phone</span><span class="detail-value">{{ $purchase->phone_no ?: '—' }}</span></div>
            <div class="detail-row"><span class="detail-label">Passport No.</span><span class="detail-value">{{ $purchase->passport_no ?: '—' }}</span></div>
        </div>

        <p class="section-title">Policy</p>
        <div class="details-card">
            <div class="detail-row"><span class="detail-label">Plan</span><span class="detail-value">{{ $plan }}@if ((int) $purchase->noc > 0) · {{ $purchase->noc }} child{{ (int) $purchase->noc === 1 ? '' : 'ren' }}@endif</span></div>
            <div class="detail-row"><span class="detail-label">Cover ID</span><span class="detail-value">{{ $purchase->cover_id ?: 'Not issued' }}</span></div>
            <div class="detail-row"><span class="detail-label">Status</span><span class="detail-value">{{ $purchase->status }}</span></div>
        </div>

        <p class="section-title">Payment</p>
        <div class="details-card">
            <div class="detail-row amount-row"><span class="detail-label">Amount Paid</span><span class="detail-value">₦{{ number_format((float) $purchase->t_amount) }}</span></div>
            <div class="detail-row"><span class="detail-label">Method</span><span class="detail-value">{{ strtoupper((string) $purchase->payment_option) }}</span></div>
            <div class="detail-row ref-row"><span class="detail-label">Reference</span><span class="detail-value">{{ $purchase->trans_id }}</span></div>
        </div>
    </div>

    <div class="footer">
        <p>TravelWheel · Internal notification · {{ now()->format('D, j M Y g:i A') }}</p>
    </div>

</div>
</body>
</html>
