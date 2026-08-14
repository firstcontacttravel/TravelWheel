<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>New Flight Assist Request</title>
</head>
<body style="margin:0; padding:0; background-color:#f5f7fa; font-family:'Segoe UI', Arial, sans-serif; color:#333;">
  <table role="presentation" style="width:100%; border-collapse:collapse; background-color:#f5f7fa; padding:30px 0;">
    <tr>
      <td align="center">
        <table role="presentation" style="width:100%; max-width:650px; background:#ffffff; border-radius:10px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.08);">

          <!-- Header -->
          <tr>
            <td style="background:#1369FF; padding:20px; text-align:center;">
              <img src="https://your-travelwheel-logo-url.png" alt="TravelWheel" width="120" style="margin-bottom:8px;">
              <h2 style="color:#ffffff; font-size:20px; margin:0;">New Flight Assist Request</h2>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:30px;">
              <p style="font-size:16px; margin-bottom:10px;">
                Hello Support Team,
              </p>

              <p style="font-size:15px; line-height:1.6; margin-bottom:20px;">
                A client has submitted a <strong>{{ $service }}</strong> support request. The ₦{{ $amount }} fee is
                not paid yet — it will be billed together with the client's main flight fee. Below are the request details:
              </p>

              <!-- Details Table -->
              <table style="width:100%; border-collapse:collapse; font-size:15px; margin-bottom:25px;">
                <tr>
                  <td style="padding:8px; border-bottom:1px solid #eee;"><strong>Client Name:</strong></td>
                  <td style="padding:8px; border-bottom:1px solid #eee;">{{ $name }}</td>
                </tr>
                <tr>
                  <td style="padding:8px; border-bottom:1px solid #eee;"><strong>Email:</strong></td>
                  <td style="padding:8px; border-bottom:1px solid #eee;">{{ $email }}</td>
                </tr>
                <tr>
                  <td style="padding:8px; border-bottom:1px solid #eee;"><strong>Phone:</strong></td>
                  <td style="padding:8px; border-bottom:1px solid #eee;">{{ $phone }}</td>
                </tr>
                <tr>
                  <td style="padding:8px; border-bottom:1px solid #eee;"><strong>Service Type:</strong></td>
                  <td style="padding:8px; border-bottom:1px solid #eee;">{{ $service }}</td>
                </tr>
                <tr>
                  <td style="padding:8px; border-bottom:1px solid #eee;"><strong>Booking Source:</strong></td>
                  <td style="padding:8px; border-bottom:1px solid #eee;">{{ $booking_source }}</td>
                </tr>
                <tr>
                  <td style="padding:8px; border-bottom:1px solid #eee;"><strong>Fee (billed with main fee):</strong></td>
                  <td style="padding:8px; border-bottom:1px solid #eee;">₦{{ $amount }}</td>
                </tr>
                <tr>
                  <td style="padding:8px; border-bottom:1px solid #eee;"><strong>Reference:</strong></td>
                  <td style="padding:8px; border-bottom:1px solid #eee;">{{ $reference }}</td>
                </tr>
                <tr>
                  <td style="padding:8px;"><strong>Additional Info:</strong></td>
                  <td style="padding:8px;">{{ $additional_info ?? 'N/A' }}</td>
                </tr>
              </table>

              <p style="font-size:15px;">
                Please log into the admin dashboard to view more details or begin processing the client’s request.
              </p>

              <div style="text-align:center; margin-top:25px;">
                <a href="{{ url('/admin/support-flight-assists') }}" style="display:inline-block; background:#1369FF; color:#fff; text-decoration:none; padding:12px 28px; border-radius:6px; font-size:15px;">
                  View in Dashboard
                </a>
              </div>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background:#f0f4f8; text-align:center; padding:18px;">
              <p style="font-size:13px; color:#777; margin:0;">
                &copy; {{ date('Y') }} TravelWheel Support Department.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
