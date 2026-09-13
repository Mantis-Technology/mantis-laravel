<?php

namespace App\Factories;

class TextareaField extends Field
{
    public function __construct(string $name, bool $required, int $order)
    {
        parent::__construct($name, FieldType::TEXTAREA, $required, $order);
    }
}
