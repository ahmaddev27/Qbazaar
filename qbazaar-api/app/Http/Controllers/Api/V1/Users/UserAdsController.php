<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Users;

use App\Enums\UserStatus;
use App\Exceptions\DomainException;
use App\Exceptions\ErrorCode;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Users
 */
class UserAdsController extends Controller
{
    /**
     * Public, paginated list of a user's active ads.
     *
     * STUB: the ads table doesn't exist until Sprint 5. We return an empty
     * data array with a valid pagination meta block so clients can integrate
     * against the wire shape today. When ads ships, the real query lands
     * here without changing the response envelope.
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

        $perPage = 20;
        $currentPage = max(1, (int) $request->query('page', '1'));

        // TODO Sprint 5: replace stub with Ad::query()->where('user_id', $user->id)->where('status', 'active')->paginate(20)
        return response()->json([
            'data' => [],
            'meta' => [
                'current_page' => $currentPage,
                'per_page' => $perPage,
                'total' => 0,
                'last_page' => 1,
                'has_more' => false,
                'next_cursor' => null,
                'prev_cursor' => null,
            ],
        ]);
    }
}
