<?php

declare(strict_types=1);

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\putJson;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create([
        'password' => Hash::make('OldStr0ng!Pass'),
    ]);
    Sanctum::actingAs($this->user, ['*']);
});

it('updates the password and burns other refresh tokens', function (): void {
    // Create a previous refresh token for the user so we can assert it gets burnt.
    $other = RefreshToken::query()->create([
        'id' => strtolower((string) Str::ulid()),
        'user_id' => $this->user->id,
        'token_hash' => Hash::make('some_old_token'),
        'expires_at' => now()->addDays(30),
    ]);

    putJson('/api/v1/account/password', [
        'current_password' => 'OldStr0ng!Pass',
        'password' => 'NewStr0ng!Pass#1',
        'password_confirmation' => 'NewStr0ng!Pass#1',
    ])->assertNoContent();

    expect(Hash::check('NewStr0ng!Pass#1', $this->user->fresh()->password))->toBeTrue();
    expect($other->fresh()->used_at)->not->toBeNull();
});

it('rejects when current_password is wrong', function (): void {
    putJson('/api/v1/account/password', [
        'current_password' => 'wrong-password',
        'password' => 'NewStr0ng!Pass#1',
        'password_confirmation' => 'NewStr0ng!Pass#1',
    ])
        ->assertStatus(422)
        ->assertJson(
            fn ($json) => $json
                ->where('error.code', 'USER_004')
                ->etc(),
        );
});

it('rejects when new password is weak', function (): void {
    putJson('/api/v1/account/password', [
        'current_password' => 'OldStr0ng!Pass',
        'password' => 'weak',
        'password_confirmation' => 'weak',
    ])->assertStatus(422);
});

it('rejects when password and confirmation do not match', function (): void {
    putJson('/api/v1/account/password', [
        'current_password' => 'OldStr0ng!Pass',
        'password' => 'NewStr0ng!Pass#1',
        'password_confirmation' => 'Mismatch!1',
    ])->assertStatus(422);
});

it('rejects unauthenticated requests', function (): void {
    $this->refreshApplication();

    putJson('/api/v1/account/password', [
        'current_password' => 'OldStr0ng!Pass',
        'password' => 'NewStr0ng!Pass#1',
        'password_confirmation' => 'NewStr0ng!Pass#1',
    ])->assertStatus(401);
});
