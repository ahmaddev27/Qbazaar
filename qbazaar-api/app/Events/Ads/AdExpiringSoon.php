<?php

declare(strict_types=1);

namespace App\Events\Ads;

use App\Models\Ad;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired by the daily expiry job ~24h before an ad's `expires_at`. Drives the
 * "your ad is about to expire — renew now" notification.
 *
 * We do not (yet) dedupe — sellers may receive duplicate warnings if the job
 * runs more than once a day. A follow-up sprint will introduce
 * `ads.expiring_notified_at` to suppress repeats.
 */
class AdExpiringSoon
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Ad $ad) {}
}
