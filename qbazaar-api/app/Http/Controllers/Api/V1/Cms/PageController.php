<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Cms;

use App\Exceptions\DomainException;
use App\Exceptions\ErrorCode;
use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class PageController extends Controller
{
    /**
     * GET /api/v1/pages — list of published pages (slug + title + display_order).
     */
    public function index(): JsonResponse
    {
        $rows = Cache::remember('pages.list', 3600, static function (): array {
            return Page::query()
                ->published()
                ->orderBy('display_order')
                ->get(['id', 'slug', 'title', 'display_order'])
                ->map(static fn (Page $p): array => [
                    'id' => $p->id,
                    'slug' => $p->slug,
                    'title' => $p->title,
                    'display_order' => $p->display_order,
                ])
                ->all();
        });

        return response()->json($rows);
    }

    /**
     * GET /api/v1/pages/{slug} — full page payload.
     */
    public function show(string $slug): JsonResponse
    {
        $page = Cache::remember("pages.show.{$slug}", 3600, static function () use ($slug): ?Page {
            return Page::query()->published()->where('slug', $slug)->first();
        });

        if ($page === null) {
            throw new DomainException(ErrorCode::CMS_PAGE_NOT_FOUND);
        }

        return response()->json([
            'id' => $page->id,
            'slug' => $page->slug,
            'title' => $page->title,
            'body' => $page->body,
            'meta_description' => $page->meta_description,
            'published_at' => $page->published_at?->toIso8601String(),
            'display_order' => $page->display_order,
        ]);
    }
}
