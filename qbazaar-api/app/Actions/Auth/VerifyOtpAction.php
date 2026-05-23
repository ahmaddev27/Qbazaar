<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\Auth\OtpService;

/**
 * Verifies a presented OTP and — if a User row matches the phone — flips
 * `phone_verified` to true. The OTP itself is consumed regardless so it can't
 * be replayed.
 *
 * Phone-less (anonymous) verification is supported: if no User owns the
 * phone, the verification still succeeds (the caller is probably a
 * registration flow that hasn't created the user yet). We simply return
 * `phoneVerified = true` for the requester to act on.
 */
class VerifyOtpAction
{
    public function __construct(
        private readonly OtpService $otpService,
    ) {}

    public function execute(string $phone, string $code): VerifyOtpResult
    {
        $this->otpService->verify($phone, $code);

        /** @var User|null $user */
        $user = User::query()->where('phone', $phone)->first();

        if ($user !== null && ! $user->phone_verified) {
            $user->forceFill(['phone_verified' => true])->save();
        }

        return new VerifyOtpResult(phoneVerified: true);
    }
}
