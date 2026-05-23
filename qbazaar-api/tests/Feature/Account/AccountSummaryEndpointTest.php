<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user, ['*']);
});

it('returns the account summary envelope with zero counters', function (): void {
    getJson('/api/v1/account/summary')
        ->assertOk()
        ->assertJson(
            fn ($json) => $json
                ->where('success', true)
                ->where('data.my_ads', 0)
                ->where('data.drafts', 0)
                ->where('data.conversations', 0)
                ->where('data.unread_notifications', 0)
                ->where('data.favorites', 0)
                ->etc(),
        );
});

it('rejects unauthenticated callers', function (): void {
    $this->refreshApplication();

    getJson('/api/v1/account/summary')
        ->assertStatus(401);
});
