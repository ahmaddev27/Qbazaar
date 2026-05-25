<?php

declare(strict_types=1);

use App\Models\Ad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

use function Pest\Laravel\getJson;

use Tests\Concerns\CreatesAds;

uses(RefreshDatabase::class, CreatesAds::class);

beforeEach(function (): void {
    $this->seedReferenceData();
    $this->seller = User::factory()->create();
    Cache::flush();
});

it('returns only featured + active ads, capped at 12', function (): void {
    // Three featured + two non-featured active ads.
    $featured = Ad::factory()->count(3)->active()->create([
        'user_id' => $this->seller->id,
        'featured' => true,
    ]);
    Ad::factory()->count(2)->active()->create([
        'user_id' => $this->seller->id,
        'featured' => false,
    ]);

    $response = getJson('/api/v1/ads/featured', ['Accept' => 'application/json'])
        ->assertOk();

    $ids = collect($response->json('data'))->pluck('id')->all();

    expect($ids)->toHaveCount(3);

    foreach ($featured as $ad) {
        expect($ids)->toContain($ad->id);
    }
});

it('returns an empty list when nothing is featured', function (): void {
    Ad::factory()->count(2)->active()->create([
        'user_id' => $this->seller->id,
        'featured' => false,
    ]);

    $response = getJson('/api/v1/ads/featured', ['Accept' => 'application/json'])
        ->assertOk();

    expect($response->json('data'))->toBe([]);
});
