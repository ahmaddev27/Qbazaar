<?php

declare(strict_types=1);

namespace App\Actions\Auth;

/**
 * Result of VerifyOtpAction. Currently only carries `phoneVerified` to mirror
 * the OpenAPI verify-otp response — but kept as a value object so we can
 * extend the payload (e.g. with a freshly-issued access token in future
 * passwordless flows) without changing call-site signatures.
 */
final readonly class VerifyOtpResult
{
    public function __construct(
        public bool $phoneVerified,
    ) {}
}
