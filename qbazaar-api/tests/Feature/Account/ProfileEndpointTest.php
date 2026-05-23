<?php

declare(strict_types=1);

use App\Enums\Language;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create([
        'full_name' => 'Old Name',
        'language' => Language::ARABIC->value,
    ]);
    Sanctum::actingAs($this->user, ['*']);
});

it('returns the profile with privacy defaults', function (): void {
    getJson('/api/v1/account/profile')
        ->assertOk()
        ->assertJson(
            fn ($json) => $json
                ->where('success', true)
                ->where('data.full_name', 'Old Name')
                ->where('data.language', 'ar')
                ->where('data.privacy_settings.show_phone', true)
                ->where('data.privacy_settings.show_email', false)
                ->where('data.privacy_settings.allow_chat', true)
                ->where('data.privacy_settings.indexed_by_search', true)
                ->etc(),
        );
});

it('updates full_name and language', function (): void {
    putJson('/api/v1/account/profile', [
        'full_name' => 'New Name',
        'language' => 'en',
    ])
        ->assertOk()
        ->assertJson(
            fn ($json) => $json
                ->where('data.full_name', 'New Name')
                ->where('data.language', 'en')
                ->etc(),
        );

    expect($this->user->fresh()->full_name)->toBe('New Name');
    expect($this->user->fresh()->language->value)->toBe('en');
});

it('does not allow changing email or phone via the profile endpoint', function (): void {
    putJson('/api/v1/account/profile', [
        'full_name' => 'New Name',
        'language' => 'ar',
        'email' => 'attacker@example.com',
        'phone' => '+97455999999',
    ])->assertOk();

    expect($this->user->fresh()->email)->not->toBe('attacker@example.com');
});

it('validates required fields', function (): void {
    putJson('/api/v1/account/profile', [])
        ->assertStatus(422)
        ->assertJson(
            fn ($json) => $json
                ->where('error.code', 'VALIDATION_FAILED')
                ->has('error.details.full_name')
                ->has('error.details.language')
                ->etc(),
        );
});

it('validates language enum', function (): void {
    putJson('/api/v1/account/profile', [
        'full_name' => 'Whoever',
        'language' => 'xx',
    ])->assertStatus(422);
});

it('rejects unauthenticated requests', function (): void {
    $this->refreshApplication();

    getJson('/api/v1/account/profile')->assertStatus(401);
});
