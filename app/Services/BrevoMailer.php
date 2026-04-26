<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrevoMailer
{
    private const API_URL = 'https://api.brevo.com/v3/smtp/email';

    private static function headers(): array
    {
        return [
            'api-key' => config('services.brevo.api_key'),
            'accept' => 'application/json',
            'content-type' => 'application/json',
        ];
    }

    /**
     * Send OTP Email with dynamic subject
     */
    public static function sendOtpEmail(string $email, string $otp, string $purpose = 'reset'): bool
    {
        // Change subject based on purpose
        $subject = ($purpose === 'biometric')
            ? 'Your Biometric Registration Code'
            : 'Your Password Reset Code';

        $payload = [
            'sender' => [
                'name' => config('mail.from.name', 'Sleepywear Security'),
                'email' => config('mail.from.address', 'lovelypintes@gmail.com'),
            ],
            'to' => [['email' => $email]],
            'subject' => $subject,
            'htmlContent' => view('emails.password-reset', [
                'otp' => $otp,
                'year' => date('Y'),
                'purpose' => $purpose 
            ])->render(),
        ];

        $response = Http::withHeaders(self::headers())->post(self::API_URL, $payload);

        if (!$response->successful()) {
            Log::error('Brevo OTP Failed: ' . $response->body());
        }

        return $response->successful();
    }
}
