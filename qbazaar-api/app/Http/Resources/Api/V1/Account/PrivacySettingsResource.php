<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Account;

use App\Data\Account\PrivacySettings;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PrivacySettings
 */
class PrivacySettingsResource extends JsonResource
{
    /**
     * Plain shape — the DTO already has typed booleans, we just unwrap them
     * so the JSON response doesn't include Spatie\LaravelData metadata.
     *
     * @return array<string, bool>
     */
    public function toArray(Request $request): array
    {
        return [
            'show_phone' => $this->show_phone,
            'show_email' => $this->show_email,
            'allow_chat' => $this->allow_chat,
            'indexed_by_search' => $this->indexed_by_search,
        ];
    }
}
