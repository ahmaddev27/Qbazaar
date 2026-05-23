<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\SendOtpAction;
use App\Actions\Auth\VerifyOtpAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\OtpSendRequest;
use App\Http\Requests\Api\V1\Auth\OtpVerifyRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Phone-OTP endpoints. All three methods are deliberately thin — heavy
 * lifting lives in App\Actions\Auth\SendOtpAction / VerifyOtpAction so the
 * cooldown + max-per-hour rules can be unit-tested without bringing HTTP
 * into the picture.
 */
class OtpController extends Controller
{
    /**
     * POST /api/v1/auth/send-otp
     *
     * Returns 202 + { sent_to, expires_in, can_resend_in }.
     */
    public function send(OtpSendRequest $request, SendOtpAction $action): JsonResponse
    {
        $result = $action->execute((string) $request->validated('phone'));

        return response()->json(
            [
                'sent_to' => $result->phone,
                'expires_in' => $result->expiresIn,
                'can_resend_in' => $result->canResendIn,
            ],
            Response::HTTP_ACCEPTED,
        );
    }

    /**
     * POST /api/v1/auth/verify-otp
     *
     * Returns 200 + { phone_verified: true } on success. Errors are thrown
     * by VerifyOtpAction → OtpService and shaped by the global handler:
     *  - 410 AUTH_004 when the active OTP has expired
     *  - 422 AUTH_005 when the code is wrong or attempts exhausted
     */
    public function verify(OtpVerifyRequest $request, VerifyOtpAction $action): JsonResponse
    {
        $result = $action->execute(
            phone: (string) $request->validated('phone'),
            code: (string) $request->validated('code'),
        );

        return response()->json([
            'phone_verified' => $result->phoneVerified,
        ]);
    }

    /**
     * POST /api/v1/auth/resend-otp
     *
     * Same response shape as send-otp — the cooldown / hourly-cap throttles
     * are enforced inside SendOtpAction so both endpoints stay aligned and
     * cannot be played against each other.
     */
    public function resend(OtpSendRequest $request, SendOtpAction $action): JsonResponse
    {
        $result = $action->execute((string) $request->validated('phone'));

        return response()->json(
            [
                'sent_to' => $result->phone,
                'expires_in' => $result->expiresIn,
                'can_resend_in' => $result->canResendIn,
            ],
            Response::HTTP_ACCEPTED,
        );
    }
}
