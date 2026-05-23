<?php

declare(strict_types=1);

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

    Route::middleware(['api', 'auth:sanctum', 'phone.verified'])
        ->get('/api/test/phone-only', fn (Request $request) => response()->json([
            'user_id' => $request->user()?->id,
        ]));
});

it('lets a phone-verified user through', function (): void {
    /** @var User $user */
    $user = User::factory()->create(['phone_verified' => true]);

    Sanctum::actingAs($user);

    getJson('/api/test/phone-only')
        ->assertOk()
        ->assertJson(
            fn ($json) => $json
                ->where('success', true)
                ->where('data.user_id', $user->id)
                ->etc(),
        );
});

it('blocks an unverified-phone user with 403 AUTH_003', function (): void {
    /** @var User $user */
    $user = User::factory()->create(['phone_verified' => false]);

    Sanctum::actingAs($user);

    getJson('/api/test/phone-only')
        ->assertStatus(403)
        ->assertJson(
            fn ($json) => $json
                ->where('success', false)
                ->where('error.code', 'AUTH_003')
                ->etc(),
        );
});

it('returns 401 AUTH_010 with no auth token presented', function (): void {
    getJson('/api/test/phone-only')
        ->assertStatus(401)
        ->assertJson(
            fn ($json) => $json
                ->where('error.code', 'AUTH_010')
                ->etc(),
        );
});
