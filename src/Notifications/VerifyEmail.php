<?php

namespace Renepardon\LighthouseGraphQLPassport\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as IlluminateVerifyEmail;
use Illuminate\Support\Carbon;

class VerifyEmail extends IlluminateVerifyEmail
{
    /**
     * Get the verification URL for the given notifiable
     *
     * @param mixed $notifiable
     *
     * @return string
     */
    protected function verificationUrl($notifiable): string
    {
        $payload = base64_encode(json_encode([
            'id'         => $notifiable->getKey(),
            'hash'       => encrypt($notifiable->getEmailForVerification()),
            'expiration' => encrypt(Carbon::now()->addMinutes(10)->toIso8601String()),
        ]));

        return config('lighthouse-graphql-passport.verify_email.base_url') . '?token=' . $payload;
    }
}
