<?php

declare(strict_types=1);

namespace App\Listeners\Ads;

use App\Events\Ads\AdExpired;
use App\Events\Ads\AdRejected;

/**
 * Drops an ad from Meilisearch when it transitions out of ACTIVE.
 *
 * Why bother (Scout already calls `unsearchable()` on save via
 * `shouldBeSearchable()`)? Because Scout's hook only fires reliably for
 * model-save events; jobs that flip status via mass updates (the expiry job)
 * bypass Eloquent observers. The listener gives us a deterministic call path.
 *
 * Sold ads are handled directly in `Ad::markSold()` — no event listener
 * needed because that path is always single-row + synchronous.
 */
class RemoveAdFromSearch
{
    public function handle(AdRejected|AdExpired $event): void
    {
        $event->ad->unsearchable();
    }
}
