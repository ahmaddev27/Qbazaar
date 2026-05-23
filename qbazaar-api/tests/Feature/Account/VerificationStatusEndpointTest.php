<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create([
        'email_verified' => true,
        'phone_verified' => false,
    ]);
    Sanctum::actingAs($this->user, ['*']);
});

it('returns the current verification snapshot', function (): void {
    getJson('/api/v1/account/verification-status')
        ->assertOk()
        ->assertJson(
            fn ($json) => $json
                ->where('data.email_verified', true)
                ->where('data.phone_verified', false)
                ->where('data.business_verified', false)
                ->where('data.kyc_status', 'none')
                ->etc(),
        );
});

it('rejects unauthenticated requests', function (): void {
    $this->refreshApplication();

    getJson('/api/v1/account/verification-status')->assertStatus(401);
});
