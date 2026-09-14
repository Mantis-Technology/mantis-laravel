<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Validation\Rule;

class AssetCardRules
{
    /**
     * Builds the validation rules for the dynamic field values of the given
     * template sections.
     *
     * @param  array<int, array<string, mixed>>  $sections
     * @return array<string, array<int, mixed>>
     */
    public function forSections(array $sections): array
    {
        $rules = [];

        foreach ($sections as $section) {
            $sectionId = $section['id'] ?? null;
            $fields = $section['fields'] ?? [];

            if (! is_string($sectionId) || ! is_array($fields)) {
                continue;
            }

            foreach ($fields as $field) {
                if (! is_array($field)) {
                    continue;
                }

                $name = $field['name'] ?? null;
                $type = $field['type'] ?? null;

                if (! is_string($name) || ! is_string($type)) {
                    continue;
                }

                $rules["values.{$sectionId}.{$name}"] = $this->rulesForField($field, $type);
            }
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<int, mixed>
     */
    private function rulesForField(array $field, string $type): array
    {
        $required = (bool) ($field['required'] ?? false);

        if ($type === 'checkbox') {
            return $required ? ['accepted'] : ['nullable', 'boolean'];
        }

        $rules = $required ? ['required'] : ['nullable'];

        return [...$rules, ...match ($type) {
            'number' => ['numeric'],
            'select', 'radio' => ['string', Rule::in($this->optionValues($field))],
            'email' => ['email'],
            'url' => ['url'],
            'date' => ['date'],
            'select_multiple' => ['array'],
            'file' => [],
            default => ['string'],
        }];
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<int, string>
     */
    private function optionValues(array $field): array
    {
        $options = $field['options'] ?? [];

        if (! is_array($options)) {
            return [];
        }

        $values = [];

        foreach ($options as $option) {
            if (is_array($option) && isset($option['value']) && is_scalar($option['value'])) {
                $values[] = (string) $option['value'];

                continue;
            }

            if (is_scalar($option)) {
                $values[] = (string) $option;
            }
        }

        return $values;
    }
}
