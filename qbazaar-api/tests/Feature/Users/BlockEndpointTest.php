<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->target = User::factory()->create();
    Sanctum::actingAs($this->user, ['*']);
});

it('blocks another user', function (): void {
    postJson('/api/v1/users/' . $this->target->id . '/block')
        ->assertOk()
        ->assertJson(
            fn ($json) => $json
                ->where('data.blocked', true)
                ->where('data.user_id', $this->target->id)
                ->etc(),
        );

    expect($this->user->hasBlocked($this->target))->toBeTrue();
});

it('is idempotent for duplicate blocks', function (): void {
    postJson('/api/v1/users/' . $this->target->id . '/block')->assertOk();
    postJson('/api/v1/users/' . $this->target->id . '/block')->assertOk();

    expect($this->user->blockedUsers()->count())->toBe(1);
});

it('returns USER_003 when blocking yourself', function (): void {
    postJson('/api/v1/users/' . $this->user->id . '/block')
        ->assertStatus(422)
        ->assertJson(
            fn ($json) => $json
                ->where('error.code', 'USER_003')
                ->etc(),
        );
});

it('returns USER_001 when blocking a nonexistent user', function (): void {
    postJson('/api/v1/users/01HZZZZZZZZZZZZZZZZZZZZZZZ/block')
        ->assertStatus(404);
});

it('unblocks an existing block', function (): void {
    $this->user->blockedUsers()->attach($this->target->id, ['created_at' => now()]);

    deleteJson('/api/v1/users/' . $this->target->id . '/block')
        ->assertNoContent();

    expect($this->user->hasBlocked($this->target))->toBeFalse();
});

it('unblock returns 204 even when no block existed', function (): void {
    deleteJson('/api/v1/users/' . $this->target->id . '/block')
        ->assertNoContent();
});

it('rejects unauthenticated block', function (): void {
    $targetId = $this->target->id;
    $this->refreshApplication();

    postJson('/api/v1/users/' . $targetId . '/block')
        ->assertStatus(401);
});
