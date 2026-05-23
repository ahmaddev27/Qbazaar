<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Reference;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recursive category tree node — every node carries its already-loaded
 * `children` collection so the consumer can render the full browse
 * hierarchy from a single response.
 *
 * Caller is expected to eager-load `children.children…` to whatever depth
 * the taxonomy actually has (two levels today; the resource will silently
 * render an empty children array if more levels exist but weren't loaded).
 *
 * @mixin Category
 */
class CategoryNodeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Render children eagerly as plain arrays so the response can be
        // serialised inside the global `{success, data: ...}` envelope without
        // a Laravel ResourceCollection injecting its own `data` key.
        $children = $this->children->map(
            fn (Category $child): array => (new self($child))->toArray($request),
        )->all();

        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'icon' => $this->icon,
            'order' => $this->order,
            'is_active' => $this->is_active,
            'custom_fields' => $this->custom_fields,
            'custom_filters' => $this->custom_filters,
            'ads_count' => 0,
            'children' => $children,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
