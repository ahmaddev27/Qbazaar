<?php

declare(strict_types=1);

use App\Enums\AdStatus;
use App\Models\Ad;
use App\Models\Category;
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

it('returns up to 12 active ads in the same category, excluding the source', function (): void {
    $category = Category::query()->first();

    /** @var Category $category */
    $source = Ad::factory()->active()->create([
        'user_id' => $this->seller->id,
        'category_id' => $category->id,
    ]);

    // Eight active siblings + one draft (should be skipped).
    Ad::factory()->count(8)->active()->create([
        'user_id' => $this->seller->id,
        'category_id' => $category->id,
    ]);
    Ad::factory()->create([
        'user_id' => $this->seller->id,
        'category_id' => $category->id,
        'status' => AdStatus::DRAFT->value,
    ]);

    $response = getJson("/api/v1/ads/{$source->id}/similar", ['Accept' => 'application/json'])
        ->assertOk();

    $ids = collect($response->json('data'))->pluck('id')->all();

    expect($ids)->not->toContain($source->id)
        ->and($ids)->toHaveCount(8);
});

it('returns 404 envelope when source ad does not exist', function (): void {
    getJson('/api/v1/ads/01HZZZZZZZZZZZZZZZZZZZZZZZ/similar', ['Accept' => 'application/json'])
        ->assertStatus(404)
        ->assertJsonPath('success', false);
});
