<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Protocol Service Pass</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; color: #333; }
        .header { text-align: center; border-bottom: 3px solid #0d9c53; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { color: #0d1883; font-size: 22px; margin: 0; }
        .header h2 { color: #0d9c53; font-size: 16px; margin: 5px 0 0; }
        .badge { background: #0d1883; color: #fff; padding: 4px 14px; border-radius: 20px; font-size: 13px; display: inline-block; }
        .card { border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 15px; }
        .grid { display: table; width: 100%; }
        .grid-row { display: table-row; }
        .grid-cell { display: table-cell; padding: 5px 8px; width: 50%; }
        .label { font-size: 10px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; }
        .value { font-size: 13px; font-weight: bold; color: #222; }
        .passenger-block { page-break-inside: avoid; margin-bottom: 20px; }
        .pass-divider { border: 2px dashed #0d9c53; margin: 20px 0; }
        .footer { text-align: center; font-size: 11px; color: #777; margin-top: 30px; }
        .status { background: #d4edda; color: #155724; padding: 3px 10px; border-radius: 4px; font-weight: bold; }
        .disclaimer { border: 1px solid #ddd; border-left: 4px solid #0d1883; border-radius: 6px; padding: 10px 14px; margin-top: 10px; page-break-inside: avoid; }
        .disclaimer h4 { color: #0d1883; font-size: 12px; margin: 0 0 6px; text-transform: uppercase; letter-spacing: 0.5px; }
        .disclaimer p { font-size: 10.5px; line-height: 1.5; color: #444; margin: 0; text-align: justify; }
    </style>
</head>
<body>
    <div class="header">
        <h1>TravelWheel</h1>
        <h2>Airport Protocol Service Pass</h2>
        <p><span class="badge">{{ $package }} Plan</span> &nbsp; <span class="status">CONFIRMED</span></p>
    </div>

    <div class="card">
        <h4 style="color:#0d1883; margin-top:0;">Service Information</h4>
        <div class="grid">
            <div class="grid-row">
                <div class="grid-cell">
                    <div class="label">Location</div>
                    <div class="value">{{ $booking->state }}</div>
                </div>
                <div class="grid-cell">
                    <div class="label">Airport</div>
                    <div class="value">{{ $booking->airport }}</div>
                </div>
            </div>
            <div class="grid-row">
                <div class="grid-cell">
                    <div class="label">Service Type</div>
                    <div class="value">{{ $booking->service_type }}</div>
                </div>
                <div class="grid-cell">
                    <div class="label">Travel Date</div>
                    <div class="value">{{ $booking->travel_date?->format('d M Y') }}</div>
                </div>
            </div>
            <div class="grid-row">
                <div class="grid-cell">
                    <div class="label">{{ $booking->service_type }} Time</div>
                    <div class="value">{{ $booking->d_time }}</div>
                </div>
                <div class="grid-cell">
                    <div class="label">Airline</div>
                    <div class="value">{{ $booking->airline }}</div>
                </div>
            </div>
            <div class="grid-row">
                <div class="grid-cell">
                    <div class="label">Contact Email</div>
                    <div class="value">{{ $booking->email }}</div>
                </div>
                <div class="grid-cell">
                    <div class="label">Phone</div>
                    <div class="value">{{ $booking->phone }}</div>
                </div>
            </div>
            <div class="grid-row">
                <div class="grid-cell">
                    <div class="label">Transaction Reference</div>
                    <div class="value" style="font-size:11px;">{{ $booking->trans_id }}</div>
                </div>
                <div class="grid-cell">
                    <div class="label">Amount Paid</div>
                    {{-- Arial has no ₦ in dompdf (it printed "?"); DejaVu Sans, bundled with dompdf, does --}}
                    <div class="value"><span style="font-family: 'DejaVu Sans', sans-serif;">&#8358;</span>{{ number_format($booking->amount, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="pass-divider"></div>

    @foreach($fullnames as $i => $name)
    <div class="passenger-block">
        <div class="card">
            <h4 style="color:#0d1883; margin-top:0;">Passenger {{ $i + 1 }}</h4>
            <div class="grid">
                <div class="grid-row">
                    <div class="grid-cell">
                        <div class="label">Full Name</div>
                        <div class="value">{{ $name }}</div>
                    </div>
                    <div class="grid-cell">
                        <div class="label">Reservation Code (PNR)</div>
                        <div class="value">{{ $pnrs[$i] ?? 'N/A' }}</div>
                    </div>
                </div>
                <div class="grid-row">
                    <div class="grid-cell">
                        <div class="label">e-Ticket Number</div>
                        <div class="value">{{ $ticketNos[$i] ?? 'N/A' }}</div>
                    </div>
                    <div class="grid-cell">
                        <div class="label">Number of Bags</div>
                        <div class="value">{{ $nobs[$i] ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach

    {{-- Wording carried over from the original protocol pass (ticket.blade.php on the old site) --}}
    <div class="disclaimer">
        <h4>Disclaimer</h4>
        <p>
            This airport protocol service disclaimer specifies the terms and conditions that govern the use of the airport protocol service.
            The service provider shall not be liable for any delays, cancellations, or changes in the airline's schedule. Users are advised
            to check with the airline for any last-minute changes. The service provider shall not be responsible for any loss or damage to
            personal belongings. Users are advised to keep their belongings secured at all times. The service provider reserves the right to
            refuse service to any individual. By using the airport protocol service, users also agree to abide by all government agencies'
            rules because they deserve the right to check and value items in any bag and apply due charges as the case may be. Any government
            agency can nullify any terms and conditions set forth by the service provider.
        </p>
    </div>

    <div class="footer">
        <p>This pass is valid only for the travel date stated above.</p>
        <p>TravelWheel | info@travelwheel.ng | www.travelwheel.ng</p>
        <p>Generated: {{ now()->format('d M Y H:i') }}</p>
    </div>
</body>
</html>
