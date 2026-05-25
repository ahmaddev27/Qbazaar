<?php

declare(strict_types=1);

use App\Enums\AdStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;

use Tests\Concerns\CreatesAds;

uses(RefreshDatabase::class, CreatesAds::class);

beforeEach(function (): void {
    $this->seedReferenceData();
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user, ['*']);
});

it('parks an ad in PENDING when the description contains a banned word', function (): void {
    $ad = $this->makeAd($this->user, [
        'status' => AdStatus::DRAFT->value,
        'title' => 'Great phone for sale',
        'description' => 'Buy this phone today! Bitcoin payment accepted. ' . str_repeat('Lorem ipsum text. ', 5),
    ]);

    postJson("/api/v1/ads/{$ad->id}/publish", [], [
        'Accept' => 'application/json',
    ])
        ->assertOk()
        ->assertJsonPath('data.status', AdStatus::PENDING->value);

    expect($ad->fresh()->status)->toBe(AdStatus::PENDING);
});

it('publishes a clean ad straight to ACTIVE', function (): void {
    $ad = $this->makeAd($this->user, [
        'status' => AdStatus::DRAFT->value,
        'title' => 'Comfy reading chair for the living room',
        'description' => 'Very comfortable reading chair in excellent condition. Wood frame and cotton upholstery. Pick up from West Bay.',
    ]);

    postJson("/api/v1/ads/{$ad->id}/publish", [], [
        'Accept' => 'application/json',
    ])
        ->assertOk()
        ->assertJsonPath('data.status', AdStatus::ACTIVE->value);

    expect($ad->fresh()->status)->toBe(AdStatus::ACTIVE);
});
