<?php

declare(strict_types=1);

use App\Models\RefreshToken;
use App\Models\User;
use App\Services\Auth\RefreshTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
});

it('rotates a valid refresh token and returns a brand-new pair', function (): void {
    $user = User::factory()->create();

    /** @var RefreshTokenService $service */
    $service = app(RefreshTokenService::class);
    $initial = $service->issue($user);

    $response = postJson('/api/v1/auth/refresh', [
        'refresh_token' => $initial->refreshToken,
    ]);

    $response
        ->assertOk()
        ->assertJson(
            fn ($json) => $json
                ->where('success', true)
                ->where('data.user.id', $user->id)
                ->has('data.tokens.access_token')
                ->has('data.tokens.refresh_token')
                ->where('data.tokens.token_type', 'Bearer')
                ->etc(),
        );

    /** @var array{data: array{tokens: array{refresh_token: string}}} $payload */
    $payload = $response->json();
    expect($payload['data']['tokens']['refresh_token'])->not->toBe($initial->refreshToken);

    // The old token row is now marked used.
    expect(RefreshToken::query()->where('user_id', $user->id)->whereNotNull('used_at')->count())
        ->toBe(1);
});

it('rejects an unknown refresh token with AUTH_010', function (): void {
    postJson('/api/v1/auth/refresh', [
        'refresh_token' => 'rt_does_not_exist',
    ])
        ->assertStatus(401)
        ->assertJson(
            fn ($json) => $json
                ->where('error.code', 'AUTH_010')
                ->etc(),
        );
});

it('rejects an expired refresh token with AUTH_009', function (): void {
    $user = User::factory()->create();

    /** @var RefreshTokenService $service */
    $service = app(RefreshTokenService::class);
    $tokens = $service->issue($user);

    // Push the expiry into the past, leave it inside the candidate window.
    RefreshToken::query()
        ->where('user_id', $user->id)
        ->update(['expires_at' => Carbon::now()->subHours(1)]);

    postJson('/api/v1/auth/refresh', [
        'refresh_token' => $tokens->refreshToken,
    ])
        ->assertStatus(401)
        ->assertJson(
            fn ($json) => $json
                ->where('error.code', 'AUTH_009')
                ->etc(),
        );
});

it('burns all sibling refresh tokens when a used one is replayed', function (): void {
    $user = User::factory()->create();

    /** @var RefreshTokenService $service */
    $service = app(RefreshTokenService::class);
    $first = $service->issue($user);

    // Rotate once → fresh pair, original is marked used.
    postJson('/api/v1/auth/refresh', ['refresh_token' => $first->refreshToken])
        ->assertOk();

    // Try to reuse the now-used original — should be flagged as replay.
    postJson('/api/v1/auth/refresh', ['refresh_token' => $first->refreshToken])
        ->assertStatus(401)
        ->assertJson(
            fn ($json) => $json
                ->where('error.code', 'AUTH_010')
                ->etc(),
        );

    // After replay detection, no live refresh tokens remain for this user.
    expect(RefreshToken::query()->where('user_id', $user->id)->whereNull('used_at')->count())
        ->toBe(0);
});
