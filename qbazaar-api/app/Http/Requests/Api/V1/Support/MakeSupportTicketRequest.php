<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Support;

use App\Enums\SupportTicketCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MakeSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'min:3', 'max:160'],
            'category' => ['required', Rule::enum(SupportTicketCategory::class)],
            'body' => ['required', 'string', 'min:10', 'max:5000'],
            'email' => [
                $this->user() === null ? 'required' : 'nullable',
                'email',
                'max:120',
            ],
        ];
    }
}
