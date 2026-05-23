<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Reference;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Filter definition exposed to the frontend so it can render category-specific
 * search controls (e.g. price range for cars, bedrooms for apartments).
 *
 * Source row is the raw JSON entry stored on `categories.custom_filters`.
 * The resource normalises field presence so unknown keys never leak
 * through unfiltered.
 *
 * @property array{
 *     key?: string,
 *     label?: array{ar?: string, en?: string},
 *     type?: string,
 *     options?: array<int, mixed>|null,
 * } $resource
 */
class CategoryFilterResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $data */
        $data = is_array($this->resource) ? $this->resource : [];

        return [
            'key' => (string) ($data['key'] ?? ''),
            'label' => $data['label'] ?? ['ar' => '', 'en' => ''],
            'type' => (string) ($data['type'] ?? 'select'),
            'options' => $data['options'] ?? null,
        ];
    }
}
