<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Users;

use App\Enums\UserStatus;
use App\Exceptions\DomainException;
use App\Exceptions\ErrorCode;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Users\PublicUserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Users
 */
class PublicProfileController extends Controller
{
    /**
     * Public-profile view of any active user.
     *
     * Non-active users (suspended / deactivated / pending_deletion) are
     * surfaced as USER_001 (NotFound) instead of leaking their status — we
     * don't want bots to enumerate the moderation backlog.
     *
     * @unauthenticated
     *
     * @throws DomainException
     */
    public function __invoke(Request $request, User $user): JsonResponse
    {
        if ($user->status !== UserStatus::ACTIVE) {
            throw new DomainException(ErrorCode::USER_NOT_FOUND);
        }

        return response()->json(
            (new PublicUserResource($user))->toArray($request),
        );
    }
}
