<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Car Hire Booking</title>
    <style>
        body { margin: 0; padding: 0; background: #f4f6f9; font-family: 'Segoe UI', Arial, sans-serif; color: #1a1a1a; }
        .wrapper { max-width: 580px; margin: 32px auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.07); }
        .header { background: #0d1883; padding: 28px 32px; }
        .header h1 { color: #ffffff; font-size: 20px; margin: 0 0 4px; font-weight: 700; }
        .header p { color: rgba(255,255,255,0.75); font-size: 13px; margin: 0; }
        .alert-bar { background: #e8f5e9; border-left: 4px solid #2e7d32; padding: 12px 20px; font-size: 13.5px; color: #1b5e20; font-weight: 600; }
        .body { padding: 28px 32px; }
        .section-title { font-size: 11.5px; font-weight: 700; color: #0d1883; text-transform: uppercase; letter-spacing: 0.09em; margin: 0 0 12px; }
        .details-card { background: #f7f8ff; border: 1px solid #e0e4f8; border-radius: 10px; padding: 16px 20px; margin-bottom: 20px; }
        .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eaecf8; font-size: 13px; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #888; font-weight: 500; width: 45%; }
        .detail-value { color: #1a1a1a; font-weight: 600; text-align: right; width: 55%; }
        .amount-row .detail-value { color: #0d1883; font-size: 14.5px; }
        .ref-row .detail-value { font-family: monospace; font-size: 12px; }
        .paid-badge { display: inline-block; background: #e8f5e9; color: #2e7d32; font-size: 11.5px; font-weight: 700; padding: 3px 10px; border-radius: 20px; }
        .footer { background: #f7f8ff; padding: 18px 32px; text-align: center; border-top: 1px solid #e8eaf5; }
        .footer p { font-size: 12px; color: #aaa; margin: 0; }
    </style>
</head>
<body>
<div class="wrapper">

    <div class="header">
        <img src="{{ asset("assets/twlogo.png") }}" alt="TravelWheel Logo">
        <h1>🚗 New Car Hire Booking</h1>
        <p>A new booking has been paid and confirmed</p>
    </div>

    <div class="alert-bar">
        ✅ Payment confirmed — action required to assign driver
    </div>

    <div class="body">

        <div class="details-card">
            <p class="section-title">👤 Customer Details</p>
            <div class="detail-row">
                <span class="detail-label">Full Name</span>
                <span class="detail-value">{{ $name }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Email</span>
                <span class="detail-value">{{ $email }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Phone</span>
                <span class="detail-value">{{ $phone }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Passengers</span>
                <span class="detail-value">{{ $passengers }}</span>
            </div>
        </div>

        <div class="details-card">
            <p class="section-title">🚘 Vehicle Details</p>
            <div class="detail-row">
                <span class="detail-label">Vehicle Type</span>
                <span class="detail-value">{{ $car_type }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Vehicle Model</span>
                <span class="detail-value">{{ $car_model }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Category</span>
                <span class="detail-value">{{ $category }}</span>
            </div>
        </div>

        <div class="details-card">
            <p class="section-title">📍 Trip Details</p>
            <div class="detail-row">
                <span class="detail-label">Pick-up Location</span>
                <span class="detail-value">{{ $pickup_location }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Drop-off Location</span>
                <span class="detail-value">{{ $dropoff_location }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Pick-up Date</span>
                <span class="detail-value">{{ \Carbon\Carbon::parse($pickup_date)->format('D, d M Y') }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Pick-up Time</span>
                <span class="detail-value">{{ \Carbon\Carbon::parse($pickup_time)->format('g:i A') }}</span>
            </div>
        </div>

        <div class="details-card">
            <p class="section-title">💳 Payment Details</p>
            <div class="detail-row amount-row">
                <span class="detail-label">Amount</span>
                <span class="detail-value">₦{{ number_format($amount) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Gateway</span>
                <span class="detail-value">{{ $payment_option }}</span>
            </div>
            <div class="detail-row ref-row">
                <span class="detail-label">Reference</span>
                <span class="detail-value">{{ $reference }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="detail-value"><span class="paid-badge">PAID</span></span>
            </div>
        </div>

    </div>

    <div class="footer">
        <p>TravelWheel Internal Notification · {{ date('d M Y, g:i A') }}</p>
    </div>

</div>
</body>
</html>