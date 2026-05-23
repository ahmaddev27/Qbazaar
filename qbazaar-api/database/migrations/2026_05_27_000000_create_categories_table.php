<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Categories — the public taxonomy that drives ad classification, search
     * filters, and the home-screen browse tree.
     *
     *  - Tree-shaped via self-referential `parent_id`. Two levels deep in
     *    practice (group → leaf), but the schema doesn't enforce a depth so
     *    we can grow without a migration.
     *  - `name` / `description` are JSON instead of using a separate
     *    translations table — the catalogue is curated, finite, and shipped
     *    by seeders; a JSON column keeps reads to a single row.
     *  - `custom_fields` / `custom_filters` are JSON arrays of field /
     *    filter definitions; controllers stream them through resources so
     *    the wire shape stays decoupled from the storage shape.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('parent_id')
                ->nullable()
                ->constrained('categories')
                ->cascadeOnDelete();
            $table->string('slug', 64)->unique();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('icon', 64)->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('custom_fields')->nullable();
            $table->json('custom_filters')->nullable();
            $table->timestamps();

            // Lookup pattern: list active children of a parent in display order.
            $table->index(['parent_id', 'order', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
