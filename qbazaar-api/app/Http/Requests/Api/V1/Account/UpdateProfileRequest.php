<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Account;

use App\Enums\Language;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Profile update payload.
 *
 * Deliberately narrow: email / phone changes go through their own verified
 * flows (Sprint 1 Wave 2 OTP + email-verification), so this request only
 * accepts the freely-editable fields.
 *
 * @bodyParam full_name string required Updated display name. Example: Ahmed Al-Ali
 * @bodyParam language string required UI language preference. Example: ar
 */
class UpdateProfileRequest extends FormRequest
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
            'full_name' => ['required', 'string', 'min:3', 'max:80'],
            'language' => ['required', 'string', new Enum(Language::class)],
        ];
    }
}
