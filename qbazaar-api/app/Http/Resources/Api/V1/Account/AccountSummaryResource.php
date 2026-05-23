<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Account;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lightweight at-a-glance counters for the account dashboard.
 *
 * Backed by an array assembled in the service layer — see
 * App\Actions\Account\GetAccountSummaryAction. Any field whose underlying
 * table doesn't exist yet returns 0, with a TODO comment in the action so the
 * Sprint N owner can plug the real counter in.
 *
 * @property array{
 *   my_ads: int,
 *   drafts: int,
 *   conversations: int,
 *   unread_notifications: int,
 *   favorites: int
 * } $resource
 */
class AccountSummaryResource extends JsonResource
{
    /**
     * @return array<string, int>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, int> $data */
        $data = $this->resource;

        return [
            'my_ads' => $data['my_ads'],
            'drafts' => $data['drafts'],
            'conversations' => $data['conversations'],
            'unread_notifications' => $data['unread_notifications'],
            'favorites' => $data['favorites'],
        ];
    }
}
