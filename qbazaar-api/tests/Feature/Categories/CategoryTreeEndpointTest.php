<?php

declare(strict_types=1);

use App\Models\Category;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

use function Pest\Laravel\getJson;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
    seed(CategorySeeder::class);
});

it('returns the category tree wrapped in the success envelope', function (): void {
    getJson('/api/v1/categories/tree')
        ->assertOk()
        ->assertJson(
            fn ($json) => $json
                ->where('success', true)
                ->has('data')
                ->etc(),
        );
});

it('populates children for top-level categories', function (): void {
    $response = getJson('/api/v1/categories/tree')->assertOk();

    $data = $response->json('data');
    expect($data)->toBeArray()->not->toBeEmpty();

    $vehicles = collect($data)->firstWhere('slug', 'vehicles');
    expect($vehicles)
        ->not->toBeNull()
        ->and($vehicles['children'])->toBeArray()->not->toBeEmpty()
        ->and(collect($vehicles['children'])->pluck('slug')->all())
        ->toContain('cars');
});

it('excludes inactive categories from the tree', function (): void {
    Category::query()->where('slug', 'vehicles')->update(['is_active' => false]);
    Cache::forget('categories.tree');

    $slugs = collect(getJson('/api/v1/categories/tree')->json('data'))
        ->pluck('slug')
        ->all();

    expect($slugs)->not->toContain('vehicles');
});

it('returns only top-level categories on /main', function (): void {
    $response = getJson('/api/v1/categories/main')->assertOk();

    $data = $response->json('data');
    expect($data)->toBeArray()->not->toBeEmpty();

    foreach ($data as $row) {
        expect($row['parent_id'])->toBeNull();
    }
});
