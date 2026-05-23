<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use App\Exceptions\DomainException;
use App\Exceptions\ErrorCode;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hard-stops requests from non-active users on protected routes.
 *
 * Contract:
 *  - MUST be applied AFTER `auth:sanctum` so a user instance is resolvable.
 *    If `auth:sanctum` hasn't run, $request->user() is null and we 401 via
 *    AUTH_TOKEN_INVALID — same shape as Sanctum's own failure.
 *  - When the resolved user's status is anything other than ACTIVE
 *    (suspended / deactivated / pending_deletion), responds with 403 AUTH_002.
 *
 * Why a separate middleware instead of doing this inside auth:sanctum?
 *  - We sometimes need to allow non-active users through (e.g. a
 *    "reactivate-my-account" endpoint). Splitting active-check out gives the
 *    route table fine-grained control.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user instanceof User) {
            throw new DomainException(ErrorCode::AUTH_TOKEN_INVALID);
        }

        if ($user->status !== UserStatus::ACTIVE) {
            throw new DomainException(ErrorCode::AUTH_ACCOUNT_SUSPENDED);
        }

        return $next($request);
    }
}
