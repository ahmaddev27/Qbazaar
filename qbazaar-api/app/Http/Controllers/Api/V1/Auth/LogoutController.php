<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\RefreshTokenService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Sanctum\PersonalAccessToken;

class LogoutController extends Controller
{
    /**
     * POST /api/v1/auth/logout
     *
     * Revokes the current bearer token. If the client also sends a
     * `refresh_token` body field, we mark that one as used so it cannot be
     * rotated again. Returns 204.
     */
    public function __invoke(Request $request, RefreshTokenService $refreshTokens): Response
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        $presentedRefresh = $request->input('refresh_token');
        if (is_string($presentedRefresh) && $presentedRefresh !== '') {
            $refreshTokens->revoke($presentedRefresh);
        }

        return response()->noContent();
    }
}
