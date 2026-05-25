<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Ads;

use App\Actions\Ads\ModerateAdAction;
use App\Events\Ads\AdPublished;
use App\Events\Ads\AdRejected;
use App\Exceptions\DomainException;
use App\Exceptions\ErrorCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Ads\PublishAdRequest;
use App\Http\Resources\Api\V1\Ads\AdResource;
use App\Models\Ad;
use Illuminate\Http\JsonResponse;

/**
 * POST /api/v1/ads/{id}/publish — flip a draft toward visibility.
 *
 * The controller runs the auto-moderation action first:
 *   - clean ad   → DRAFT → ACTIVE + AdPublished event + indexed for search.
 *   - flagged ad → DRAFT → PENDING + AdRejected event (await manual review).
 *
 * Either branch returns 200 with the updated AdResource. The client inspects
 * `data.status` to render the right post-publish UX:
 *   `active`  → "Your ad is live"
 *   `pending` → "We need to review your ad"
 *
 * @group Ads
 */
class PublishAdController extends Controller
{
    public function __construct(
        private readonly ModerateAdAction $moderate,
    ) {}

    /**
     * @authenticated
     *
     * @throws DomainException
     */
    public function __invoke(PublishAdRequest $request, string $id): JsonResponse
    {
        $ad = Ad::query()->find($id);

        if ($ad === null) {
            throw new DomainException(ErrorCode::AD_NOT_FOUND);
        }

        $this->authorize('publish', $ad);

        $result = ($this->moderate)($ad);

        if ($result->clean) {
            $ad->publish();
            AdPublished::dispatch($ad);
        } else {
            $ad->holdForReview();
            AdRejected::dispatch($ad, $result);
        }

        $ad->load(['user', 'category', 'location', 'media']);

        return response()->json((new AdResource($ad))->toArray($request));
    }
}
