<?php

declare(strict_types=1);

namespace App\Services;

class BuildAssetCardData
{
    /**
     * Builds the persisted JSON payload for an asset card, keyed by section id
     * and field name, storing each field's type and value.
     *
     * @param  array<int, array<string, mixed>>  $sections
     * @param  array<string, mixed>  $values
     * @return array<string, array<string, array{type: string, value: mixed}>>
     */
    public function execute(array $sections, array $values): array
    {
        $data = [];

        foreach ($sections as $section) {
            $sectionId = $section['id'] ?? null;
            $fields = $section['fields'] ?? [];

            if (! is_string($sectionId) || ! is_array($fields)) {
                continue;
            }

            $sectionValues = $values[$sectionId] ?? [];
            $sectionValues = is_array($sectionValues) ? $sectionValues : [];
            $sectionData = [];

            foreach ($fields as $field) {
                if (! is_array($field)) {
                    continue;
                }

                $name = $field['name'] ?? null;
                $type = $field['type'] ?? null;

                if (! is_string($name) || ! is_string($type)) {
                    continue;
                }

                $sectionData[$name] = [
                    'type' => $type,
                    'value' => $this->normalize($type, $sectionValues[$name] ?? null),
                ];
            }

            $data[$sectionId] = $sectionData;
        }

        return $data;
    }

    private function normalize(string $type, mixed $value): mixed
    {
        return match ($type) {
            'checkbox' => (bool) $value,
            'number' => $this->normalizeNumber($value),
            'select_multiple' => is_array($value) ? array_values($value) : [],
            default => $this->normalizeString($value),
        };
    }

    private function normalizeString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_scalar($value) ? (string) $value : null;
    }

    private function normalizeNumber(mixed $value): int|float|null
    {
        if (! is_numeric($value)) {
            return null;
        }

        $number = (float) $value;

        return fmod($number, 1.0) === 0.0 ? (int) $number : $number;
    }
}
