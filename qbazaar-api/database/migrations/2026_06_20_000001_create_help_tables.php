<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_categories', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('slug', 64)->unique();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('icon', 64)->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('help_articles', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('category_id')
                ->constrained('help_categories')
                ->restrictOnDelete();
            $table->string('slug', 120)->unique();
            $table->json('title');
            $table->json('body');
            $table->json('excerpt')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->unsignedBigInteger('views_count')->default(0);
            $table->timestamps();

            $table->index(['category_id', 'display_order', 'is_published'], 'help_articles_category_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_articles');
        Schema::dropIfExists('help_categories');
    }
};
