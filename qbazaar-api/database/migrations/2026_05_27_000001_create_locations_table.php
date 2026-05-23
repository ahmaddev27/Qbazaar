<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Locations — Qatar municipalities and their districts.
     *
     *  - Same tree pattern as categories so the API can return a single
     *    nested response for the country.
     *  - `type` distinguishes municipalities (`city`) from neighbourhood
     *    units (`district` / `area`). Stored as MySQL ENUM and cast to
     *    LocationType on the model.
     *  - lat/lng are optional for now — frontend doesn't need them in
     *    Sprint 3 but we keep the columns so we can later add a "near me"
     *    filter without a migration.
     */
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('parent_id')
                ->nullable()
                ->constrained('locations')
                ->cascadeOnDelete();
            $table->string('slug', 64)->unique();
            $table->json('name');
            $table->enum('type', ['city', 'district', 'area'])->default('city');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
