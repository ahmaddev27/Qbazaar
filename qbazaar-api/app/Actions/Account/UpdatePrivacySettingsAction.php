<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Data\Account\PrivacySettings;
use App\Models\User;

/**
 * Replaces the user's privacy preferences with the validated payload.
 *
 * The Form Request guarantees every key is present so we can construct the
 * DTO directly without merging into existing state — keeps the resulting
 * row diffable in the activity log if we wire that up later.
 */
class UpdatePrivacySettingsAction
{
    /**
     * @param array{show_phone: bool, show_email: bool, allow_chat: bool, indexed_by_search: bool} $payload
     */
    public function execute(User $user, array $payload): PrivacySettings
    {
        $settings = new PrivacySettings(
            show_phone: $payload['show_phone'],
            show_email: $payload['show_email'],
            allow_chat: $payload['allow_chat'],
            indexed_by_search: $payload['indexed_by_search'],
        );

        $user->forceFill(['privacy_settings' => $settings])->save();

        return $settings;
    }
}
