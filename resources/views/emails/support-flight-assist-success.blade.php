<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Request Received - TravelWheel</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f6f8; font-family:'Segoe UI', Arial, sans-serif; color:#333;">
  <table role="presentation" style="width:100%; border-collapse:collapse; background-color:#f4f6f8; padding:30px 0;">
    <tr>
      <td align="center">
        <table role="presentation" style="width:100%; max-width:600px; background:#ffffff; border-radius:10px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.08);">

          <!-- Header -->
          <tr>
            <td style="background:#1369FF; padding:25px; text-align:center;">
              <img src="{{ asset('assets/twlogo.png') }}" alt="TravelWheel" width="120" style="margin-bottom:8px;">
              <h1 style="color:#ffffff; font-size:22px; margin:0;">Request Received 🎉</h1>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:30px;">
              <p style="font-size:16px;">Dear <strong>{{ $name }}</strong>,</p>
              <p style="font-size:15px; line-height:1.6;">
                Thank you for submitting your <strong>{{ $service }}</strong> request.
                We’ve received it and our support team will begin processing it shortly.
              </p>

              <table style="width:100%; border-collapse:collapse; margin:20px 0;">
                <tr>
                  <td style="padding:10px 0; border-bottom:1px solid #eee;">
                    <strong>Service Fee:</strong>
                  </td>
                  <td style="padding:10px 0; border-bottom:1px solid #eee; text-align:right;">
                    ₦{{ $amount }} (billed with your main flight fee)
                  </td>
                </tr>
                <tr>
                  <td style="padding:10px 0; border-bottom:1px solid #eee;">
                    <strong>Reference:</strong>
                  </td>
                  <td style="padding:10px 0; border-bottom:1px solid #eee; text-align:right;">
                    {{ $reference }}
                  </td>
                </tr>
                <tr>
                  <td style="padding:10px 0;">
                    <strong>Service:</strong>
                  </td>
                  <td style="padding:10px 0; text-align:right;">
                    {{ $service }}
                  </td>
                </tr>
              </table>

              <p style="font-size:15px; line-height:1.6;">
                No payment is required from you now — this fee will be charged together with your main flight booking fee.
              </p>

              <p style="font-size:15px;">Thank you for choosing <strong>TravelWheel</strong> — we’re always here to make your journey smoother.</p>

              <div style="text-align:center; margin-top:30px;">
                <a href="https://travelwheel.ng" style="display:inline-block; background:#1369FF; color:#fff; text-decoration:none; padding:12px 28px; border-radius:6px; font-size:15px;">
                  Visit Our Website
                </a>
              </div>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background:#f0f4f8; text-align:center; padding:20px;">
              <p style="font-size:13px; color:#777; margin:0;">
                &copy; {{ date('Y') }} TravelWheel. All rights reserved.
              </p>
              <p style="font-size:12px; color:#aaa; margin-top:8px;">
                This is an automated message — please do not reply.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
