<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RefreshRequest;
use App\Http\Resources\Api\V1\AuthResponseResource;
use App\Services\Auth\RefreshTokenService;
use Illuminate\Http\JsonResponse;

class RefreshTokenController extends Controller
{
    /**
     * POST /api/v1/auth/refresh
     *
     * Rotates the presented refresh token. The old one is marked used (replay
     * detection kicks in if anyone tries to reuse it later) and a brand-new
     * access + refresh pair is minted and returned.
     *
     * Failures (expired / invalid / replay) raise a DomainException carrying
     * AUTH_TOKEN_EXPIRED or AUTH_TOKEN_INVALID — see RefreshTokenService.
     */
    public function __invoke(RefreshRequest $request, RefreshTokenService $service): JsonResponse
    {
        $platform = $request->attributes->get('client_platform');
        $fingerprint = is_string($platform) ? $platform : null;

        $result = $service->rotate(
            presentedRaw: (string) $request->validated('refresh_token'),
            deviceFingerprint: $fingerprint,
        );

        return response()->json(
            (new AuthResponseResource($result['user'], $result['tokens']))->toArray(),
        );
    }
}
