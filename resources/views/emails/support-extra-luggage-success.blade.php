<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Extra Luggage Payment Successful</title>
</head>
<body style="margin:0; padding:0; background-color:#f5f7fa; font-family:'Segoe UI', Arial, sans-serif; color:#333;">
    <table role="presentation" style="width:100%; border-collapse:collapse; background-color:#f5f7fa; padding:30px 0;">
        <tr>
            <td align="center">
                <table role="presentation" style="width:100%; max-width:650px; background:#ffffff; border-radius:10px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.08);">
                    
                    <tr>
                        <td style="background:#0D1883; padding:20px; text-align:center;">
                            <img src="https://your-travelwheel-logo-url.png" alt="TravelWheel" width="120" style="margin-bottom:8px;">
                            <h2 style="color:#ffffff; font-size:20px; margin:0;">Extra Luggage Payment Confirmed</h2>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:30px;">
                            <h1 style="font-size:24px; color:#0D1883; margin-bottom:15px;">
                                Hello {{ $name ?? 'Valued Customer' }},
                            </h1>

                            <p style="font-size:16px; line-height:1.6; margin-bottom:20px;">
                                We are happy to confirm that your payment for the **Extra Luggage** request was **successful**! We have received your request, ticket information, and passport data.
                            </p>

                            <table style="width:100%; border-collapse:collapse; font-size:15px; margin-bottom:25px; border:1px solid #ddd;">
                                <tr>
                                    <td style="padding:12px 8px; background-color:#f0f4f8; border-bottom:1px solid #ddd; width:40%;"><strong>Airline:</strong></td>
                                    <td style="padding:12px 8px; border-bottom:1px solid #ddd;">{{ $airline ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 8px; background-color:#f0f4f8; border-bottom:1px solid #ddd;"><strong>Amount Paid:</strong></td>
                                    <td style="padding:12px 8px; border-bottom:1px solid #ddd;">₦{{ number_format($amount) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 8px; background-color:#f0f4f8;"><strong>Reference ID:</strong></td>
                                    <td style="padding:12px 8px;">{{ $reference }}</td>
                                </tr>
                            </table>

                            <p style="font-size:15px; margin-bottom:10px;">
                                **What happens next?**
                            </p>
                            <ul style="font-size:15px; padding-left:20px; margin-bottom:25px;">
                                <li>Our team will begin processing your **Extra Luggage** request with the airline.</li>
                                <li>We will notify you via email when the request is confirmed or if any additional information is needed.</li>
                            </ul>
                            
                            <p style="font-size:15px; margin-top:20px;">
                                Thank you for choosing TravelWheel.
                            </p>

                            <p style="font-size:15px; margin-bottom:0;">
                                Sincerely,<br>
                                The TravelWheel Support Team
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="background:#f0f4f8; text-align:center; padding:18px;">
                            <p style="font-size:13px; color:#777; margin:0;">
                                &copy; {{ date('Y') }} TravelWheel. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>