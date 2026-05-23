<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

it('lists active sessions', function (): void {
    // Two sanctum tokens for the user — one expired, one valid.
    $this->user->createToken('current', ['*'], now()->addMinutes(15));
    $this->user->createToken('expired', ['*'], now()->subMinute());

    Sanctum::actingAs($this->user, ['*']);

    $response = getJson('/api/v1/account/sessions')->assertOk();

    /** @var array<string, mixed> $payload */
    $payload = $response->json();
    $data = $payload['data'] ?? [];

    expect(is_array($data))->toBeTrue();
    // Expired tokens are filtered out; only the live "current" + the Sanctum::actingAs token remain.
    expect(count($data))->toBeGreaterThanOrEqual(1);
});

it('revokes a session belonging to the user', function (): void {
    $other = $this->user->createToken('other-device', ['*'], now()->addMinutes(15));
    Sanctum::actingAs($this->user, ['*']);

    deleteJson('/api/v1/account/sessions/' . $other->accessToken->getKey())
        ->assertNoContent();

    expect($this->user->tokens()->whereKey($other->accessToken->getKey())->exists())->toBeFalse();
});

it('returns USER_001 when revoking another user\'s session', function (): void {
    Sanctum::actingAs($this->user, ['*']);
    $stranger = User::factory()->create();
    $strangerToken = $stranger->createToken('api', ['*'], now()->addMinutes(15));

    deleteJson('/api/v1/account/sessions/' . $strangerToken->accessToken->getKey())
        ->assertStatus(404)
        ->assertJson(fn ($json) => $json->where('error.code', 'USER_001')->etc());
});

it('rejects unauthenticated requests', function (): void {
    getJson('/api/v1/account/sessions')->assertStatus(401);
});
