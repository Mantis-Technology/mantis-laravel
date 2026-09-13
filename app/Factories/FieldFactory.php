<?php

namespace App\Factories;

class FieldFactory
{
    /**
     * @param  array<string, mixed>  $config
     */
    public static function createField(string $name, FieldType $type, bool $required, int $order, array $config): Field
    {
        return match ($type) {
            FieldType::TEXT => new TextField($name, $required, $order),
            FieldType::NUMBER => new NumberField($name, $required, $order, $config['minValue'] ?? 0, $config['maxValue'] ?? PHP_INT_MAX),
            FieldType::DATE => new DateField($name, $required, $order),
            FieldType::SELECT => new SelectField($name, $required, $order, $config['options'] ?? []),
            FieldType::CHECKBOX => new CheckboxField($name, $required, $order),
            FieldType::RADIO => new RadioField($name, $required, $order, $config['options'] ?? []),
            FieldType::TEXTAREA => new TextareaField($name, $required, $order),
            FieldType::EMAIL => new EmailField($name, $required, $order),
            FieldType::SELECT_MULTIPLE => new SelectMultipleField($name, $required, $order, $config['options'] ?? []),
            FieldType::FILE => new FileField($name, $required, $order, $config['allowedMimeTypes'] ?? [], $config['maxFileSize'] ?? 0),
            FieldType::URL => new UrlField($name, $required, $order),
        };
    }
}
