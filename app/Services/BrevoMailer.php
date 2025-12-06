<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class BrevoMailer
{
    public static function sendResetLink($email, $resetUrl)
    {
        $apiKey = env('BREVO_API_KEY');
        $year = date('Y');

        // Brand Colors based on your React App
        $brandColor = '#232D80'; // Deep Blue
        $brandAccent = '#F0F2F5'; // Light Background

        $htmlContent = "
        <!DOCTYPE html>
        <html lang=\"en\">
        <head>
          <meta charset=\"UTF-8\" />
          <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\" />
          <title>Reset Password - Sleepywear Inventory</title>
          <style type=\"text/css\">
            /* Reset styles */
            body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
            table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
            img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }

            /* General */
            body {
              margin: 0;
              padding: 0;
              background-color: {$brandAccent};
              font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
              color: #333333;
            }
            .container {
              width: 100%;
              max-width: 600px;
              margin: 0 auto;
            }
            .email-card {
              background-color: #ffffff;
              border-radius: 8px;
              overflow: hidden;
              box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
              margin-top: 40px;
              margin-bottom: 20px;
              border-top: 4px solid {$brandColor};
            }
            .header {
              padding: 30px 40px 10px 40px;
              text-align: center;
            }
            .header h1 {
              color: {$brandColor};
              font-size: 24px;
              font-weight: 700;
              margin: 0;
              text-transform: uppercase;
              letter-spacing: 1px;
            }
            .sub-header {
              color: #8898aa;
              font-size: 12px;
              text-transform: uppercase;
              letter-spacing: 1px;
              margin-top: 5px;
            }
            .content {
              padding: 20px 40px 40px 40px;
              text-align: center;
            }
            .icon-circle {
              background-color: #eef2ff;
              color: {$brandColor};
              width: 60px;
              height: 60px;
              border-radius: 50%;
              line-height: 60px;
              font-size: 24px;
              margin: 0 auto 20px auto;
              font-weight: bold;
              display: block;
            }
            h2 {
              font-size: 20px;
              font-weight: 600;
              margin-bottom: 16px;
              color: #1a1a1a;
            }
            p {
              font-size: 15px;
              line-height: 1.6;
              color: #525f7f;
              margin-bottom: 24px;
            }
            .btn {
              display: inline-block;
              padding: 14px 32px;
              background-color: {$brandColor};
              color: #ffffff !important;
              text-decoration: none;
              border-radius: 6px;
              font-weight: 600;
              font-size: 15px;
              transition: background-color 0.3s ease;
            }
            .btn:hover {
              background-color: #1a2260;
            }
            .secondary-text {
              font-size: 12px;
              color: #8898aa;
              margin-top: 30px;
              line-height: 1.5;
              border-top: 1px solid #f0f0f0;
              padding-top: 20px;
            }
            .link-fallback {
              color: {$brandColor};
              word-break: break-all;
            }
            .footer {
              text-align: center;
              padding: 20px;
              font-size: 12px;
              color: #8898aa;
            }
          </style>
        </head>
        <body>
          <div class=\"container\">
            <div class=\"email-card\">

              <!-- Header -->
              <div class=\"header\">
                <h1>Sleepywear</h1>
                <div class=\"sub-header\">Inventory Management System</div>
              </div>

              <!-- Body -->
              <div class=\"content\">

                <!-- Visual Icon (Lock Symbol) -->
                <div class=\"icon-circle\">&#128274;</div>

                <h2>Password Reset Request</h2>

                <p>
                  We received a request to reset the password for your <strong>Sleepywear Inventory</strong> account.
                  To ensure account security, this link will expire in 60 minutes.
                </p>

                <a href=\"{$resetUrl}\" class=\"btn\">Reset Password</a>

                <p style=\"margin-top: 30px; font-size: 14px;\">
                  If you did not initiate this request, please disregard this email. Your password will remain unchanged.
                </p>

                <!-- Technical Fallback -->
                <div class=\"secondary-text\">
                  <p style=\"margin-bottom: 5px;\">Button not working? Copy and paste the link below into your browser:</p>
                  <a href=\"{$resetUrl}\" class=\"link-fallback\">{$resetUrl}</a>
                </div>

              </div>
            </div>

            <!-- Footer -->
            <div class=\"footer\">
              <p>&copy; {$year} Sleepywear. All rights reserved.<br>
              This is an automated system message. Please do not reply.</p>
            </div>
          </div>
        </body>
        </html>
        ";

        $response = Http::withHeaders([
            'api-key' => $apiKey,
            'accept' => 'application/json',
            'content-type' => 'application/json',
        ])->post('https://api.brevo.com/v3/smtp/email', [
            'sender' => [
                'name' => 'Sleepywear Security', // Updated Sender Name
                'email' => 'lovelypintes@gmail.com', // Ideally update this to no-reply@sleepywear.com if possible later
            ],
            'to' => [
                ['email' => $email],
            ],
            'subject' => 'Action Required: Reset Password',
            'htmlContent' => $htmlContent,
        ]);

        return $response->successful();
    }
}
