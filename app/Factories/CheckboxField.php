<?php

namespace App\Factories;

class CheckboxField extends Field
{
    public function __construct(string $name, string $label, bool $required, int $order, ?string $placeholder = null)
    {
        parent::__construct($name, $label, FieldType::CHECKBOX, $required, $order, $placeholder);
    }
}
