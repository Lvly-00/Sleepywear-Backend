<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrevoMailer
{
    private const API_URL = 'https://api.brevo.com/v3/smtp/email';

    public static function sendResetLink(string $email, string $resetUrl): bool
    {
        Log::info('BrevoMailer: Sending reset email', [
            'to' => $email,
            'reset_url' => substr($resetUrl, 0, 50).'...', // Truncate long URLs
        ]);

        $response = Http::withHeaders(self::headers())
            ->post(self::API_URL, self::resetEmailPayload($email, $resetUrl));

        // Log FULL response for debugging
        Log::info('Brevo API Response', [
            'status' => $response->status(),
            'successful' => $response->successful(),
            'body' => $response->body(),
            'message_id' => $response->json('messageId') ?? null,
            'error' => $response->json('error') ?? null,
        ]);

        if (! $response->successful()) {
            Log::error('Brevo API Failed', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
        }

        return $response->successful();
    }

    private static function headers(): array
    {
        return [
            'api-key' => config('services.brevo.api_key'),
            'accept' => 'application/json',
            'content-type' => 'application/json',
        ];
    }

    private static function resetEmailPayload(string $email, string $resetUrl): array
{
    return [
        'sender' => [
            'name' => config('mail.from.name', 'Sleepywear Security'),
            'email' => config('mail.from.address', 'lovelypintes@gmail.com'),
        ],
        'to' => [['email' => $email]],
        'subject' => 'Action Required: Reset Password',
        'htmlContent' => self::resetEmailTemplate($resetUrl),
    ];
}


    private static function resetEmailTemplate(string $resetUrl): string
    {
        return view('emails.password-reset', [
            'resetUrl' => $resetUrl,
            'year' => date('Y'),
        ])->render();
    }
}
