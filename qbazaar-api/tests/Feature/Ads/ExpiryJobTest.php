<?php

declare(strict_types=1);

use App\Enums\AdStatus;
use App\Events\Ads\AdExpired;
use App\Events\Ads\AdExpiringSoon;
use App\Jobs\Ads\ExpireOldAdsJob;
use App\Models\Ad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Concerns\CreatesAds;

uses(RefreshDatabase::class, CreatesAds::class);

beforeEach(function (): void {
    $this->seedReferenceData();
    $this->seller = User::factory()->create();
});

it('flips ACTIVE ads past expires_at into EXPIRED and fires AdExpired', function (): void {
    Event::fake([AdExpired::class, AdExpiringSoon::class]);

    $stale = Ad::factory()->active()->create([
        'user_id' => $this->seller->id,
        'expires_at' => now()->subDay(),
    ]);

    $fresh = Ad::factory()->active()->create([
        'user_id' => $this->seller->id,
        'expires_at' => now()->addDays(5),
    ]);

    (new ExpireOldAdsJob)->handle();

    expect($stale->fresh()->status)->toBe(AdStatus::EXPIRED)
        ->and($fresh->fresh()->status)->toBe(AdStatus::ACTIVE);

    Event::assertDispatched(AdExpired::class, function (AdExpired $e) use ($stale): bool {
        return $e->ad->id === $stale->id;
    });
});

it('fires AdExpiringSoon for ads expiring within the next 24h', function (): void {
    Event::fake([AdExpired::class, AdExpiringSoon::class]);

    $expiringSoon = Ad::factory()->active()->create([
        'user_id' => $this->seller->id,
        'expires_at' => now()->addHours(12),
    ]);

    Ad::factory()->active()->create([
        'user_id' => $this->seller->id,
        'expires_at' => now()->addDays(5),
    ]);

    (new ExpireOldAdsJob)->handle();

    Event::assertDispatched(AdExpiringSoon::class, function (AdExpiringSoon $e) use ($expiringSoon): bool {
        return $e->ad->id === $expiringSoon->id;
    });

    Event::assertDispatched(AdExpiringSoon::class, 1);
});
