<?php

namespace App\Factories;

class TextareaField extends Field
{
    public function __construct(string $name, string $label, bool $required, int $order, ?string $placeholder = null)
    {
        parent::__construct($name, $label, FieldType::TEXTAREA, $required, $order, $placeholder);
    }
}
