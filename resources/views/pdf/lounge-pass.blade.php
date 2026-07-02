<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Airport Lounge Pass</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; color: #333; }
        .header { text-align: center; border-bottom: 3px solid #0d9c53; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { color: #0d1883; font-size: 22px; margin: 0; }
        .header h2 { color: #0d9c53; font-size: 16px; margin: 5px 0 0; }
        .badge { background: #0d1883; color: #fff; padding: 4px 14px; border-radius: 20px; font-size: 13px; display: inline-block; }
        .status { background: #d4edda; color: #155724; padding: 3px 10px; border-radius: 4px; font-weight: bold; }
        .card { border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 15px; }
        .grid { display: table; width: 100%; }
        .grid-row { display: table-row; }
        .grid-cell { display: table-cell; padding: 5px 8px; width: 50%; }
        .label { font-size: 10px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; }
        .value { font-size: 13px; font-weight: bold; color: #222; }
        .pass-divider { border: 2px dashed #0d9c53; margin: 20px 0; }
        .footer { text-align: center; font-size: 11px; color: #777; margin-top: 30px; }
        .ticket-id { font-size: 14px; color: #0d1883; font-weight: bold; letter-spacing: 1px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>TravelWheel</h1>
        <h2>Airport Lounge Pass</h2>
        <p><span class="badge">{{ $booking->lounge_name }}</span> &nbsp; <span class="status">CONFIRMED</span></p>
    </div>

    <div class="card">
        <h4 style="color:#0d1883; margin-top:0;">Lounge Information</h4>
        <div class="grid">
            <div class="grid-row">
                <div class="grid-cell">
                    <div class="label">Lounge Name</div>
                    <div class="value">{{ $booking->lounge_name }}</div>
                </div>
                <div class="grid-cell">
                    <div class="label">Travel Date</div>
                    <div class="value">{{ $booking->travel_date?->format('d M Y') }}</div>
                </div>
            </div>
            <div class="grid-row">
                <div class="grid-cell">
                    <div class="label">Departure Time</div>
                    <div class="value">{{ $booking->d_time }}</div>
                </div>
                <div class="grid-cell">
                    <div class="label">Airline</div>
                    <div class="value">{{ $booking->airline }}</div>
                </div>
            </div>
            <div class="grid-row">
                <div class="grid-cell">
                    <div class="label">Passengers</div>
                    <div class="value">Adult ({{ $booking->noa }}) Child ({{ $booking->noc }}) Infant ({{ $booking->noi }})</div>
                </div>
                <div class="grid-cell">
                    <div class="label">Amount Paid</div>
                    <div class="value">₦{{ number_format($booking->amount, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="pass-divider"></div>

    <div class="card">
        <h4 style="color:#0d1883; margin-top:0;">Guest Information</h4>
        <div class="grid">
            <div class="grid-row">
                <div class="grid-cell">
                    <div class="label">Full Name</div>
                    <div class="value">{{ $booking->fullname }}</div>
                </div>
                <div class="grid-cell">
                    <div class="label">Phone Number</div>
                    <div class="value">{{ $booking->phone_no }}</div>
                </div>
            </div>
            <div class="grid-row">
                <div class="grid-cell">
                    <div class="label">Email Address</div>
                    <div class="value">{{ $booking->email }}</div>
                </div>
                <div class="grid-cell">
                    <div class="label">Transaction Reference</div>
                    <div class="value" style="font-size:11px;">{{ $booking->trans_id }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>This pass is valid only for the travel date stated above. It expires after the travel date.</p>
        <p>TravelWheel | info@travelwheel.ng | www.travelwheel.ng</p>
        <p>Generated: {{ now()->format('d M Y H:i') }}</p>
    </div>
</body>
</html>
