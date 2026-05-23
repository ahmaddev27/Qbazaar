<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Change-password payload for an authenticated user.
 *
 * `current_password` MUST be presented even though the user is already
 * authenticated — defence in depth against session-hijack / token-theft.
 *
 * @bodyParam current_password string required The current account password. Example: Str0ng!Pass
 * @bodyParam password string required New password. Example: NewStr0ng!Pass
 * @bodyParam password_confirmation string required Must match `password`. Example: NewStr0ng!Pass
 */
class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->letters()->mixedCase()->numbers()->symbols(),
                'different:current_password',
            ],
        ];
    }
}
