<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Account;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Privacy settings update payload.
 *
 * All four fields are required so a PUT either fully replaces the stored
 * configuration or fails validation — matching the wire contract documented
 * in openapi/v1.yaml.
 *
 * @bodyParam show_phone bool required Show the user's phone on public profile. Example: true
 * @bodyParam show_email bool required Show the user's email on public profile. Example: false
 * @bodyParam allow_chat bool required Allow other users to start a conversation. Example: true
 * @bodyParam indexed_by_search bool required Allow the public profile to be indexed by search engines. Example: true
 */
class UpdatePrivacySettingsRequest extends FormRequest
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
            'show_phone' => ['required', 'boolean'],
            'show_email' => ['required', 'boolean'],
            'allow_chat' => ['required', 'boolean'],
            'indexed_by_search' => ['required', 'boolean'],
        ];
    }
}
