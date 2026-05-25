<?php

declare(strict_types=1);

namespace App\Services\Ads;

use App\Models\Category;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Validator as ValidatorFactory;
use Illuminate\Validation\ValidationException;

/**
 * Dynamic validator for category-specific `custom_fields` payloads.
 *
 * Each Category row carries a `custom_fields` JSON schema:
 *
 *   [
 *     { key: "year",  type: "number",  required: true,  label: {...} },
 *     { key: "make",  type: "select",  required: true,  options: [...] },
 *     ...
 *   ]
 *
 * This validator turns that schema into a runtime ruleset against the
 * user-submitted `custom_fields[key]` blob, throwing the standard Laravel
 * ValidationException with `custom_fields.{key}` error paths so the API
 * envelope surfaces them under the existing VALIDATION_FAILED code.
 *
 * Supported field types (from CategorySeeder):
 *   - text     → string, max 255
 *   - number   → numeric
 *   - boolean  → boolean
 *   - select   → in:options[]
 *   - range    → not used in custom_fields (filter-only); we accept any numeric
 *
 * Unknown types are accepted to keep the door open for future field shapes
 * without breaking existing seller submissions — we'll add stricter rules
 * once the admin UI for editing custom_fields lands in Sprint 11.
 */
class CustomFieldsValidator
{
    /**
     * Validate $submitted against the schema attached to $category. Returns
     * the (cast) value on success; throws ValidationException on failure
     * with `custom_fields.{key}` error paths so the envelope renders them
     * under the existing VALIDATION_FAILED code.
     *
     * @param array<string, mixed>|null $submitted
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validate(Category $category, ?array $submitted): array
    {
        $schema = $category->custom_fields;
        if ($schema === null || $schema === []) {
            // Category has no schema → custom_fields is opaque. Persist as-is.
            return $submitted ?? [];
        }

        $submitted = $submitted ?? [];

        $rules = [];
        $messages = [];

        foreach ($schema as $field) {
            if (! is_array($field) || ! isset($field['key']) || ! is_string($field['key'])) {
                continue;
            }

            $key = $field['key'];
            $type = isset($field['type']) && is_string($field['type']) ? $field['type'] : 'text';
            $required = (bool) ($field['required'] ?? false);

            $path = 'custom_fields.' . $key;
            $fieldRules = [];

            if ($required) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
                $fieldRules[] = 'sometimes';
            }

            switch ($type) {
                case 'number':
                    $fieldRules[] = 'numeric';
                    break;
                case 'boolean':
                    $fieldRules[] = 'boolean';
                    break;
                case 'select':
                    if (isset($field['options']) && is_array($field['options']) && $field['options'] !== []) {
                        $fieldRules[] = 'in:' . implode(',', array_map(
                            static fn ($o): string => (string) $o,
                            $field['options'],
                        ));
                    } else {
                        $fieldRules[] = 'string';
                    }
                    break;
                case 'range':
                    // Range is used by filters, not raw submissions; accept numeric.
                    $fieldRules[] = 'numeric';
                    break;
                case 'text':
                default:
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:255';
                    break;
            }

            $rules[$path] = $fieldRules;
        }

        if ($rules === []) {
            return $submitted;
        }

        /** @var Validator $validator */
        $validator = ValidatorFactory::make(
            ['custom_fields' => $submitted],
            $rules,
            $messages,
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $submitted;
    }
}
