<?php

declare(strict_types=1);

namespace App\Jobs\Ads;

use App\Enums\AdStatus;
use App\Events\Ads\AdExpired;
use App\Events\Ads\AdExpiringSoon;
use App\Models\Ad;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Daily sweeper for ad lifecycle.
 *
 * Two passes:
 *   1. Active ads whose `expires_at` is in the past → flip to EXPIRED,
 *      unindex from search, fire AdExpired.
 *   2. Active ads expiring in the next 24h → fire AdExpiringSoon so the
 *      seller receives a renewal nudge.
 *
 * Pass 1 iterates row-by-row (instead of a single mass UPDATE) so that
 * Spatie\Activitylog observers, Scout's unsearchable() and the AdExpired
 * listener chain all run with full model context. The cohort is small
 * (one day's worth of expiries) — readability beats raw throughput here.
 *
 * Pass 2 also iterates so we can dispatch the event per-ad. We accept that
 * duplicate notifications can occur if the job runs more than once a day —
 * a follow-up sprint adds `ads.expiring_notified_at` to suppress repeats.
 *
 * Failure handling: each row processes in isolation. A single failure is
 * logged via the queue worker's default exception path and the loop moves on.
 */
class ExpireOldAdsJob implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->onQueue('low');
    }

    public function handle(): void
    {
        $now = now()->toDateTimeImmutable();

        $this->expirePastDueAds($now);
        $this->notifyExpiringSoon($now);
    }

    /**
     * Pass 1 — move ACTIVE ads whose expires_at < now into EXPIRED.
     */
    private function expirePastDueAds(DateTimeInterface $now): void
    {
        Ad::query()
            ->where('status', AdStatus::ACTIVE->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', $now)
            ->orderBy('expires_at')
            ->chunkById(100, function (Collection $ads): void {
                /** @var Collection<int, Ad> $ads */
                foreach ($ads as $ad) {
                    $ad->markExpired();
                    AdExpired::dispatch($ad);
                }
            });
    }

    /**
     * Pass 2 — fire the expiring-soon event for ads whose window closes in
     * the next 24h. We pass the cached "now" so both passes see the same
     * time baseline regardless of clock drift inside long-running queues.
     */
    private function notifyExpiringSoon(DateTimeInterface $now): void
    {
        $windowStart = new DateTimeImmutable($now->format('c'));
        $windowEnd = $windowStart->modify('+1 day');

        Ad::query()
            ->where('status', AdStatus::ACTIVE->value)
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [$windowStart, $windowEnd])
            ->orderBy('expires_at')
            ->chunkById(100, function (Collection $ads): void {
                /** @var Collection<int, Ad> $ads */
                foreach ($ads as $ad) {
                    AdExpiringSoon::dispatch($ad);
                }
            });
    }
}
