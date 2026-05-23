<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Account;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * Compact shape for blocked-user listings — id + display name + avatar +
 * the timestamp the block was created. Anything more sensitive (email,
 * phone, status) is deliberately omitted since the only context this
 * resource is rendered in is "people the current user no longer wants
 * to interact with".
 *
 * @mixin User
 */
class BlockedUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Pivot|null $pivot */
        $pivot = $this->resource->getAttribute('pivot');

        $blockedAt = $pivot?->getAttribute('created_at');
        if (is_string($blockedAt)) {
            $blockedAt = Carbon::parse($blockedAt);
        }

        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'avatar_url' => $this->avatar_url,
            'blocked_at' => $blockedAt?->toIso8601String(),
        ];
    }
}
