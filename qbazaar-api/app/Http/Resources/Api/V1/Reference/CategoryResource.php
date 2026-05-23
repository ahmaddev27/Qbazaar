<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Reference;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Flat category shape — no children. Used by endpoints that return either
 * a single category (filters, fields, stats) or a flat list (main). The
 * recursive tree shape lives in {@see CategoryNodeResource}.
 *
 * Field order mirrors the `Category` schema in qbazaar-contracts/openapi/v1.yaml.
 *
 * @mixin Category
 */
class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
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
            // Sprint 5 will populate this from the ads table; until then we
            // expose a stable 0 so the wire shape doesn't change later.
            'ads_count' => 0,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
