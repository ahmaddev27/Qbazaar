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

it('lists blocked users in newest-first order', function (): void {
    $a = User::factory()->create(['full_name' => 'Alpha']);
    $b = User::factory()->create(['full_name' => 'Beta']);

    $this->user->blockedUsers()->attach($a->id, ['created_at' => now()->subMinute()]);
    $this->user->blockedUsers()->attach($b->id, ['created_at' => now()]);

    getJson('/api/v1/account/blocked-users')
        ->assertOk()
        ->assertJson(
            fn ($json) => $json
                ->where('success', true)
                ->has('data', 2)
                ->where('data.0.full_name', 'Beta')
                ->where('data.1.full_name', 'Alpha')
                ->etc(),
        );
});

it('returns an empty list when nothing is blocked', function (): void {
    getJson('/api/v1/account/blocked-users')
        ->assertOk()
        ->assertJson(
            fn ($json) => $json
                ->where('success', true)
                ->has('data', 0)
                ->etc(),
        );
});
