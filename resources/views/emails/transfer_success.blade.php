<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer Booking Confirmed</title>
    <style>
        body { margin: 0; padding: 0; background: #f4f6f9; font-family: 'Segoe UI', Arial, sans-serif; color: #1a1a1a; }
        .wrapper { max-width: 580px; margin: 32px auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.07); }
        .header { background: linear-gradient(135deg, #0d1883, #2d39b6); padding: 36px 32px; text-align: center; }
        .header img { height: 36px; margin-bottom: 16px; }
        .header h1 { color: #ffffff; font-size: 22px; margin: 0 0 6px; font-weight: 700; }
        .header p { color: rgba(255,255,255,0.8); font-size: 14px; margin: 0; }
        .badge { display: inline-block; background: rgba(255,255,255,0.2); color: #fff; font-size: 12px; font-weight: 600; padding: 4px 14px; border-radius: 20px; margin-top: 12px; letter-spacing: 0.05em; }
        .body { padding: 32px; }
        .greeting { font-size: 16px; margin-bottom: 16px; color: #333; }
        .greeting strong { color: #0d1883; }
        .intro { font-size: 14px; color: #555; line-height: 1.7; margin-bottom: 24px; }
        .details-card { background: #f7f8ff; border: 1px solid #e0e4f8; border-radius: 12px; padding: 20px 24px; margin-bottom: 24px; }
        .details-card h3 { font-size: 13px; font-weight: 700; color: #0d1883; letter-spacing: 0.08em; text-transform: uppercase; margin: 0 0 16px; }
        .detail-row { display: flex; justify-content: space-between; padding: 9px 0; border-bottom: 1px solid #e8eaf5; font-size: 13.5px; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #888; font-weight: 500; }
        .detail-value { color: #1a1a1a; font-weight: 600; text-align: right; max-width: 60%; }
        .amount-row .detail-value { color: #0d1883; font-size: 15px; }
        .ref-row .detail-value { font-family: monospace; font-size: 12.5px; color: #555; }
        .note { background: #fffbea; border-left: 3px solid #f5a623; border-radius: 6px; padding: 12px 16px; font-size: 13px; color: #6b4a00; line-height: 1.6; margin-bottom: 24px; }
        .footer { background: #f7f8ff; padding: 24px 32px; text-align: center; border-top: 1px solid #e8eaf5; }
        .footer p { font-size: 12.5px; color: #999; margin: 4px 0; line-height: 1.6; }
        .footer a { color: #0d1883; text-decoration: none; }
    </style>
</head>
<body>
<div class="wrapper">

    <div class="header">
        <img src="{{ asset("assets/twlogo.png") }}" alt="TravelWheel Logo">
        <h1>Transfer Confirmed! 🚘</h1>
        <p>Your transfer booking has been successfully booked</p>
        <span class="badge">PAYMENT RECEIVED</span>
    </div>

    <div class="body">
        <p class="greeting">Hello, <strong>{{ $name }}</strong> 👋</p>
        <p class="intro">
            Great news — your transfer booking is confirmed and payment has been received.
            Please find your booking details below. Our team will be in touch shortly to confirm your driver assignment.
        </p>

        <div class="details-card">
            <h3>🚗 Vehicle Details</h3>
            <div class="detail-row">
                <span class="detail-label">Vehicle Type</span>
                <span class="detail-value">{{ $vehicle_type }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Vehicle Name</span>
                <span class="detail-value">{{ $vehicle_name }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Passengers</span>
                <span class="detail-value">{{ $passengers }}</span>
            </div>
        </div>

        <div class="details-card">
            <h3>📍 Trip Details</h3>
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
            <div class="detail-row">
                <span class="detail-label">Distance</span>
                <span class="detail-value">{{ number_format($distance_km, 1) }} km</span>
            </div>
            @if($flight_number)
            <div class="detail-row">
                <span class="detail-label">Flight Number</span>
                <span class="detail-value">{{ $flight_number }}</span>
            </div>
            @endif
            @if($special_requests)
            <div class="detail-row">
                <span class="detail-label">Special Requests</span>
                <span class="detail-value">{{ $special_requests }}</span>
            </div>
            @endif
        </div>

        <div class="details-card">
            <h3>💳 Payment Details</h3>
            <div class="detail-row amount-row">
                <span class="detail-label">Amount Paid</span>
                <span class="detail-value">₦{{ number_format($amount) }}</span>
            </div>
            <div class="detail-row ref-row">
                <span class="detail-label">Reference</span>
                <span class="detail-value">{{ $reference }}</span>
            </div>
        </div>

        <div class="note">
            📌 Please save your booking reference <strong>{{ $reference }}</strong> for any enquiries.
            If you need to make changes, contact us at least 2 hours before your pick-up time.
        </div>
    </div>

    <div class="footer">
        <p>Need help? Contact us at <a href="mailto:support@travelwheel.ng">support@travelwheel.ng</a></p>
        <p>© {{ date('Y') }} TravelWheel. All rights reserved.</p>
    </div>

</div>
</body>
</html>