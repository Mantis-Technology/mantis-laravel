<?php

namespace App\Factories;

class FieldFactory
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function createField(array $data): Field
    {
        $type = FieldType::from($data['type']);
        $name = (string) $data['name'];
        $label = (string) ($data['label'] ?? $name);
        $required = (bool) ($data['required'] ?? false);
        $order = (int) ($data['order'] ?? 0);
        $placeholder = isset($data['placeholder']) ? (string) $data['placeholder'] : null;

        return match ($type) {
            FieldType::TEXT => new TextField($name, $label, $required, $order, $placeholder),
            FieldType::TEXTAREA => new TextareaField($name, $label, $required, $order, $placeholder),
            FieldType::EMAIL => new EmailField($name, $label, $required, $order, $placeholder),
            FieldType::URL => new UrlField($name, $label, $required, $order, $placeholder),
            FieldType::DATE => new DateField($name, $label, $required, $order, $placeholder),
            FieldType::CHECKBOX => new CheckboxField($name, $label, $required, $order, $placeholder),
            FieldType::NUMBER => new NumberField(
                $name,
                $label,
                $required,
                $order,
                $placeholder,
                (int) ($data['min'] ?? 0),
                (int) ($data['max'] ?? PHP_INT_MAX),
            ),
            FieldType::SELECT => new SelectField(
                $name,
                $label,
                $required,
                $order,
                $placeholder,
                self::normalizeOptions($data['options'] ?? []),
            ),
            FieldType::RADIO => new RadioField(
                $name,
                $label,
                $required,
                $order,
                $placeholder,
                self::normalizeOptions($data['options'] ?? []),
            ),
            FieldType::SELECT_MULTIPLE => new SelectMultipleField(
                $name,
                $label,
                $required,
                $order,
                $placeholder,
                self::normalizeOptions($data['options'] ?? []),
            ),
            FieldType::FILE => new FileField(
                $name,
                $label,
                $required,
                $order,
                $placeholder,
                self::normalizeStringArray($data['mimeTypes'] ?? []),
                (int) ($data['maxFileSize'] ?? 0),
            ),
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $options
     * @return array<int, array{value: string, label: string}>
     */
    private static function normalizeOptions(array $options): array
    {
        $normalized = [];

        foreach ($options as $option) {
            $normalized[] = [
                'value' => (string) ($option['value'] ?? ''),
                'label' => (string) ($option['label'] ?? ''),
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array<int, string>
     */
    private static function normalizeStringArray(array $values): array
    {
        $normalized = [];

        foreach ($values as $value) {
            $normalized[] = (string) $value;
        }

        return $normalized;
    }
}
