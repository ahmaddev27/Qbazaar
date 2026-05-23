<?php

declare(strict_types=1);

namespace App\Services\Auth;

/**
 * Value object returned by OtpService::issue() — bundles the freshly-minted
 * raw code and its timing metadata so the controller can both hand it to the
 * SMS channel and shape the response envelope without re-deriving them.
 */
final readonly class OtpIssueResult
{
    public function __construct(
        public string $phone,
        public string $rawCode,
        public int $expiresIn,
        public int $canResendIn,
    ) {}
}
