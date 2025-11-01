<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class BrevoMailer
{
    public static function sendResetLink($email, $resetUrl)
    {
        $apiKey = env('BREVO_API_KEY');
        $year = date('Y');

        // Compose full HTML email content (using your design/colors)
        $htmlContent = "
        <!DOCTYPE html>
        <html lang=\"en\">
        <head>
          <meta charset=\"UTF-8\" />
          <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\" />
          <title>Reset Password - Sleepywear</title>
          <style>
            body {
              margin: 0;
              font-family: 'Poppins', Arial, sans-serif;
              background-color: #f1f0ed;
              color: #24293b;
            }
            .email-container {
              max-width: 600px;
              margin: 40px auto;
              background: #ffffff;
              border-radius: 16px;
              overflow: hidden;
              box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            }
            .header {
              background-color: #24293b;
              padding: 24px;
              text-align: center;
            }
            .header h1 {
              color: #ffd36b;
              font-size: 28px;
              font-weight: 600;
              margin: 0;
              letter-spacing: 0.5px;
            }
            .body {
              padding: 30px;
              text-align: left;
              background-color: #f1f0ed;
            }
            .body h2 {
              color: #24293b;
              font-size: 22px;
              margin-bottom: 10px;
            }
            .body p {
              font-size: 16px;
              line-height: 1.6;
              margin: 10px 0;
              color: #24293b;
            }
            .button {
              display: inline-block;
              margin: 25px 0;
              padding: 12px 24px;
              background-color: #ab8262;
              color: #ffffff !important;
              text-decoration: none;
              border-radius: 12px;
              font-weight: 500;
              font-size: 16px;
              letter-spacing: 0.3px;
              transition: background 0.3s;
            }
            .button:hover {
              background-color: #936d51;
            }
            .footer {
              background-color: #24293b;
              color: #f1f0ed;
              text-align: center;
              padding: 20px;
              font-size: 14px;
            }
            .footer p {
              margin: 0;
            }
            @media (max-width: 600px) {
              .email-container {
                margin: 10px;
                border-radius: 12px;
              }
              .body {
                padding: 20px;
              }
              .header h1 {
                font-size: 24px;
              }
              .button {
                padding: 10px 18px;
                font-size: 15px;
              }
            }
          </style>
        </head>
        <body>
          <div class=\"email-container\">
            <!-- Header -->
            <div class=\"header\">
              <h1>Sleepywear</h1>
            </div>

            <!-- Body -->
            <div class=\"body\">
              <h2>Reset Your Password</h2>
              <p>Hello there,</p>
              <p>
                You requested to reset your Sleepywear account password.
                Click the button below to securely update your password:
              </p>

              <a href=\"{$resetUrl}\" class=\"button\">Reset Password</a>

              <p>If you didn’t request this, no worries — you can safely ignore this email.</p>

            </div>

            <!-- Footer -->
            <div class=\"footer\">
              <p>© {$year} Sleepywear. All rights reserved.</p>
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
                'name' => 'Sleepywear',
                'email' => 'lovelypintes@gmail.com',
            ],
            'to' => [
                ['email' => $email],
            ],
            'subject' => 'Reset Your Sleepywear Password',
            'htmlContent' => $htmlContent,
        ]);

        return $response->successful();
    }
}
