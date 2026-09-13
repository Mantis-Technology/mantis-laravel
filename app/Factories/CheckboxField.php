<?php

namespace App\Factories;

class CheckboxField extends Field
{
    public function __construct(string $name, bool $required, int $order)
    {
        parent::__construct($name, FieldType::CHECKBOX, $required, $order);
    }
}
