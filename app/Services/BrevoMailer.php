<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class BrevoMailer
{
    public static function sendResetLink($email, $resetUrl)
    {
        $apiKey = env('BREVO_API_KEY');

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
            'htmlContent' => "
                <h2>Password Reset</h2>
                <p>Click the link below to reset your password:</p>
                <p><a href='{$resetUrl}'>{$resetUrl}</a></p>
                <br><p>If you didn’t request this, you can ignore this email.</p>
            ",
        ]);

        return $response->successful();
    }
}
