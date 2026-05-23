<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->target = User::factory()->create();
});

it('returns an empty paginated list as a stub', function (): void {
    getJson('/api/v1/users/' . $this->target->id . '/ads')
        ->assertOk()
        ->assertJson(
            fn ($json) => $json
                ->where('success', true)
                ->has('data', 0)
                ->where('meta.current_page', 1)
                ->where('meta.per_page', 20)
                ->where('meta.total', 0)
                ->where('meta.has_more', false)
                ->etc(),
        );
});

it('returns 404 when the user is not active', function (): void {
    $this->target->forceFill(['status' => UserStatus::SUSPENDED->value])->save();

    getJson('/api/v1/users/' . $this->target->id . '/ads')
        ->assertStatus(404);
});
