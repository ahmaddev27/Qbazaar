<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();

    // Throwaway route to exercise the middleware. Mounted under /api so the
    // standard ApiResponseWrapper + JSON exception renderer apply.
    Route::middleware(['api', 'auth:sanctum', 'active.user'])
        ->get('/api/test/active-only', fn (Request $request) => response()->json([
            'user_id' => $request->user()?->id,
        ]));
});

it('lets an active user through and returns the wrapped 200', function (): void {
    /** @var User $user */
    $user = User::factory()->create(['status' => UserStatus::ACTIVE->value]);

    Sanctum::actingAs($user);

    getJson('/api/test/active-only')
        ->assertOk()
        ->assertJson(
            fn ($json) => $json
                ->where('success', true)
                ->where('data.user_id', $user->id)
                ->etc(),
        );
});

it('blocks a suspended user with 403 AUTH_002', function (): void {
    /** @var User $user */
    $user = User::factory()->create(['status' => UserStatus::SUSPENDED->value]);

    Sanctum::actingAs($user);

    getJson('/api/test/active-only')
        ->assertStatus(403)
        ->assertJson(
            fn ($json) => $json
                ->where('success', false)
                ->where('error.code', 'AUTH_002')
                ->etc(),
        );
});

it('blocks a deactivated user with 403 AUTH_002', function (): void {
    /** @var User $user */
    $user = User::factory()->create(['status' => UserStatus::DEACTIVATED->value]);

    Sanctum::actingAs($user);

    getJson('/api/test/active-only')
        ->assertStatus(403)
        ->assertJson(
            fn ($json) => $json
                ->where('error.code', 'AUTH_002')
                ->etc(),
        );
});

it('returns 401 AUTH_010 when no token is presented (sanctum runs first)', function (): void {
    getJson('/api/test/active-only')
        ->assertStatus(401)
        ->assertJson(
            fn ($json) => $json
                ->where('error.code', 'AUTH_010')
                ->etc(),
        );
});
