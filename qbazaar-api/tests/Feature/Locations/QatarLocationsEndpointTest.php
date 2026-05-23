<?php

declare(strict_types=1);

use Database\Seeders\LocationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

use function Pest\Laravel\getJson;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
    seed(LocationSeeder::class);
});

it('returns the Qatar location tree wrapped in success envelope', function (): void {
    $response = getJson('/api/v1/locations/qatar')->assertOk();

    $data = $response->json('data');
    expect($data)->toBeArray()->not->toBeEmpty();
});

it('places cities at the top level and districts as their children', function (): void {
    $data = getJson('/api/v1/locations/qatar')->json('data');

    $doha = collect($data)->firstWhere('slug', 'doha');

    expect($doha)
        ->not->toBeNull()
        ->and($doha['parent_id'])->toBeNull()
        ->and($doha['type'])->toBe('city')
        ->and($doha['name']['ar'])->toBe('الدوحة')
        ->and($doha['children'])->toBeArray()->not->toBeEmpty();

    $districtSlugs = collect($doha['children'])->pluck('slug')->all();
    expect($districtSlugs)->toContain('west-bay', 'al-sadd');

    foreach ($doha['children'] as $district) {
        expect($district['type'])->toBe('district');
    }
});
