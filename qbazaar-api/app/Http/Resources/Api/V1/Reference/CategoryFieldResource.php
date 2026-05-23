<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Reference;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Custom-field definition used when posting an ad inside a given category
 * (e.g. Cars → make, model, year). Source is the JSON entry stored on
 * `categories.custom_fields`.
 *
 * @property array{
 *     key?: string,
 *     label?: array{ar?: string, en?: string},
 *     type?: string,
 *     required?: bool,
 *     options?: array<int, mixed>|null,
 * } $resource
 */
class CategoryFieldResource extends JsonResource
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
            'type' => (string) ($data['type'] ?? 'text'),
            'required' => (bool) ($data['required'] ?? false),
            'options' => $data['options'] ?? null,
        ];
    }
}
