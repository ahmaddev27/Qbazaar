<?php

declare(strict_types=1);

namespace App\Events\Ads;

use App\Models\Ad;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when the daily ExpireOldAdsJob flips an ACTIVE ad past `expires_at`
 * into EXPIRED. Listeners drop the ad from Meilisearch (RemoveAdFromSearch)
 * and email the owner with a one-click renewal CTA (SendAdNotifications).
 */
class AdExpired
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Ad $ad) {}
}
