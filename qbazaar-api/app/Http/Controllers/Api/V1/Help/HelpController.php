<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Help;

use App\Exceptions\DomainException;
use App\Exceptions\ErrorCode;
use App\Http\Controllers\Controller;
use App\Models\HelpArticle;
use App\Models\HelpCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HelpController extends Controller
{
    /**
     * GET /api/v1/help/categories — list categories with articles_count.
     */
    public function categories(): JsonResponse
    {
        $rows = Cache::remember('help.categories', 3600, static function (): array {
            return HelpCategory::query()
                ->ordered()
                ->withCount(['articles as articles_count' => static function ($q): void {
                    $q->where('is_published', true);
                }])
                ->get()
                ->map(static fn (HelpCategory $c): array => [
                    'id' => $c->id,
                    'slug' => $c->slug,
                    'name' => $c->name,
                    'description' => $c->description,
                    'icon' => $c->icon,
                    'display_order' => $c->display_order,
                    'articles_count' => (int) ($c->articles_count ?? 0),
                ])
                ->all();
        });

        return response()->json($rows);
    }

    /**
     * GET /api/v1/help/categories/{slug} — category + its articles (lean shape).
     */
    public function categoryShow(string $slug): JsonResponse
    {
        /** @var HelpCategory|null $category */
        $category = HelpCategory::query()->where('slug', $slug)->first();

        if ($category === null) {
            throw new DomainException(ErrorCode::HELP_CATEGORY_NOT_FOUND);
        }

        $articles = $category->articles()
            ->where('is_published', true)
            ->orderBy('display_order')
            ->get(['id', 'slug', 'title', 'excerpt', 'display_order'])
            ->map(static fn (HelpArticle $a): array => [
                'id' => $a->id,
                'slug' => $a->slug,
                'title' => $a->title,
                'excerpt' => $a->excerpt,
                'display_order' => $a->display_order,
            ])
            ->all();

        return response()->json([
            'id' => $category->id,
            'slug' => $category->slug,
            'name' => $category->name,
            'description' => $category->description,
            'icon' => $category->icon,
            'articles' => $articles,
        ]);
    }

    /**
     * GET /api/v1/help/articles/{slug} — full article + increments views_count.
     */
    public function articleShow(string $slug, Request $request): JsonResponse
    {
        /** @var HelpArticle|null $article */
        $article = HelpArticle::query()
            ->with('category')
            ->published()
            ->where('slug', $slug)
            ->first();

        if ($article === null) {
            throw new DomainException(ErrorCode::HELP_ARTICLE_NOT_FOUND);
        }

        // Throttle view count increments to 1 per IP per hour per article.
        $ip = (string) $request->ip();
        $lock = Cache::lock("help.view.{$slug}.{$ip}", 3600);
        if ($lock->get()) {
            $article->increment('views_count');
        }

        return response()->json([
            'id' => $article->id,
            'slug' => $article->slug,
            'title' => $article->title,
            'body' => $article->body,
            'excerpt' => $article->excerpt,
            'views_count' => $article->views_count,
            'category' => [
                'id' => $article->category->id,
                'slug' => $article->category->slug,
                'name' => $article->category->name,
                'icon' => $article->category->icon,
            ],
        ]);
    }

    /**
     * GET /api/v1/help/search?q= — LIKE on title/excerpt across published articles.
     */
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';

        $hits = HelpArticle::query()
            ->published()
            ->where(function ($w) use ($like): void {
                $w->where('title', 'like', $like)
                    ->orWhere('excerpt', 'like', $like);
            })
            ->orderBy('display_order')
            ->limit(20)
            ->get(['id', 'slug', 'title', 'excerpt', 'display_order'])
            ->map(static fn (HelpArticle $a): array => [
                'id' => $a->id,
                'slug' => $a->slug,
                'title' => $a->title,
                'excerpt' => $a->excerpt,
                'display_order' => $a->display_order,
            ])
            ->all();

        return response()->json($hits);
    }
}
