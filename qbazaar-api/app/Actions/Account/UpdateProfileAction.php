<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Enums\Language;
use App\Models\User;

/**
 * Persists the editable subset of a profile (display name + language).
 *
 * Email and phone changes are intentionally NOT supported here — they each
 * have their own verified flow (OTP / email-verification) and updating them
 * silently would bypass the security model.
 */
class UpdateProfileAction
{
    /**
     * @param array{full_name: string, language: string} $payload
     */
    public function execute(User $user, array $payload): User
    {
        $user->forceFill([
            'full_name' => $payload['full_name'],
            'language' => Language::from($payload['language'])->value,
        ])->save();

        return $user->refresh();
    }
}
