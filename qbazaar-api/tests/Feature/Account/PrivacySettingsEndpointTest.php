<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user, ['*']);
});

it('returns defaults when nothing has been set', function (): void {
    getJson('/api/v1/account/privacy-settings')
        ->assertOk()
        ->assertJson(
            fn ($json) => $json
                ->where('success', true)
                ->where('data.show_phone', true)
                ->where('data.show_email', false)
                ->where('data.allow_chat', true)
                ->where('data.indexed_by_search', true)
                ->etc(),
        );
});

it('updates privacy settings', function (): void {
    putJson('/api/v1/account/privacy-settings', [
        'show_phone' => false,
        'show_email' => true,
        'allow_chat' => false,
        'indexed_by_search' => false,
    ])
        ->assertOk()
        ->assertJson(
            fn ($json) => $json
                ->where('data.show_phone', false)
                ->where('data.show_email', true)
                ->where('data.allow_chat', false)
                ->where('data.indexed_by_search', false)
                ->etc(),
        );

    $persisted = $this->user->fresh()->privacySettings();
    expect($persisted->show_phone)->toBeFalse();
    expect($persisted->show_email)->toBeTrue();
});

it('validates that all four flags are required', function (): void {
    putJson('/api/v1/account/privacy-settings', [
        'show_phone' => true,
    ])
        ->assertStatus(422)
        ->assertJson(
            fn ($json) => $json
                ->where('error.code', 'VALIDATION_FAILED')
                ->has('error.details.show_email')
                ->has('error.details.allow_chat')
                ->has('error.details.indexed_by_search')
                ->etc(),
        );
});

it('rejects unauthenticated requests', function (): void {
    $this->refreshApplication();

    getJson('/api/v1/account/privacy-settings')->assertStatus(401);
});
