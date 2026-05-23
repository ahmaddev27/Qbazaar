<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Account;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Public shape of a single Sanctum personal access token, enriched with the
 * matching refresh-token device hints when available.
 *
 * @property PersonalAccessToken $resource
 */
class SessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PersonalAccessToken $token */
        $token = $this->resource;

        /** @var string|null $device */
        $device = $token->getAttribute('device_label');

        /** @var string|null $ip */
        $ip = $token->getAttribute('ip_address');

        /** @var bool $isCurrent */
        $isCurrent = (bool) $token->getAttribute('is_current');

        return [
            'id' => (string) $token->id,
            'name' => $token->name,
            'last_used_at' => $token->last_used_at?->toIso8601String(),
            'created_at' => $token->created_at?->toIso8601String(),
            'expires_at' => $token->expires_at?->toIso8601String(),
            'device_label' => $device,
            'ip_address' => $ip,
            'is_current' => $isCurrent,
        ];
    }
}
