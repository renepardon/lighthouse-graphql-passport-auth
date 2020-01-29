<?php

namespace Renepardon\LighthouseGraphQLPassport;

use Renepardon\LighthouseGraphQLPassport\Notifications\VerifyEmail;

trait MustVerifyEmailGraphQL
{
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmail());
    }
}
