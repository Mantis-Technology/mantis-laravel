<?php

namespace App\Factories;

class DateField extends Field
{
    public function __construct(string $name, string $label, bool $required, int $order, ?string $placeholder = null)
    {
        parent::__construct($name, $label, FieldType::DATE, $required, $order, $placeholder);
    }
}
